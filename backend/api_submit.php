<?php
require 'config.php';

// --- 1. CORS (Obrigatório para o Next.js falar com o PHP) ---
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
}
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    exit(0);
}

header("Content-Type: application/json; charset=UTF-8");

// --- 2. RECEBER DADOS ---
$input = json_decode(file_get_contents("php://input"), true);
$form_id = $input['form_id'] ?? null;
$formData = $input['data'] ?? [];

// --- 3. BUSCA DADOS DO FORMULÁRIO ---
try {
    $stmtForm = $pdo->prepare("SELECT title, recipient_email FROM forms WHERE id = ?");
    $stmtForm->execute([$form_id]);
    $formInfo = $stmtForm->fetch(PDO::FETCH_ASSOC);

    if (!$formInfo) {
        throw new Exception("Formulário não encontrado.");
    }

    // --- 4. SALVA NO BANCO (JSON) ---
    $jsonData = json_encode($formData, JSON_UNESCAPED_UNICODE);
    $stmt = $pdo->prepare("INSERT INTO form_submissions (form_id, data, email_status) VALUES (?, ?, ?)");
    $stmt->execute([$form_id, $jsonData, 'Pendente']);
    $submission_id = $pdo->lastInsertId();

    // --- 5. ENVIA O E-MAIL (MODO NATIVO / WORDPRESS) ---
    // Não precisa de senha. Usa o servidor local.
    
    $para = $formInfo['recipient_email'];
    $assunto = "Novo Contato: " . $formInfo['title'];
    
    // Monta o corpo do e-mail
    $mensagem = "<h2>Novo contato recebido pelo site</h2><hr>";
    foreach ($formData as $key => $value) {
        $label = ucwords(str_replace('_', ' ', $key));
        $mensagem .= "<strong>$label:</strong> " . htmlspecialchars($value) . "<br>";
    }

    // Cabeçalhos OBRIGATÓRIOS para não cair no lixo
    // O 'From' tem que ser do seu domínio, mesmo que o email não exista.
    $headers  = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
    $headers .= "From: =?UTF-8?B?".base64_encode("Site André Ventura")."?= <no-reply@asventura.com.br>" . "\r\n";
    
    // Se o cliente mandou e-mail, coloca no Reply-To (Responder Para)
    if (!empty($formData['email'])) {
        $headers .= "Reply-To: " . $formData['email'] . "\r\n";
    }

    // A FUNÇÃO MÁGICA DO PHP
    $enviou = mail($para, $assunto, $mensagem, $headers);

    // Atualiza status
    $statusFinal = $enviou ? 'Enviado' : 'Falha Local';
    $pdo->prepare("UPDATE form_submissions SET email_status = ? WHERE id = ?")->execute([$statusFinal, $submission_id]);

    echo json_encode(["success" => true, "message" => "Recebido com sucesso!"]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Erro: " . $e->getMessage()]);
}
?>