<?php

namespace Classes;

require "app/php/mail/PHPMailer/src/PHPMailer.php";
require "app/php/mail/PHPMailer/src/SMTP.php";
require "app/php/mail/PHPMailer/src/Exception.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mail
{

    private array $email_details = [];

    public function __construct(string $var = null)
    {
        $this->email_details['to'] = $var;
    }

    public static function to(string $receiver)
    {
        return new self($receiver);
    }

    public function message(array|string $message)
    {
        if (is_array($message)) {
            $this->email_details['message'] = $message;
        } else if (is_string($message)) {
            $this->email_details['message'] = [
                "text" => $message
            ];
        }
        return $this;
    }

    public function from(string $from)
    {
        $this->email_details['from'] = $from;
        return $this;
    }

    public function subject(string|int $subject)
    {
        $this->email_details['subject'] = $subject;
        return $this;
    }

    public function fromEmail(string $fromEmail)
    {
        $this->email_details['fromEmail'] = $fromEmail;
        return $this;
    }

    public function page(string $page)
    {
        $this->email_details['page'] = $page;
        return $this;
    }

    public function template(string $template)
    {
        $this->email_details['template'] = $template;
        return $this;
    }

    public function send()
    {
        $data = $this->email_details;
        if (! isset($data['to'])) {
            throw new Exception("Send Email: 'to' is required");
        }
        $message = $data['message'] ?? [];
        $message = ["text" => "CTRX FRAMEWORK", "email_to" => $data['to'], ...$message];
        $template = $data['template'] ?? "email";
        $message = $this->email_template($template, $message ?? []);

        return $this->send_email(
            $data['to'],
            $data['subject'] ?? env("app_name"),
            $message,
            $data['from'] ?? env("app_name"),
            $data['fromEmail'] ?? "ctrx@outlook.com"
        );
    }

    public static function email_template(string $template, array $content = [])
    {
        $email_template = $template;
        $email_template = substr($email_template, -4) == ".php" ? $email_template : $email_template . ".php";
        $template = "app/php/templates/" . $email_template;
        if (\Classes\Ctrx::file_exists_strict($template)) {
            if (!empty($content)) {
                extract($content);
            }
            ob_start();
            include $template;
            $message = ob_get_clean();

            $message = self::preventEmailAutoLink($message);

            return $message;
        } else {
            return false;
        }
    }

    private static function preventEmailAutoLink(string $html): string
    {
        $pattern = '/(?<!href="mailto:)(?<!href=\'mailto:)(?<=[\s>]|^)([a-zA-Z0-9._%+-]+)@([a-zA-Z0-9.-]+\.[a-zA-Z]{2,})(?=[\s<]|$)/';

        return preg_replace_callback($pattern, function ($matches) {
            return $matches[1] . '&#64;' . $matches[2];
        }, $html);
    }

    public static function sendDirect(array $mail)
    {
        if (empty($mail)) {
            throw new Exception("Please add Email details");
        }
        return self::send_email(
            $mail['to'],
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
        $mail->Host       = env("smtp_host");
        $mail->SMTPAuth   = true;
        $mail->Username   = env("smtp_user");
        $mail->Password   = env("smtp_password");
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = env("smtp_port");

        $e_sender = $sender ?? env("sender_name") ?? "CODETAZER";
        $e_sendemail = $sender_email ?? env("sender_email") ?? "codetazer@test.com";

        $mail->setFrom($e_sendemail, $e_sender);
        if (is_string($to)) {
            $mail->addAddress($to);
        }
        if (is_array($to)) {
            foreach ($to as $t) {
                $mail->addAddress($t);
            }
        }

        $mail->isHTML(true);
        $mail->Subject = $subject;

        $message = self::addAntiLinkMeta($message);

        $mail->Body    = $message;
        $mail->AltBody = strip_tags($message);

        $mail->send();
        return true;
    }

    private static function addAntiLinkMeta(string $html): string
    {
        $meta = '<meta name="format-detection" content="telephone=no, email=no">';

        if (strpos($html, '<head>') !== false) {
            return str_replace('<head>', '<head>' . $meta, $html);
        }

        return '<head>' . $meta . '</head>' . $html;
    }
}
