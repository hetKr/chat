# Aplikacja chatowa — dokumentacja projektu

Projekt zaliczeniowy z PHP (PJATK). Prosty komunikator: użytkownicy logują się,
prowadzą rozmowy i wysyłają wiadomości różnych typów (tekst, obraz, film, plik, link).
Aplikacja napisana jest **obiektowo**, w architekturze **MVC** (Model–Widok–Kontroler),
korzysta z bazy **SQLite** przez **PDO**.

---

## 1. Jak uruchomić

W terminalu, w katalogu projektu:

```bash
php setup.php          # tworzy bazę data/chat.sqlite i wypełnia ją przykładowymi danymi
php -S localhost:8000  # uruchamia wbudowany serwer PHP
```

> `setup.php` nie nadpisze istniejącej bazy — chroni Twoje konta i wiadomości.
> Aby celowo wyczyścić wszystko i zacząć od zera, uruchom `php setup.php --reset`.

Następnie otwórz w przeglądarce: **http://localhost:8000**

> **Ważne:** projekt używa funkcji `mb_*` (obsługa polskich znaków UTF-8), więc
> w `php.ini` musi być włączone rozszerzenie **mbstring** (linia `extension=mbstring`).

### Konta testowe

| Rola        | E-mail          | Hasło     |
|-------------|-----------------|-----------|
| Administrator | `admin@chat.pl` | `admin123` |
| Użytkownik    | `ania@chat.pl`  | `haslo123` |
| Użytkownik    | `jan@chat.pl`   | `haslo123` |

---

## 2. Architektura — wzorzec MVC

Aplikacja dzieli się na trzy warstwy. To jest sedno wymogu „oddzielenie logiki od prezentacji”.

- **Model** (`src/Models/`) — klasy operujące na bazie danych. Tu i tylko tu jest SQL.
- **Widok** (`views/`) — pliki HTML z PHP. Tylko wyświetlają dane. Brak SQL i logiki biznesowej.
- **Kontroler** (`src/Controllers/`) — „spina” model z widokiem: bierze dane z żądania,
  woła model, przekazuje wynik do widoku.

### Przepływ jednego żądania

```
Przeglądarka  →  index.php (router)  →  Kontroler  →  Model  →  baza danych
                                            │
                                            ▼
                                          Widok  →  HTML  →  Przeglądarka
```

Przykład: wejście na `index.php?page=messages`
1. `index.php` sprawdza w mapie tras, że `messages` obsługuje `MessageController::index()`.
2. Sprawdza, czy użytkownik jest zalogowany (`Auth::requireLogin()`).
3. `MessageController::index()` pobiera filtry z `$_GET`, woła `Message::paginate(...)`.
4. Wynik trafia do widoku `views/messages/index.php` przez funkcję `view()`.
5. Widok renderuje tabelę wewnątrz wspólnego `views/layout.php`.

---

## 3. Struktura katalogów

```
chat/
├── index.php              # FRONT CONTROLLER — jedyny punkt wejścia, router (mapa tras)
├── config.php             # ustawienia, autoloader klas, funkcje pomocnicze, start sesji
├── setup.php              # instalator: tworzy bazę i przykładowe dane (uruchamiany raz)
├── DOKUMENTACJA.md        # ten plik
│
├── database/
│   └── schema.sql         # definicje tabel (CREATE TABLE)
├── data/
│   └── chat.sqlite        # plik bazy danych (tworzony przez setup.php)
├── uploads/               # wgrane pliki (załączniki wiadomości)
│
├── src/                   # WARSTWA LOGIKI (klasy)
│   ├── Database.php        # połączenie z bazą (PDO)
│   ├── Auth.php            # logowanie, sesja, role, kontrola dostępu
│   ├── Validator.php       # walidacja danych po stronie serwera
│   ├── Mailer.php          # wysyłanie e-maili (link aktywacyjny)
│   ├── Models/
│   │   ├── User.php         # użytkownicy
│   │   ├── Message.php      # wiadomości (GŁÓWNY ZASÓB) — CRUD, filtry, paginacja
│   │   ├── Conversation.php # rozmowy + relacja many-to-many
│   │   └── Contact.php      # kontakty
│   └── Controllers/
│       ├── AuthController.php          # rejestracja, logowanie, weryfikacja, logowanie zewnętrzne
│       ├── MessageController.php       # CRUD wiadomości, eksport
│       ├── ConversationController.php  # tworzenie rozmów
│       ├── ContactController.php       # kontakty
│       ├── AdminController.php         # panel administratora
│       └── SettingsController.php      # preferencje (motyw)
│
├── views/                 # WARSTWA PREZENTACJI (szablony HTML)
│   ├── layout.php          # wspólna ramka (nagłówek, menu, stopka)
│   ├── auth/               # login, register, verify_notice
│   ├── messages/           # index (tabela), form (dodawanie/edycja)
│   ├── contacts/           # lista kontaktów
│   ├── admin/              # dashboard, users
│   └── errors/             # error (404/403/500)
│
└── public/css/style.css   # własne style (motyw jasny/ciemny)
```

