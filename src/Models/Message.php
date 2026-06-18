<?php
/**
 * Message (model) — GŁÓWNY ZASÓB aplikacji
 * ----------------------------------------
 * Odpowiada za pełny CRUD na wiadomościach oraz za listę z wyszukiwaniem,
 * filtrowaniem, sortowaniem i paginacją (wszystko po stronie serwera/SQL).
 */
class Message
{
    /** Dozwolone typy wiadomości — wykorzystywane też przy walidacji. */
    public const TYPES = ['tekst', 'obraz', 'film', 'plik', 'link'];

    /** Kolumny, po których wolno sortować (zabezpieczenie przed SQL injection). */
    private const SORTABLE = ['created_at', 'type', 'priority', 'is_important'];

    /** Pobiera pojedynczą wiadomość wraz z nazwą nadawcy i rozmowy. */
    public static function find(int $id): ?array
    {
        $stmt = Database::run(
            'SELECT m.*, u.display_name AS author, c.name AS conversation_name
             FROM messages m
             JOIN users u ON u.id = m.user_id
             JOIN conversations c ON c.id = m.conversation_id
             WHERE m.id = :id',
            [':id' => $id]
        );
        return $stmt->fetch() ?: null;
    }

    /** Dodaje nową wiadomość, zwraca jej ID. */
    public static function create(array $data): int
    {
        Database::run(
            'INSERT INTO messages (conversation_id, user_id, type, body, link_url, file_path, priority, is_important)
             VALUES (:conversation_id, :user_id, :type, :body, :link_url, :file_path, :priority, :is_important)',
            self::bind($data)
        );
        return (int) Database::pdo()->lastInsertId();
    }

    /** Aktualizuje istniejącą wiadomość. */
    public static function update(int $id, array $data): void
    {
        $params = self::bind($data);
        unset($params[':user_id']);   // nadawcy nie zmieniamy przy edycji
        $params[':id'] = $id;
        Database::run(
            'UPDATE messages SET
                conversation_id = :conversation_id,
                type = :type,
                body = :body,
                link_url = :link_url,
                file_path = :file_path,
                priority = :priority,
                is_important = :is_important
             WHERE id = :id',
            $params
        );
    }

    /**
     * Usuwa wiadomość ORAZ powiązany plik z dysku (jeśli istnieje).
     * To spełnia wymóg: usunięcie rekordu kasuje plik z dysku.
     */
    public static function delete(int $id): void
    {
        $message = self::find($id);
        if ($message && !empty($message['file_path'])) {
            $path = BASE_PATH . '/' . $message['file_path'];
            if (is_file($path)) {
                unlink($path);
            }
        }
        Database::run('DELETE FROM messages WHERE id = :id', [':id' => $id]);
    }

    /**
     * Lista wiadomości z wyszukiwaniem, 3 filtrami, sortowaniem i paginacją.
     * $filters: search, type, conversation_id, important, sort, dir, page.
     * Zwraca: ['rows' => [...], 'total' => int, 'pages' => int, 'page' => int].
     */
    public static function paginate(array $filters, ?int $limitToUser = null): array
    {
        [$where, $params] = self::buildFilters($filters, $limitToUser);

        // 1. Liczymy wszystkie pasujące rekordy (do paginacji).
        $total = (int) Database::run(
            "SELECT COUNT(*) FROM messages m JOIN users u ON u.id = m.user_id $where",
            $params
        )->fetchColumn();

        // 2. Sortowanie po dozwolonej kolumnie i kierunku.
        $sort = in_array($filters['sort'] ?? '', self::SORTABLE, true) ? $filters['sort'] : 'created_at';
        $dir  = strtolower($filters['dir'] ?? '') === 'asc' ? 'ASC' : 'DESC';

        // 3. Paginacja: przesunięcie wynikające z numeru strony.
        $page   = max(1, (int) ($filters['page'] ?? 1));
        $offset = ($page - 1) * PER_PAGE;

        $rows = Database::run(
            "SELECT m.*, u.display_name AS author, c.name AS conversation_name
             FROM messages m
             JOIN users u ON u.id = m.user_id
             JOIN conversations c ON c.id = m.conversation_id
             $where
             ORDER BY m.$sort $dir
             LIMIT " . PER_PAGE . " OFFSET $offset",
            $params
        )->fetchAll();
        // Uwaga: $sort, $dir, $limit, $offset pochodzą z białej listy / rzutowania na int,
        // więc bezpiecznie wstawiamy je wprost do zapytania.

        return [
            'rows'  => $rows,
            'total' => $total,
            'pages' => max(1, (int) ceil($total / PER_PAGE)),
            'page'  => $page,
        ];
    }

