<?php
/**
 * ContactController
 * -----------------
 * Zarządzanie listą kontaktów zalogowanego użytkownika.
 */
class ContactController
{
    /** GET: lista kontaktów + lista osób do dodania. */
    public function index(): void
    {
        $user = Auth::user();
        view('contacts/index', [
            'contacts' => Contact::forUser((int) $user['id']),
            'addable'  => Contact::addable((int) $user['id']),
        ]);
    }

    /** POST: dodanie kontaktu. */
    public function add(): void
    {
        csrf_check();
        $user = Auth::user();
        $contactId = (int) ($_POST['contact_id'] ?? 0);

        if ($contactId > 0 && User::find($contactId)) {
            Contact::add((int) $user['id'], $contactId);
            flash('Dodano kontakt do listy.');
        } else {
            flash('Nie udało się dodać kontaktu.', 'error');
        }
        redirect('index.php?page=contacts');
    }

    /** POST: usunięcie kontaktu. */
    public function delete(): void
    {
        csrf_check();
        $user = Auth::user();
        Contact::delete((int) ($_POST['id'] ?? 0), (int) $user['id']);
        flash('Usunięto kontakt z listy.');
        redirect('index.php?page=contacts');
    }
}
