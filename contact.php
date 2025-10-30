<?php
// contact.php - secure contact form handler
// Receives POST from the contact form, validates inputs, prevents header injection,
// attempts to send mail, then redirects back to index.html with a status.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html?status=error&msg=invalid_request');
    exit;
}

function contains_newlines($str) {
    return preg_match('/[\r\n]/', $str);
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$message = trim($_POST['message'] ?? '');

// Basic required validation
if ($name === '' || $email === '' || $message === '') {
    header('Location: index.html?status=error&msg=missing_fields');
    exit;
}

// Prevent header injection
if (contains_newlines($name) || contains_newlines($email)) {
    header('Location: index.html?status=error&msg=invalid_input');
    exit;
}

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: index.html?status=error&msg=invalid_email');
    exit;
}

require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);
try {
    // Gmail SMTP settings
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'nyandorovictor3900@gmail.com';
    $mail->Password = 'zemhzzikjtprbotl';  // Gmail App Password (spaces removed)
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('nyandorovictor3900@gmail.com', 'Portfolio Contact Form');
    $mail->addReplyTo($email, $name);
    $mail->addAddress('nyandorovictor3900@gmail.com');

    $mail->Subject = 'New Contact Form Message from ' . $name;
    $mail->isHTML(true);
    
    // Create HTML email body with your portfolio styling
    $htmlBody = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            /* Matching your portfolio styles */
            body {
                font-family: "Poppins", sans-serif;
                line-height: 1.6;
                color: rgb(68, 68, 68);
                background: rgb(250, 250, 250);
            }
            .message-container {
                max-width: 600px;
                margin: 20px auto;
                padding: 30px;
                background: white;
                border-radius: 20px;
                box-shadow: 1px 8px 10px 2px rgba(0, 0, 0, 0.1);
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
                color: rgb(30, 159, 171);
                font-size: 24px;
                font-weight: 600;
            }
            .info-item {
                margin-bottom: 15px;
                padding: 15px;
                background: rgba(0, 201, 255, 0.05);
                border-radius: 10px;
            }
            .info-label {
                font-weight: 600;
                color: rgb(110, 87, 224);
                margin-bottom: 5px;
            }
            .info-text {
                color: rgb(68, 68, 68);
            }
            .message-text {
                white-space: pre-wrap;
                padding: 20px;
                background: rgba(192, 166, 49, 0.05);
                border-radius: 10px;
                margin-top: 20px;
            }
        </style>
    </head>
    <body>
        <div class="message-container">
            <div class="header">New Portfolio Contact Message</div>
            <div class="info-item">
                <div class="info-label">From:</div>
                <div class="info-text">' . htmlspecialchars($name) . '</div>
            </div>
            <div class="info-item">
                <div class="info-label">Email:</div>
                <div class="info-text">' . htmlspecialchars($email) . '</div>
            </div>
            <div class="info-item">
                <div class="info-label">Message:</div>
                <div class="message-text">' . nl2br(htmlspecialchars($message)) . '</div>
            </div>
        </div>
    </body>
    </html>';

    $mail->Body = $htmlBody;
    // Plain text alternative
    $mail->AltBody = "From: $name\nEmail: $email\n\nMessage:\n$message";
    
    $mail->send();
    header('Location: index.html?status=success');
} catch (Exception $e) {
    // If mail fails (common on local/dev environments), save the message to a local log
    // so you don't lose submissions. This is a safe fallback for development.
    $logDir = __DIR__ . DIRECTORY_SEPARATOR . 'logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    $logFile = $logDir . DIRECTORY_SEPARATOR . 'contacts.log';
    $entry  = "---\n";
    $entry .= "Date: " . date('Y-m-d H:i:s') . "\n";
    $entry .= "IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n";
    $entry .= "Name: $name\n";
    $entry .= "Email: $email\n";
    $entry .= "Message:\n" . $message . "\n";
    $entry .= "---\n\n";
    @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);

    // Redirect back with a specific msg so the UI can tell the user the message was saved.
    header('Location: index.html?status=success&msg=saved_to_log');
    exit;
}

?>