<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer-master/PHPMailer-master/src/Exception.php';
require_once __DIR__ . '/PHPMailer-master/PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer-master/PHPMailer-master/src/SMTP.php';

if (!function_exists('send_activation_email')) {
    function send_activation_email($to, $code) {

        $link = "http://localhost/Fiszkers/activate.php?code=$code";

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp-relay.brevo.com';
            $mail->SMTPAuth = true;

            // Wpisz swoje dane
            $mail->Username = 'a08032001@smtp-brevo.com';
            $mail->Password = '9XE04RKwICQ5BzZy';

            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('maksymilianlesniak@icloud.com', 'Fiszkers');
            $mail->addAddress($to);

            $mail->isHTML(true);
            $mail->Subject = 'Aktywacja konta Fiszkers';
            $mail->Body = "
                <h2>Aktywacja konta</h2>
                <p>Kliknij poniższy link, aby aktywować konto:</p>
                <p><a href='$link'>$link</a></p>
            ";

            $mail->send();

        } catch (Exception $e) {
            echo "Błąd wysyłania maila: {$mail->ErrorInfo}";
        }
    }
}
