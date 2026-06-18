<?php
/**
 * MessageController
 * -----------------
 * Pełny CRUD głównego zasobu (wiadomości): lista z wyszukiwaniem,
 * filtrowaniem, sortowaniem i paginacją, formularz dodawania/edycji,
 * usuwanie oraz eksport do CSV / JSON.
 */
class MessageController
{
    private const MAX_FILE_BYTES = 5 * 1024 * 1024;   // 5 MB

    /** GET: tabela wiadomości + panel filtrów + podsumowanie. */
    public function index(): void
    {
        $user  = Auth::user();
        $scope = Auth::isAdmin() ? null : (int) $user['id'];   // admin widzi wszystko

        $filters = [
            'search'          => trim($_GET['search'] ?? ''),
            'type'            => $_GET['type'] ?? '',
            'conversation_id' => $_GET['conversation_id'] ?? '',
            'important'       => $_GET['important'] ?? '',
            'sort'            => $_GET['sort'] ?? 'created_at',
            'dir'             => $_GET['dir'] ?? 'desc',
            'page'            => $_GET['p'] ?? 1,   // 'p' = numer strony (parametr 'page' to router)
        ];

        $result    = Message::paginate($filters, $scope);
        $summary   = Message::aggregate($scope);
        $conversations = Conversation::forUser((int) $user['id']);

        view('messages/index', [
            'result'        => $result,
            'summary'       => $summary,
            'filters'       => $filters,
            'conversations' => $conversations,
            'types'         => Message::TYPES,
        ]);
    }

    /** GET: pusty formularz nowej wiadomości. */
    public function create(): void
    {
        $this->renderForm([], [], null);
    }

    /** GET: formularz edycji istniejącej wiadomości. */
    public function edit(): void
    {
        $message = $this->findOwnedOr404((int) ($_GET['id'] ?? 0));
        $this->renderForm($message, [], (int) $message['id']);
    }

    /** POST: zapis nowej lub edytowanej wiadomości (z walidacją serwerową). */
    public function save(): void
    {
        csrf_check();
        $user = Auth::user();
        $id   = (int) ($_POST['id'] ?? 0);

        // Dane z formularza.
        $data = [
            'conversation_id' => (int) ($_POST['conversation_id'] ?? 0),
            'type'            => $_POST['type'] ?? 'tekst',
            'body'            => trim($_POST['body'] ?? ''),
            'link_url'        => trim($_POST['link_url'] ?? ''),
            'priority'        => $_POST['priority'] ?? 1,
            'is_important'    => isset($_POST['is_important']) ? 1 : 0,
            'file_path'       => null,
        ];

        // --- Walidacja serwerowa ---
        // Reguły zależą od typu wiadomości: tekst wymaga treści, link adresu URL,
        // a obraz/film/plik wymaga załącznika.
        $userConversations = Conversation::idsForUser((int) $user['id']);
        $needsFile = in_array($data['type'], ['obraz', 'film', 'plik'], true);
        $hasUpload = !empty($_FILES['file']['name']);

        $v = new Validator();
        $v->inList('conversation_id', (string) $data['conversation_id'], array_map('strval', $userConversations), 'Wybierz prawidłową rozmowę.')
          ->inList('type', $data['type'], Message::TYPES, 'Wybierz typ wiadomości.')
          ->length('body', $data['body'], 0, 2000, 'Treść może mieć maksymalnie 2000 znaków.')
          ->intRange('priority', $data['priority'], 1, 5, 'Priorytet musi być liczbą od 1 do 5.');

        if ($data['type'] === 'tekst') {
            $v->required('body', $data['body'], 'Treść wiadomości jest wymagana.');
        }
        if ($data['type'] === 'link') {
            $v->url('link_url', $data['link_url'], 'Podaj poprawny adres URL (np. https://...).');
        }
        // Plik wymagany dla obraz/film/plik przy DODAWANIU nowej wiadomości.
        if ($needsFile && $id === 0 && !$hasUpload) {
            $v->addError('file', 'Dla tego typu wiadomości musisz wgrać plik.');
        }

        // Obsługa wgranego pliku (jeśli walidacja reszty nie ma sensu, i tak sprawdzamy plik).
        if ($hasUpload) {
            $upload = $this->handleUpload($_FILES['file']);
            if ($upload['error']) {
                $v->addError('file', $upload['error']);
            } else {
                $data['file_path'] = $upload['path'];
            }
        }

        if (!$v->passes()) {
            // Jeśli zdążyliśmy zapisać plik, ale formularz ma inne błędy — usuwamy plik.
            if (!empty($data['file_path']) && is_file(BASE_PATH . '/' . $data['file_path'])) {
                unlink(BASE_PATH . '/' . $data['file_path']);
            }
            $this->renderForm($data, $v->errors(), $id ?: null);
            return;
        }

        if ($id > 0) {
            // EDYCJA — zachowujemy stary plik, jeśli nie wgrano nowego.
            $existing = $this->findOwnedOr404($id);
            if ($data['file_path'] === null) {
                $data['file_path'] = $existing['file_path'];
            } elseif (!empty($existing['file_path'])) {
                // Wgrano nowy plik — kasujemy stary z dysku.
                $old = BASE_PATH . '/' . $existing['file_path'];
                if (is_file($old)) {
                    unlink($old);
                }
            }
            Message::update($id, $data);
            flash('Wiadomość została zaktualizowana.');
        } else {
            // DODAWANIE — nadawcą jest zalogowany użytkownik.
            $data['user_id'] = (int) $user['id'];
            Message::create($data);
            flash('Wiadomość została wysłana.');
        }

        redirect('index.php?page=messages');
    }