---

## 4. Opis klas (logika obiektowa)

Każda klasa ma jedną odpowiedzialność. Metody publiczne to interfejs „na zewnątrz”,
metody prywatne to szczegóły schowane wewnątrz.

### `Database` (src/Database.php)
Jedyne miejsce łączące się z bazą. Wzorzec **singleton** — połączenie tworzone raz.
- `pdo()` — zwraca obiekt PDO (tworzy go przy pierwszym użyciu).
- `run($sql, $params)` — wykonuje zapytanie przygotowane (ochrona przed SQL injection).
- `connect()` *(prywatna)* — tworzy połączenie, włącza wyjątki i klucze obce.

### `Validator` (src/Validator.php)
Walidacja po stronie serwera. Zbiera błędy do tablicy `['pole' => 'komunikat']`.
- `required`, `length`, `email`, `inList`, `intRange`, `url` — reguły sprawdzające.
- `passes()` — czy brak błędów; `errors()` — zwraca błędy.

### `Auth` (src/Auth.php)
Logowanie i kontrola dostępu. Stan zalogowania trzymany w sesji (tylko ID).
- `attempt($email, $pass)` — sprawdza hasło (`password_verify`) i weryfikację konta.
- `login`, `logout`, `check`, `user`, `isAdmin`.
- `requireLogin()`, `requireAdmin()` — strażnicy dostępu (wołane w routerze).

### `User` (model)
Operacje na tabeli `users`. Hasła zawsze jako **hash** (`password_hash`, bcrypt).
- `create`, `findByEmail`, `find`, `findByToken`, `verify`, `setTheme`,
  `setRole`, `delete`, `all`, `stats`.

### `Message` (model) — GŁÓWNY ZASÓB
- `find`, `create`, `update`, `delete` — pełny CRUD.
  `delete()` kasuje też **plik z dysku**.
- `paginate($filters, $scope)` — lista z wyszukiwaniem, 3 filtrami, sortowaniem i paginacją.
- `aggregate($scope)` — dane do panelu podsumowania.
- `buildFilters`, `bind` *(prywatne)* — budują warunki SQL i parametry.

### `Conversation` (model)
Rozmowy i relacja **many-to-many** z użytkownikami (tabela `conversation_user`).
- `create`, `addMember`, `forUser`, `idsForUser`.

### `Mailer` (src/Mailer.php)
Wysyłanie e-maili. `sendVerification()` wysyła link aktywacyjny przez serwer SMTP
(klasa `Smtp`) i zapisuje kopię w `data/mail_outbox.log`.

### `Smtp` (src/Smtp.php)
Własny klient SMTP (bez bibliotek zewnętrznych). Łączy się z serwerem poczty,
włącza szyfrowanie STARTTLS, loguje się (`AUTH LOGIN`) i wysyła wiadomość.
Dane serwera są w stałej `MAIL` w `config.php`.

> **Konfiguracja poczty:** w `config.php`, w stałej `MAIL`, uzupełnij `user` i `pass`
> danymi SMTP z konta **Mailtrap** (panel → Inbox → SMTP Settings → Show Credentials).
> Maile pojawią się w skrzynce Mailtrap, a ich kopie w `data/mail_outbox.log`.

### `Contact` (model)
Lista kontaktów: `forUser`, `add`, `delete`, `addable`.

---

## 5. Baza danych

Plik SQLite: `data/chat.sqlite`. Dostęp wyłącznie przez **PDO** (obiektowo).
Schemat: `database/schema.sql`. **5 tabel** powiązanych kluczami obcymi.

```
users ─────┐
  │ (1:N)   │ (M:N przez conversation_user)
  ▼         ▼
messages   conversations
              ▲
              │ (M:N)
        conversation_user  ← TABELA ŁĄCZĄCA (many-to-many)

contacts: users ←→ users (kto kogo ma w kontaktach)
```

- **users** — konta (e-mail, hash hasła, rola, weryfikacja, motyw, status online).
- **conversations** — rozmowy (indywidualne / grupowe).
- **conversation_user** — *tabela łącząca* relacji **many-to-many**: który użytkownik
  należy do której rozmowy. Klucze obce do `users` i `conversations`, `ON DELETE CASCADE`.
