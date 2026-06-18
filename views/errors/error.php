<?php /** Widok: strona błędu. Zmienne: $code, $message. */ ?>
<div class="card card-narrow error-page">
    <h1><?= (int) $code ?></h1>
    <p><?= e($message) ?></p>
    <a class="btn btn-primary" href="index.php?page=messages">Wróć na stronę główną</a>
</div>
