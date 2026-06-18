<?php /** Widok: informacja o weryfikacji e-maila. Zmienna: $email. */ ?>
<div class="card card-narrow">
    <h1>Potwierdź adres e-mail</h1>
    <p>Na adres <strong><?= e($email) ?></strong> wysłaliśmy wiadomość z linkiem aktywacyjnym.</p>
    <p class="muted">Otwórz swoją skrzynkę e-mail i kliknij w link, aby dokończyć rejestrację.
        Dopiero po aktywacji konta możliwe jest zalogowanie.</p>
    <p><a class="btn btn-ghost" href="index.php?page=login">Przejdź do logowania</a></p>
</div>
