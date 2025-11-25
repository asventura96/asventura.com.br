<?php
require 'config.php';

// --- 1. SEGURANÇA E CORS (Obrigatório para POST funcionar entre Local e Servidor) ---
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');    // Cache por 1 dia
}

// Se o navegador perguntar "Posso enviar?", respondemos "Sim" e encerramos.
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    
    exit(0);
}

// Define que a resposta será sempre JSON
header("Content-Type: application/json; charset=UTF-8");

// --- 2. RECEBIMENTO DOS DADOS ---
try {
    $inputJSON = file_get_contents("php://input");
    $input = json_decode($inputJSON, true);

    // Debug: Se o JSON vier quebrado
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("JSON inválido enviado pelo site.");
    }

    $form_id = $input['form_id'] ?? null;
    $formData = $input['data'] ?? [];

    if (!$form_id || empty($formData)) {
        throw new Exception("Dados incompletos (Faltando form_id ou campos preenchidos).");
    }

    // --- 3. BUSCA CONFIGURAÇÃO DO FORMULÁRIO ---
    $stmtForm = $pdo->prepare("SELECT title, recipient_email FROM forms WHERE id = ?");
    $stmtForm->execute([$form_id]);
    $formInfo = $stmtForm->fetch(PDO::FETCH_ASSOC);

    if (!$formInfo) {
        throw new Exception("Formulário ID $form_id não existe no sistema.");
    }

    // --- 4. SALVA NO BANCO (JSON) ---
    $jsonData = json_encode($formData, JSON_UNESCAPED_UNICODE);
    
    $stmt = $pdo->prepare("INSERT INTO form_submissions (form_id, data, email_status) VALUES (?, ?, ?)");
    if (!$stmt->execute([$form_id, $jsonData, 'Pendente'])) {
        throw new Exception("Erro ao salvar no banco de dados.");
    }
    
    $submission_id = $pdo->lastInsertId();

    // --- 5. ENVIA O E-MAIL ---
    $emailSent = false;
    $mailBody = "<h2>Novo contato: " . htmlspecialchars($formInfo['title']) . "</h2><hr>";
    
    foreach ($formData as $key => $value) {
        $label = ucwords(str_replace('_', ' ', $key));
        $mailBody .= "<strong>$label:</strong> " . htmlspecialchars($value) . "<br>";
    }

    // Tenta usar PHPMailer se disponível, senão usa mail() nativo
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = 'tls'; 
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom(SMTP_USER, 'Site Notificação');
            $mail->addAddress($formInfo['recipient_email']);
            
            if (!empty($formData['email'])) {
                $mail->addReplyTo($formData['email']);
            }

            $mail->isHTML(true);
            $mail->Subject = "Novo Lead: " . $formInfo['title'];
            $mail->Body    = $mailBody;

            $mail->send();
            $emailSent = true;
        } catch (Exception $e) {
            // Erro de e-mail não deve parar o sucesso do formulário
            // Apenas logamos que falhou o envio
        }
    } else {
        // Fallback simples
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: " . SMTP_USER;
        $emailSent = mail($formInfo['recipient_email'], "Novo Lead: " . $formInfo['title'], $mailBody, $headers);
    }

    // Atualiza status
    $statusFinal = $emailSent ? 'Enviado' : 'Falha Envio';
    $pdo->prepare("UPDATE form_submissions SET email_status = ? WHERE id = ?")->execute([$statusFinal, $submission_id]);

    // SUCESSO FINAL
    echo json_encode(["success" => true, "message" => "Recebido com sucesso!"]);

} catch (Exception $e) {
    // Captura qualquer erro e devolve para o Frontend ver
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>