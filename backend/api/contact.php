<?php
require_once __DIR__ . '/../config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Envia cabeçalhos CORS
send_cors_headers();

// Lida com a requisição OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Lida com a requisição POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Garante que o conteúdo é JSON
    $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
    if (strcasecmp($contentType, 'application/json') != 0) {
        json_response(['success' => false, 'message' => 'Content-Type deve ser application/json'], 400);
    }

    // Decodifica o JSON
    $data = json_decode(file_get_contents("php://input"), true);

    // Validação básica
    if (empty($data['name']) || empty($data['email']) || empty($data['message'])) {
        json_response(['success' => false, 'message' => 'Todos os campos são obrigatórios.'], 400);
    }

    $name = filter_var($data['name'], FILTER_SANITIZE_STRING);
    $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
    $message = filter_var($data['message'], FILTER_SANITIZE_STRING);

    try {
        // 1. Salva o contato no banco de dados
        $stmt = $pdo->prepare('INSERT INTO contacts (name, email, message) VALUES (?, ?, ?)');
        $stmt->execute([$name, $email, $message]);

        // 2. Envia o e-mail via SMTP
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';

        // Remetente
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        // Destinatário (para onde o e-mail será enviado)
        $mail->addAddress(SMTP_FROM_EMAIL, SMTP_FROM_NAME);

        // Conteúdo
        $mail->isHTML(true);
        $mail->Subject = 'Novo Contato Recebido de ' . $name;
        $mail->Body    = "
            <h2>Novo Contato Recebido</h2>
            <p><strong>Nome:</strong> {$name}</p>
            <p><strong>Email:</strong> {$email}</p>
            <p><strong>Mensagem:</strong></p>
            <p>{$message}</p>
        ";
        $mail->AltBody = "Novo Contato Recebido\nNome: {$name}\nEmail: {$email}\nMensagem: {$message}";

        $mail->send();

        json_response(['success' => true, 'message' => 'Mensagem enviada com sucesso!']);

    } catch (PDOException $e) {
        json_response(['success' => false, 'message' => 'Erro ao salvar contato: ' . $e->getMessage()], 500);
    } catch (Exception $e) {
        json_response(['success' => false, 'message' => 'Erro ao enviar e-mail: ' . $mail->ErrorInfo], 500);
    }
}

// Lida com métodos não permitidos
json_response(['success' => false, 'message' => 'Método não permitido.'], 405);

?>