    /** POST: usunięcie wiadomości (kasuje też plik z dysku — w modelu). */
    public function delete(): void
    {
        csrf_check();
        $message = $this->findOwnedOr404((int) ($_POST['id'] ?? 0));
        Message::delete((int) $message['id']);
        flash('Wiadomość została usunięta.');
        redirect('index.php?page=messages');
    }

    /** GET: eksport bieżącej (przefiltrowanej) listy do CSV lub JSON. */
    public function export(): void
    {
        $user  = Auth::user();
        $scope = Auth::isAdmin() ? null : (int) $user['id'];
        $format = $_GET['format'] ?? 'csv';

        // Stosujemy te same filtry co na liście, ale pobieramy wszystkie strony.
        $filters = [
            'search'          => $_GET['search'] ?? '',
            'type'            => $_GET['type'] ?? '',
            'conversation_id' => $_GET['conversation_id'] ?? '',
            'important'       => $_GET['important'] ?? '',
            'sort'            => $_GET['sort'] ?? 'created_at',
            'dir'             => $_GET['dir'] ?? 'desc',
        ];
        $rows = $this->collectAll($filters, $scope);

        if ($format === 'json') {
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename="wiadomosci.json"');
            echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        // CSV (domyślnie).
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="wiadomosci.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");   // BOM — poprawne polskie znaki w Excelu
        // Argumenty ',', '"', '' jawnie (PHP 8.4+ wymaga podania parametru escape).
        fputcsv($out, ['ID', 'Rozmowa', 'Nadawca', 'Typ', 'Treść', 'Priorytet', 'Ważna', 'Data'], ',', '"', '');
        foreach ($rows as $r) {
            fputcsv($out, [$r['id'], $r['conversation_name'], $r['author'], $r['type'],
                $r['body'], $r['priority'], $r['is_important'] ? 'tak' : 'nie', $r['created_at']], ',', '"', '');
        }
        fclose($out);
        exit;
    }

    // ---------------------------------------------------------------
    //  Metody prywatne
    // ---------------------------------------------------------------

    /** Renderuje formularz dodawania/edycji z ewentualnymi błędami. */
    private function renderForm(array $message, array $errors, ?int $id): void
    {
        $user = Auth::user();
        view('messages/form', [
            'message'       => $message,
            'errors'        => $errors,
            'id'            => $id,
            'conversations' => Conversation::forUser((int) $user['id']),
            'types'         => Message::TYPES,
        ]);
    }

    /**
     * Znajduje wiadomość i sprawdza prawo dostępu (autor lub admin).
     * Inaczej kończy działanie stroną 404/403.
     */
    private function findOwnedOr404(int $id): array
    {
        $message = Message::find($id);
        if (!$message) {
            show_error(404, 'Nie znaleziono wiadomości.');
        }
        $user = Auth::user();
        if (!Auth::isAdmin() && (int) $message['user_id'] !== (int) $user['id']) {
            show_error(403, 'Możesz edytować lub usuwać tylko własne wiadomości.');
        }
        return $message;
    }

    /** Przenosi wgrany plik do katalogu uploads. Zwraca ['path'=>?, 'error'=>?]. */
    private function handleUpload(array $file): array
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['path' => null, 'error' => 'Błąd podczas przesyłania pliku.'];
        }
        if ($file['size'] > self::MAX_FILE_BYTES) {
            return ['path' => null, 'error' => 'Plik jest za duży (maksymalnie 5 MB).'];
        }

        if (!is_dir(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0777, true);
        }

        // Bezpieczna, unikalna nazwa pliku (zachowujemy rozszerzenie).
        $ext  = pathinfo($file['name'], PATHINFO_EXTENSION);
        $name = bin2hex(random_bytes(8)) . ($ext ? '.' . strtolower($ext) : '');
        $dest = UPLOAD_DIR . '/' . $name;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return ['path' => null, 'error' => 'Nie udało się zapisać pliku na serwerze.'];
        }
        return ['path' => UPLOAD_URL . '/' . $name, 'error' => null];
    }

    /** Pobiera wszystkie pasujące rekordy (do eksportu, bez paginacji). */
    private function collectAll(array $filters, ?int $scope): array
    {
        $rows = [];
        $page = 1;
        do {
            $filters['page'] = $page;
            $res = Message::paginate($filters, $scope);
            $rows = array_merge($rows, $res['rows']);
            $page++;
        } while ($page <= $res['pages']);
        return $rows;
    }
}
