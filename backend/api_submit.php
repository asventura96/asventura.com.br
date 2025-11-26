<?php
require 'config.php';

// 1. CORS
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

// 2. Recebe dados
$input = json_decode(file_get_contents("php://input"), true);
$form_id = $input['form_id'] ?? null;
$formData = $input['data'] ?? [];

try {
    // 3. Busca destino
    $stmtForm = $pdo->prepare("SELECT title, recipient_email FROM forms WHERE id = ?");
    $stmtForm->execute([$form_id]);
    $formInfo = $stmtForm->fetch(PDO::FETCH_ASSOC);

    if (!$formInfo) throw new Exception("Formulário não encontrado.");

    // 4. Salva no Banco
    $jsonData = json_encode($formData, JSON_UNESCAPED_UNICODE);
    $stmt = $pdo->prepare("INSERT INTO form_submissions (form_id, data, email_status) VALUES (?, ?, ?)");
    $stmt->execute([$form_id, $jsonData, 'Pendente']);
    $submission_id = $pdo->lastInsertId();

    // --- 5. ENVIO DE E-MAIL NATIVO (BLINDADO) ---
    
    $para = $formInfo['recipient_email'];
    $assunto = "Novo Contato: " . $formInfo['title'];
    
    // Define o remetente oficial
    // IMPORTANTE: Use um e-mail que 'pareça' real do domínio
    $emailSistema = "no-reply@asventura.com.br"; 
    $nomeSite = "Site Andre Ventura"; // Sem acentos no código pra garantir compatibilidade máxima

    // Corpo do E-mail
    $mensagem = "
    <html>
    <body style='font-family: Arial, sans-serif; color: #333;'>
      <div style='background-color: #f9f9f9; padding: 20px; border: 1px solid #ddd;'>
        <h2 style='color: #023047; margin-top: 0;'>Novo Lead: {$formInfo['title']}</h2>
        <hr style='border: 0; border-top: 1px solid #ccc; margin: 20px 0;'>
    ";
    
    foreach ($formData as $key => $value) {
        $label = ucwords(str_replace('_', ' ', $key));
        $val = htmlspecialchars($value);
        $mensagem .= "<p style='margin: 10px 0;'><strong>$label:</strong><br>$val</p>";
    }
    
    $mensagem .= "
        <hr style='border: 0; border-top: 1px solid #ccc; margin: 20px 0;'>
        <p style='font-size: 12px; color: #999;'>Enviado via formulário do site.</p>
      </div>
    </body>
    </html>
    ";

    // CABEÇALHOS ESPECIAIS (PARA MATAR O 'EM NOME DE')
    // O segredo é alinhar o FROM, o SENDER e o RETURN-PATH
    
    $headers  = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
    
    // Remetente
    $headers .= "From: $nomeSite <$emailSistema>" . "\r\n";
    
    // Força o servidor a entender que quem manda é o domínio, não o usuário Linux
    $headers .= "Sender: $emailSistema" . "\r\n";
    $headers .= "Return-Path: $emailSistema" . "\r\n";
    $headers .= "X-Sender: $emailSistema" . "\r\n";
    $headers .= "X-Priority: 1 (Highest)" . "\r\n"; // Tenta furar o filtro de spam
    
    if (!empty($formData['email'])) {
        $headers .= "Reply-To: " . $formData['email'] . "\r\n";
    }

    // Parâmetro -f (Envelope Sender)
    // Isso é OBRIGATÓRIO para o SPF da Hostinger funcionar
    $parametros = "-f$emailSistema";

    // Força configuração do PHP para este envio
    ini_set('sendmail_from', $emailSistema);

    // Envia
    $enviou = mail($para, $assunto, $mensagem, $headers, $parametros);

    // Atualiza status
    $statusFinal = $enviou ? 'Enviado' : 'Falha Local';
    $pdo->prepare("UPDATE form_submissions SET email_status = ? WHERE id = ?")->execute([$statusFinal, $submission_id]);

    echo json_encode(["success" => true, "message" => "Recebido com sucesso!"]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Erro: " . $e->getMessage()]);
}
?>