<?php
/**
 * Database
 * --------
 * Jedyne miejsce w aplikacji, które łączy się z bazą danych.
 * Opakowuje obiekt PDO (PHP Data Objects) — dostęp do bazy w stylu obiektowym.
 *
 * Wzorzec "singleton": połączenie tworzymy tylko raz i współdzielimy
 * je w całej aplikacji przez statyczną metodę Database::pdo().
 */
class Database
{
    /** Jedyna, współdzielona instancja połączenia PDO. */
    private static ?PDO $instance = null;

    /** Zwraca połączenie PDO (tworzy je przy pierwszym wywołaniu). */
    public static function pdo(): PDO
    {
        if (self::$instance === null) {
            self::$instance = self::connect();
        }
        return self::$instance;
    }

    /** Tworzy nowe połączenie z bazą SQLite i ustawia tryby pracy PDO. */
    private static function connect(): PDO
    {
        $pdo = new PDO('sqlite:' . DB_PATH);

        // Rzucaj wyjątki przy błędach SQL (łatwiej wykrywać pomyłki).
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // Domyślnie zwracaj wiersze jako tablice asocjacyjne.
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        // SQLite domyślnie ignoruje klucze obce — tu je włączamy.
        $pdo->exec('PRAGMA foreign_keys = ON');

        return $pdo;
    }

    /**
     * Skrót do wykonania zapytania z parametrami (zapytania przygotowane).
     * Zwraca obiekt PDOStatement, z którego pobieramy wyniki.
     */
    public static function run(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}
