<?php
/**
 * Mailer
 * ------
 * Wysyłanie wiadomości e-mail (np. linku aktywacyjnego po rejestracji).
 * Wysyła pocztę przez serwer SMTP (klasa Smtp, konfiguracja w config.php)
 * i zapisuje kopię do lokalnej skrzynki nadawczej (data/mail_outbox.log),
 * dzięki czemu zawsze widać, co zostało wysłane.
 */
class Mailer
{
    /** Wysyła e-mail z linkiem aktywacyjnym konta. Zwraca true przy sukcesie. */
    public static function sendVerification(string $to, string $link): bool
    {
        $subject = 'Potwierdź swoje konto w komunikatorze';
        $body = "Witaj!\n\nKliknij poniższy link, aby aktywować konto:\n$link\n";
        return self::send($to, $subject, $body);
    }

    /** Wysyła e-mail przez SMTP i zapisuje kopię w skrzynce nadawczej. */
    private static function send(string $to, string $subject, string $body): bool
    {
        $result = Smtp::send(MAIL, $to, $subject, $body);

        // Kopia lokalna — zapis do pliku z informacją, czy wysyłka się powiodła.
        $status = $result['ok'] ? 'WYSŁANO' : ('BŁĄD: ' . $result['error']);
        $entry  = '[' . date('Y-m-d H:i:s') . "] [$status] Do: $to\nTemat: $subject\n$body\n----------\n";
        file_put_contents(BASE_PATH . '/data/mail_outbox.log', $entry, FILE_APPEND);

        return $result['ok'];
    }
}