- **messages** — wiadomości (klucze obce do `conversations` i `users`).
- **contacts** — kontakty (dwa klucze obce do `users`).

Każda tabela ma kolumnę `created_at` z **automatyczną datą utworzenia**
(`DEFAULT (datetime('now'))`).

---

## 6. Gdzie spełnione jest każde wymaganie (ściąga na obronę)

### Wymagania podstawowe

| Wymaganie | Gdzie w kodzie |
|-----------|----------------|
| **Kod obiektowy** (min. 3 klasy) | `src/` — 9 klas: Database, Auth, Validator, User, Message, Conversation, Contact + kontrolery |
| **Formularz z 4 typami pól + walidacja** | `views/messages/form.php` (select, radio, textarea, number, checkbox, file) + `MessageController::save()` woła `Validator` |
| **Zachowanie wartości po błędzie** | `MessageController::save()` przy błędzie renderuje formularz z `$_POST`; widok wstawia stare wartości |
| **Tabela rekordów** | `views/messages/index.php` — `<table>` z nagłówkami i akcjami Edytuj/Usuń |
| **Oddzielenie logiki od prezentacji** | Modele/kontrolery (`src/`) vs widoki (`views/`) — w widokach brak SQL |
| **Estetyczny wygląd** | `public/css/style.css` — własne style, zmienne CSS, motyw jasny/ciemny |
| **Pełny CRUD** | `MessageController`: create, index, edit, save, delete |

### Wymagania specyficzne

| Wymaganie | Gdzie w kodzie |
|-----------|----------------|
| **Wiadomości tekst/obraz/film/plik/link** | `Message::TYPES`, formularz + upload w `MessageController::handleUpload()` |
| **Rozmowy indywidualne i grupowe** | `ConversationController` + `Conversation` (tworzenie, dodawanie uczestników) |
| **Zarządzanie użytkownikami i kontaktami** | `AdminController` (użytkownicy), `ContactController` (kontakty) |
| **Logowanie e-mail + zewnętrzne** | `AuthController::doLogin()` oraz `AuthController::social()` (Google/Facebook) |
| **Rejestracja + weryfikacja e-mail** | `AuthController::doRegister()` → `Mailer::sendVerification()` → token → `verify()` |
| **Baza relacyjna przez PDO** | `Database` (PDO), `schema.sql` |
| **Min. 3 tabele + klucze obce + M:N** | 5 tabel; `conversation_user` to tabela łącząca M:N |
| **Upload plików + kasowanie z dysku** | `handleUpload()` + `Message::delete()` (`unlink`) |
| **Automatyczna data utworzenia** | kolumny `created_at DEFAULT (datetime('now'))` |
| **Hash haseł** | `password_hash` w `User::create`, `password_verify` w `Auth::attempt` |
| **Brak dostępu bez logowania** | `Auth::requireLogin()` w routerze (`index.php`) |
| **Min. 2 role** | `admin` / `user`; `Auth::requireAdmin()` |
| **Preferencja zapamiętana** | motyw w kolumnie `users.theme`, `SettingsController::toggleTheme()` |
| **Wyszukiwanie + 3 filtry** | `Message::buildFilters()` — search, typ, rozmowa, ważne |
| **Paginacja + sortowanie serwerowe** | `Message::paginate()` — `LIMIT/OFFSET`, `ORDER BY` z białej listy |
| **Eksport CSV/JSON** | `MessageController::export()` |
| **Panel podsumowania** | `Message::aggregate()` + `views/messages/index.php` (sekcja „summary”) |
| **Jednorazowy komunikat (flash)** | funkcje `flash()` / `take_flash()` w `config.php` |
| **Brak ponownego wysłania (PRG)** | po zapisie `redirect()` (kod 303) zamiast wyświetlenia |
| **Strony błędów** | `show_error()` + `views/errors/error.php` (404/403/500) |

---

## 7. Najważniejsze mechanizmy — jak działają

### Front controller i routing (`index.php`)
Wszystkie żądania trafiają do `index.php`. Tablica `$routes` mapuje `?page=...`
na `[Kontroler, metoda, poziom dostępu]`. Router sprawdza dostęp i woła metodę.
Dzięki temu kontrola logowania jest w **jednym miejscu**.

### Walidacja + zachowanie wartości
W `MessageController::save()` tworzymy `Validator`, dodajemy reguły. Jeśli `passes()`
zwróci `false`, ponownie renderujemy formularz, przekazując **dane z `$_POST`** jako
`$message` oraz `$errors`. Widok wstawia te wartości w pola (`value="..."`), więc
użytkownik nie traci tego, co wpisał. Hasła nigdy nie są zwracane.

