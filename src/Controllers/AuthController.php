<?php
/**
 * AuthController
 * --------------
 * Rejestracja, logowanie, weryfikacja e-maila, logowanie społecznościowe
 * i wylogowanie. Łączy walidację (Validator), model User i klasę Auth.
 */
class AuthController
{
    /** GET: formularz logowania. */
    public function login(): void
    {
        view('auth/login', ['errors' => [], 'old' => []]);
    }

    /** POST: obsługa logowania e-mailem i hasłem. */
    public function doLogin(): void
    {
        csrf_check();
        $email = trim($_POST['email'] ?? '');
        $result = Auth::attempt($email, $_POST['password'] ?? '');

        if ($result['ok']) {
            flash('Zalogowano pomyślnie. Witaj ponownie!');
            redirect('index.php?page=messages');
        }
        // Błąd — wracamy do formularza z zachowanym e-mailem (bez hasła).
        view('auth/login', ['errors' => ['email' => $result['error']], 'old' => ['email' => $email]]);
    }

    /** GET: formularz rejestracji. */
    public function register(): void
    {
        view('auth/register', ['errors' => [], 'old' => []]);
    }

    /** POST: rejestracja nowego użytkownika z walidacją serwerową. */
    public function doRegister(): void
    {
        csrf_check();
        $email = trim($_POST['email'] ?? '');
        $name  = trim($_POST['name'] ?? '');
        $pass  = $_POST['password'] ?? '';

        $v = new Validator();
        $v->required('name', $name, 'Podaj nazwę wyświetlaną.')
          ->length('name', $name, 2, 50, 'Nazwa musi mieć od 2 do 50 znaków.')
          ->required('email', $email, 'Podaj adres e-mail.')
          ->email('email', $email, 'Adres e-mail jest nieprawidłowy.')
          ->required('password', $pass, 'Podaj hasło.')
          ->length('password', $pass, 6, 100, 'Hasło musi mieć co najmniej 6 znaków.');

        // Unikalność e-maila sprawdzamy dopiero, gdy format jest poprawny.
        if (User::findByEmail($email)) {
            $v->addError('email', 'Ten adres e-mail jest już zajęty.');
        }

        if (!$v->passes()) {
            view('auth/register', ['errors' => $v->errors(), 'old' => ['email' => $email, 'name' => $name]]);
            return;
        }

        $id = User::create($email, $pass, $name);
        $user = User::find($id);

        // Wysyłamy do użytkownika link aktywacyjny z unikalnym tokenem.
        // Pełny adres (z BASE_URL), aby link działał także po otwarciu w e-mailu.
        $link = BASE_URL . '/index.php?page=verify&token=' . $user['verification_token'];
        Mailer::sendVerification($email, $link);
        view('auth/verify_notice', ['email' => $email]);
    }

    /** GET: weryfikacja e-maila po kliknięciu linka aktywacyjnego. */
    public function verify(): void
    {
        $token = $_GET['token'] ?? '';
        $user = $token ? User::findByToken($token) : null;

        if (!$user) {
            show_error(404, 'Link aktywacyjny jest nieprawidłowy lub konto zostało już potwierdzone.');
        }

        User::verify((int) $user['id']);
        flash('Adres e-mail został potwierdzony. Możesz się teraz zalogować.');
        redirect('index.php?page=login');
    }

    /** Wylogowanie. */
    public function logout(): void
    {
        Auth::logout();
        redirect('index.php?page=login');
    }
}
