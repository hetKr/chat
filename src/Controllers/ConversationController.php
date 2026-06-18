<?php
/**
 * ConversationController
 * ----------------------
 * Tworzenie rozmów (indywidualnych i grupowych). Twórca rozmowy jest do niej
 * automatycznie dodawany, dzięki czemu od razu może wysyłać w niej wiadomości.
 */
class ConversationController
{
    /** GET: formularz nowej rozmowy (nazwa + wybór uczestników). */
    public function create(): void
    {
        $user = Auth::user();
        view('conversations/form', [
            'errors' => [],
            'old'    => [],
            // Do rozmowy można dodać każdego istniejącego użytkownika (poza sobą).
            'people' => User::allExcept((int) $user['id']),
        ]);
    }

    /** POST: zapis nowej rozmowy z walidacją serwerową. */
    public function save(): void
    {
        csrf_check();
        $user    = Auth::user();
        $name    = trim($_POST['name'] ?? '');
        $isGroup = isset($_POST['is_group']);
        $members = $_POST['members'] ?? [];

        $v = new Validator();
        $v->required('name', $name, 'Podaj nazwę rozmowy.')
          ->length('name', $name, 3, 60, 'Nazwa musi mieć od 3 do 60 znaków.');

        if (!$v->passes()) {
            view('conversations/form', [
                'errors' => $v->errors(),
                'old'    => ['name' => $name, 'is_group' => $isGroup],
                'people' => User::allExcept((int) $user['id']),
            ]);
            return;
        }

        // Utwórz rozmowę i dodaj twórcę oraz wybranych uczestników.
        $conversationId = Conversation::create($name, $isGroup);
        Conversation::addMember($conversationId, (int) $user['id']);
        foreach ((array) $members as $memberId) {
            Conversation::addMember($conversationId, (int) $memberId);
        }

        flash('Utworzono rozmowę „' . $name . '".');
        redirect('index.php?page=messages');
    }
}
