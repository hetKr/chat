<?php
/**
 * Mailer
 * ------
 * Wysyłanie wiadomości e-mail (np. linku aktywacyjnego po rejestracji).
 * Korzysta z gotowej, sprawdzonej biblioteki PHPMailer (instalowanej przez
 * Composera) — dzięki temu nie musimy sami obsługiwać protokołu SMTP.
 * Dodatkowo zapisuje kopię do lokalnej skrzynki nadawczej
 * (data/mail_outbox.log), więc zawsze widać, co zostało wysłane.
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
    /** Wysyła e-mail z linkiem aktywacyjnym konta. Zwraca true przy sukcesie. */
    public static function sendVerification(string $to, string $link): bool
    {
        $subject = 'Potwierdź swoje konto w komunikatorze';
        $body = "Witaj!\n\nKliknij poniższy link, aby aktywować konto:\n$link\n";
        return self::send($to, $subject, $body);
    }

    /** Wysyła e-mail przez SMTP (PHPMailer) i zapisuje kopię w skrzynce nadawczej. */
    private static function send(string $to, string $subject, string $body): bool
    {
        $mail = new PHPMailer(true);   // true = rzucaj wyjątki przy błędach

        try {
            // --- Konfiguracja serwera SMTP (dane z config.php) ---
            $mail->isSMTP();
            $mail->Host       = MAIL['host'];
            $mail->Port       = (int) MAIL['port'];
            $mail->SMTPAuth   = true;
            $mail->Username   = MAIL['user'];
            $mail->Password   = MAIL['pass'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;   // szyfrowanie połączenia
            $mail->CharSet    = 'UTF-8';                          // poprawne polskie znaki

            // --- Treść wiadomości ---
            $mail->setFrom(MAIL['from'], MAIL['from_name']);
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->Body    = $body;

            $mail->send();
            $ok = true;
            $error = null;
        } catch (Exception $e) {
            $ok = false;
            // PHPMailer wkłada czytelny opis błędu do właściwości ErrorInfo.
            $error = $mail->ErrorInfo;
        }

        // Kopia lokalna — zapis do pliku z informacją, czy wysyłka się powiodła.
        $status = $ok ? 'WYSŁANO' : ('BŁĄD: ' . $error);
        $entry  = '[' . date('Y-m-d H:i:s') . "] [$status] Do: $to\nTemat: $subject\n$body\n----------\n";
        file_put_contents(BASE_PATH . '/data/mail_outbox.log', $entry, FILE_APPEND);

        return $ok;
    }
}
