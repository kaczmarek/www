<?php

define('DEBUG', false); // Set to false on production

define('MAIL_SMTP', true); // Use SMTP, if "false" use "mail" and it settings
define('MAIL_SMTP_HOST', 'smtp.mandrillapp.com'); // (Only if you use "MAIL_SMTP == true") SMTP server
define('MAIL_SMTP_PORT', 587); // (ТOnly if you use "MAIL_SMTP == true")  port
define('MAIL_SMTP_USERNAME', 'marcin@saepia.net'); // (Only if you use "MAIL_SMTP == true")  user
define('MAIL_SMTP_PASSWORD', 'C1T0n-CmVHZJvQAlHua9AA'); // (Only if you use "MAIL_SMTP == true")  password

define('MAIL_SUBJECT', 'Wiadomość ze strony kaczmarek.szczecin.pl'); // Mail Subject
define('MAIL_TO_EMAIL', 'marcin@saepia.net'); // on which mail must be send mail

define('MAIL_MESSAGE_SUCCESS', 'Dziękuję za Twoją wiadomość!'); // Message from contact form when mail is succesfull send.
define('MAIL_MESSAGE_ERROR', 'Wystąpił problem przy wysyłaniu wiadomości, proszę spróbować później.');  // Message from contact form when mail is not send when send is failed.

if (!defined('__DIR__')) {
    define('__DIR__', dirname(__FILE__));
}

require_once __DIR__ . '/libs/Swift/lib/swift_required.php';

/**
 * Parse mail template
 *
 * @param string $name
 * @param string $email
 * @param string $message
 * @param string $type
 * @return string
 */
function mail_content_layout ($name, $email, $message, $type = 'html') {
    ob_start();
    require_once __DIR__.'/mail/template.' . $type;
    $output = ob_get_contents();
    ob_end_clean();
    return str_replace(array('{{name}}', '{{email}}', '{{message}}'), array($name, $email, nl2br($message)), $output);
}

/**
 * @param string $name
 * @param mix $default
 */
function post_param($name, $default = null) {
    return isset($_POST[$name]) ? $_POST[$name] : $default;
}

if ($_POST) {
    $name = htmlentities(strip_tags(post_param('name', '')));
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $message = htmlentities(strip_tags(post_param('message', '')));
    $errors = array();

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Nieprawidłowy adres e-mail';
    }

    if (empty($name)) {
        $errors['name'] = 'Imię i nazwisko musi być wypełnione.';
    }


    if (empty($message)) {
        $errors['message'] = 'Wiadomość nie może być pusta.';
    }

    if (count($errors) === 0) {
        try {
            if (MAIL_SMTP) {
                $transport = Swift_SmtpTransport::newInstance(MAIL_SMTP_HOST, MAIL_SMTP_PORT, 'ssl')
                    ->setUsername(MAIL_SMTP_USERNAME)
                    ->setPassword(MAIL_SMTP_PASSWORD);
                $mailer = Swift_Mailer::newInstance($transport);
            } else {
                $transport = Swift_MailTransport::newInstance();
                $mailer = Swift_Mailer::newInstance($transport);
            }

            $message = Swift_Message::newInstance(MAIL_SUBJECT)
                ->setFrom(array($email => $name))
                ->setTo(array(MAIL_TO_EMAIL))
                ->setBody(mail_content_layout($name, $email, $message), 'text/html')
                ->setBody(mail_content_layout($name, $email, $message, 'txt'), 'text/plain');
            $result = $mailer->send($message);

            if ($result) {
                echo json_encode(array(
                    'success' => MAIL_MESSAGE_SUCCESS
                ));
            } else {
                echo json_encode(array(
                    'error' => MAIL_MESSAGE_ERROR
                ));
            }
        } catch (Swift_TransportException $e) {
            echo json_encode(array(
                'error' => DEBUG ? $e->getMessage() : 'Nie udało się połączyć z serwerem poczty, spróbuj później.'
            ));
        } catch (Exception $e) {
            echo json_encode(array(
                'error' => DEBUG ? $e->getMessage() : 'Wystąpił błąd, spróbuj później.'
            ));
        }
    } else {
        echo json_encode(compact('errors'));
    }
} else {
    header('HTTP/1.0 400 Bad Request', true, 400);
}

?>
