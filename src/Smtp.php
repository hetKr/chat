<?php
/**
 * Smtp
 * ----
 * Prosty klient SMTP napisany od zera (bez zewnętrznych bibliotek).
 * Łączy się z serwerem poczty, szyfruje połączenie (STARTTLS), loguje się
 * i wysyła pojedynczą wiadomość. Używany przez klasę Mailer.
 *
 * Działanie krok po kroku (protokół SMTP to rozmowa komenda → odpowiedź):
 *   220  powitanie serwera
 *   EHLO          → przedstawiamy się
 *   STARTTLS      → włączamy szyfrowanie
 *   AUTH LOGIN    → logujemy się (login i hasło w base64)
 *   MAIL FROM     → nadawca
 *   RCPT TO       → odbiorca
 *   DATA … .      → nagłówki i treść, zakończone kropką
 *   QUIT          → koniec
 */
class Smtp
{
    private $socket;

    /**
     * Wysyła wiadomość. Zwraca ['ok' => bool, 'error' => ?string].
     * $cfg to tablica z config.php (host, port, user, pass, from, from_name).
     */
    public static function send(array $cfg, string $to, string $subject, string $body): array
    {
        $smtp = new self();
        try {
            $smtp->connect($cfg['host'], (int) $cfg['port']);
            $smtp->command("EHLO localhost", 250);
            $smtp->command("STARTTLS", 220);
            $smtp->enableTls();
            $smtp->command("EHLO localhost", 250);

            // Logowanie (login i hasło kodowane w base64).
            $smtp->command("AUTH LOGIN", 334);
            $smtp->command(base64_encode($cfg['user']), 334);
            $smtp->command(base64_encode($cfg['pass']), 235);

            // Koperta wiadomości.
            $smtp->command("MAIL FROM:<{$cfg['from']}>", 250);
            $smtp->command("RCPT TO:<$to>", 250);
            $smtp->command("DATA", 354);

            // Nagłówki + treść, zakończone linią z samą kropką.
            $headers  = "From: {$cfg['from_name']} <{$cfg['from']}>\r\n";
            $headers .= "To: <$to>\r\n";
            $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $smtp->command($headers . "\r\n" . $body . "\r\n.", 250);

            $smtp->command("QUIT", 221);
            fclose($smtp->socket);
            return ['ok' => true, 'error' => null];
        } catch (Throwable $e) {
            if ($smtp->socket) {
                @fclose($smtp->socket);
            }
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /** Otwiera połączenie TCP i czyta powitanie serwera (kod 220). */
    private function connect(string $host, int $port): void
    {
        $this->socket = stream_socket_client("tcp://$host:$port", $errno, $errstr, 15);
        if (!$this->socket) {
            throw new RuntimeException("Nie można połączyć z serwerem SMTP: $errstr");
        }
        $this->expect(220);
    }

    /** Włącza szyfrowanie TLS na otwartym połączeniu. */
    private function enableTls(): void
    {
        if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            throw new RuntimeException('Nie udało się włączyć szyfrowania TLS.');
        }
    }

    /** Wysyła komendę i sprawdza, czy serwer odpowiedział oczekiwanym kodem. */
    private function command(string $line, int $expectedCode): void
    {
        fwrite($this->socket, $line . "\r\n");
        $this->expect($expectedCode);
    }

    /** Czyta odpowiedź serwera i sprawdza jej kod (np. 250). */
    private function expect(int $code): void
    {
        $response = '';
        while ($line = fgets($this->socket, 512)) {
            $response .= $line;
            // W odpowiedzi wieloliniowej 4. znak to '-'; spacja oznacza ostatnią linię.
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        if ((int) substr($response, 0, 3) !== $code) {
            throw new RuntimeException("Serwer SMTP odpowiedział: " . trim($response));
        }
    }
}
