<?php

/**
 * Mail Service
 *
 * Handles email sending using PHPMailer
 */
class MailService
{
    /**
     * Send an email
     */
    public function send(string $to, string $subject, string $template, array $data): void
    {
        // Render email template
        $body = $this->renderTemplate($template, $data);

        // Log email for debugging
        $this->logEmail($to, $subject, $body);

        // Send actual email
        try {
            $this->sendEmail($to, $subject, $body);
        } catch (Exception $e) {
            error_log('Email send failed: ' . $e->getMessage());
        }
    }

    /**
     * Send email using PHPMailer
     */
    private function sendEmail(string $to, string $subject, string $body): void
    {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        // Server settings
        $mail->isSMTP();
        $mail->Host       = getenv('MAIL_HOST');
        $mail->SMTPAuth   = true;
        $mail->Username   = getenv('MAIL_USERNAME');
        $mail->Password   = getenv('MAIL_PASSWORD');
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int) getenv('MAIL_PORT');

        // Recipients
        $mail->setFrom(getenv('MAIL_FROM_ADDRESS'), getenv('MAIL_FROM_NAME'));
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
    }

    /**
     * Render email template
     */
    private function renderTemplate(string $template, array $data): string
    {
        extract($data);

        ob_start();

        $templateFile = __DIR__ . '/../../views/' . $template . '.php';

        if (file_exists($templateFile)) {
            require $templateFile;
        } else {
            // Fallback to simple text email
            return $this->renderFallbackTemplate($template, $data);
        }

        return ob_get_clean();
    }

    /**
     * Render fallback template when template file doesn't exist
     */
    private function renderFallbackTemplate(string $template, array $data): string
    {
        $output = "Email Template: $template\n\n";

        foreach ($data as $key => $value) {
            $output .= ucfirst($key) . ": $value\n";
        }

        return $output;
    }

    /**
     * Log email for development
     */
    private function logEmail(string $to, string $subject, string $body): void
    {
        $logMessage = sprintf(
            "[%s] Email sent to: %s\nSubject: %s\nBody:\n%s\n%s\n",
            date('Y-m-d H:i:s'),
            $to,
            $subject,
            $body,
            str_repeat('-', 80)
        );

        $logFile = __DIR__ . '/../../logs/email.log';
        error_log($logMessage, 3, $logFile);
    }
}