    /** Dane zagregowane do panelu podsumowania (liczby wg typu itp.). */
    public static function aggregate(?int $limitToUser = null): array
    {
        [$where, $params] = self::buildFilters([], $limitToUser);

        $byType = Database::run(
            "SELECT type, COUNT(*) AS c FROM messages m JOIN users u ON u.id = m.user_id $where GROUP BY type",
            $params
        )->fetchAll();

        $total     = (int) Database::run("SELECT COUNT(*) FROM messages m JOIN users u ON u.id = m.user_id $where", $params)->fetchColumn();
        $important = (int) Database::run("SELECT COUNT(*) FROM messages m JOIN users u ON u.id = m.user_id $where" . ($where ? ' AND' : ' WHERE') . ' m.is_important = 1', $params)->fetchColumn();

        return [
            'total'     => $total,
            'important' => $important,
            'by_type'   => $byType,
        ];
    }

    // ---------------------------------------------------------------
    //  Metody prywatne (szczegóły implementacji)
    // ---------------------------------------------------------------

    /**
     * Buduje fragment WHERE i parametry na podstawie filtrów.
     * Zwraca [string $where, array $params].
     */
    private static function buildFilters(array $filters, ?int $limitToUser): array
    {
        $conditions = [];
        $params = [];

        // Ograniczenie do rozmów użytkownika (zwykły użytkownik nie widzi cudzych).
        if ($limitToUser !== null) {
            $ids = Conversation::idsForUser($limitToUser);
            if ($ids === []) {
                return ['WHERE 1 = 0', []];   // brak rozmów = brak wiadomości
            }
            $place = implode(',', array_map('intval', $ids));
            $conditions[] = "m.conversation_id IN ($place)";
        }

        // Wyszukiwanie pełnotekstowe: treść lub nazwa nadawcy.
        if (!empty($filters['search'])) {
            $conditions[] = '(m.body LIKE :search OR u.display_name LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        // Filtr 1: typ wiadomości.
        if (!empty($filters['type']) && in_array($filters['type'], self::TYPES, true)) {
            $conditions[] = 'm.type = :type';
            $params[':type'] = $filters['type'];
        }

        // Filtr 2: rozmowa.
        if (!empty($filters['conversation_id'])) {
            $conditions[] = 'm.conversation_id = :cid';
            $params[':cid'] = (int) $filters['conversation_id'];
        }

        // Filtr 3: tylko ważne.
        if (!empty($filters['important'])) {
            $conditions[] = 'm.is_important = 1';
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        return [$where, $params];
    }

    /** Przygotowuje parametry zapytania INSERT/UPDATE z danych formularza. */
    private static function bind(array $data): array
    {
        return [
            ':conversation_id' => (int) $data['conversation_id'],
            ':user_id'         => (int) ($data['user_id'] ?? 0),
            ':type'            => $data['type'],
            ':body'            => $data['body'] ?? '',
            ':link_url'        => $data['link_url'] ?? null,
            ':file_path'       => $data['file_path'] ?? null,
            ':priority'        => (int) ($data['priority'] ?? 1),
            ':is_important'    => !empty($data['is_important']) ? 1 : 0,
        ];
    }
}
