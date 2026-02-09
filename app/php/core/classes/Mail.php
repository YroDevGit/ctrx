<?php

namespace Classes;

require "_backend/core/mail/PHPMailer/src/PHPMailer.php";
require "_backend/core/mail/PHPMailer/src/SMTP.php";
require "_backend/core/mail/PHPMailer/src/Exception.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mail
{

    public static function email_template(string $template, array $content = [])
    {
        $email_template = $template;
        $email_template = substr($email_template, -4) == ".php" ? $email_template : $email_template . ".php";
        $template = "_backend/core/template/" . $email_template;
        if (file_exists($template)) {
            if (!empty($content)) {
                extract($content);
            }
            ob_start();
            include $template;
            $message = ob_get_clean();
            return $message;
        } else {
            return false;
        }
    }

    public static function send(array $mail){
        if(empty($mail)){
            throw new Exception("Please add Email details");
        }
        return self::send_email(
            $main['to'],
            $mail['subject'],
            $mail['message'] ?? $mail['msg'],
            $mail['sender'] ?? $mail['from'] ?? null,
            $mail['senderemail'] ?? $mail['myemail'] ?? null
        );
    }

    public static function send_email(string|array $to, string $subject, $message, string|null $sender = null, string|null $sender_email = null)
    {
        if (!function_exists('has_internet_connection') || !has_internet_connection()) {
            throw new Exception("No Internet Connection");
        }

        if (!$message) {
            throw new Exception("Message not found");
        }
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = getenv("smtp_host");
        $mail->SMTPAuth   = true;
        $mail->Username   = getenv("smtp_user");
        $mail->Password   = getenv("smtp_password");
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = getenv("smtp_port");

        $e_sender = $sender ?? getenv("sender_name") ?? "CODETAZER";
        $e_sendemail = $sender_email ?? getenv("sender_email") ?? "codetazer@test.com";

        $mail->setFrom($e_sendemail, $e_sender);
        if(is_string($to)){
            $mail->addAddress($to);
        }
        if(is_array($to)){
            foreach($to as $t){
                $mail->addAddress($t);
            }
        }

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;
        $mail->AltBody = strip_tags($message);

        $mail->send();
        return true;
    }
}