### PRG (Post–Redirect–Get)
Po udanym zapisie kontroler woła `redirect()` (HTTP 303). Przeglądarka robi nowy `GET`,
więc odświeżenie strony **nie wysyła formularza ponownie**.

### Hash haseł i sesje
Rejestracja: `password_hash($pass, PASSWORD_BCRYPT)`. Logowanie: `password_verify()`.
W sesji trzymamy tylko `user_id`; dane konta dociągamy z bazy. Przy logowaniu
`session_regenerate_id()` chroni przed przejęciem sesji.

### Ochrona CSRF
Każdy formularz ma ukryte pole z tokenem (`csrf_field()`). Akcje POST sprawdzają go
(`csrf_check()`), porównując bezpiecznie (`hash_equals`).

### Filtrowanie, sortowanie, paginacja (po stronie SQL)
`Message::buildFilters()` buduje `WHERE` z parametrami (zapytania przygotowane).
Sortowanie używa **białej listy kolumn** (`SORTABLE`) — zabezpieczenie przed SQL injection.
Paginacja to `LIMIT (stała) OFFSET (numer_strony - 1) * PER_PAGE`.

### Upload i kasowanie pliku
`handleUpload()` sprawdza rozmiar, nadaje bezpieczną unikalną nazwę i przenosi plik
do `uploads/`. `Message::delete()` przed usunięciem rekordu kasuje plik (`unlink`).

### Obsługa błędów
Router opakowuje wywołanie kontrolera w `try/catch`. Nieoczekiwany wyjątek → strona 500.
Brak zasobu → `show_error(404)`. Brak uprawnień → `show_error(403)`.

### Pola zależne od typu wiadomości (czysty CSS)
W formularzu wiadomości wybór typu pokazuje właściwe pole: „tekst" → treść,
„link" → adres URL, „obraz/film/plik" → załącznik. Zrobione **bez JavaScriptu** —
radia i pola są rodzeństwem w `<div class="type-fields">`, a CSS używa selektora
`#type-link:checked ~ .field-link { display: block; }`. Zaznaczony typ (atrybut
`checked`) decyduje, które pole jest widoczne — działa też przy edycji.

---

## 8. Przykładowe pytania na obronie (i odpowiedzi)

**Czym jest wzorzec MVC i gdzie go widać?**
Podział na Model (dane/SQL — `src/Models`), Widok (HTML — `views`) i Kontroler
(`src/Controllers`, łączy oba). Widoki nie zawierają SQL.

**Jak realizujesz relację many-to-many?**
Tabela łącząca `conversation_user` z dwoma kluczami obcymi (do `users` i `conversations`).
Jeden użytkownik należy do wielu rozmów, jedna rozmowa ma wielu użytkowników.

**Jak chronisz przed SQL injection?**
Wyłącznie zapytania przygotowane PDO z parametrami (`:nazwa`). Przy sortowaniu —
biała lista dozwolonych kolumn.

**Jak przechowujesz hasła?**
Jako hash bcrypt (`password_hash`). Nigdy w postaci jawnej. Sprawdzanie: `password_verify`.

**Co się dzieje po wysłaniu formularza?**
Walidacja serwerowa. Błąd → powrót formularza z zachowanymi wartościami. Sukces →
zapis + przekierowanie (PRG), żeby odświeżenie nie wysłało danych ponownie.

**Jak działa weryfikacja e-maila?**
Przy rejestracji generujemy losowy token i zapisujemy konto jako niezweryfikowane.
`Mailer` wysyła e-mail z linkiem aktywacyjnym (funkcja `mail()`), a kopię zapisuje
w `data/mail_outbox.log`. Kliknięcie linka z tokenem ustawia `is_verified = 1`.
Bez tego logowanie jest blokowane.

**Jak działa logowanie zewnętrzne (Google/Facebook)?**
Dostawca potwierdza tożsamość i zwraca dane konta (e-mail, nazwę). Na ich podstawie
logujemy istniejące konto lub zakładamy nowe z `auth_provider` ustawionym na dostawcę.
Takie konto jest od razu zweryfikowane (tożsamość potwierdza dostawca).

**Czym różnią się role?**
`user` widzi tylko swoje rozmowy i wiadomości. `admin` ma dostęp do panelu, widzi
wszystkie wiadomości oraz może zarządzać użytkownikami. Sprawdzane przez `Auth::requireAdmin()`.

**Jak zapamiętujesz preferencję użytkownika?**
Motyw (jasny/ciemny) zapisany w kolumnie `users.theme`, więc jest pamiętany między
wizytami (po ponownym zalogowaniu).
