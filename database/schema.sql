-- ============================================================
--  SCHEMAT BAZY DANYCH (SQLite) — aplikacja chatowa
-- ============================================================
--  5 tabel powiązanych kluczami obcymi.
--  Relacja many-to-many: users <-> conversations
--  poprzez dedykowaną tabelę łączącą conversation_user.
-- ============================================================

-- Użytkownicy aplikacji (role: admin / user)
CREATE TABLE users (
    id                 INTEGER PRIMARY KEY AUTOINCREMENT,
    email              TEXT    NOT NULL UNIQUE,
    password_hash      TEXT    NOT NULL,
    display_name       TEXT    NOT NULL,
    role               TEXT    NOT NULL DEFAULT 'user',      -- 'admin' lub 'user'
    is_verified        INTEGER NOT NULL DEFAULT 0,           -- 0 = niezweryfikowany, 1 = e-mail potwierdzony
    verification_token TEXT,                                 -- token z linka aktywacyjnego
    theme              TEXT    NOT NULL DEFAULT 'light',     -- preferencja: 'light' / 'dark'
    is_online          INTEGER NOT NULL DEFAULT 0,           -- status online
    created_at         TEXT    NOT NULL DEFAULT (datetime('now'))
);

-- Rozmowy (indywidualne lub grupowe)
CREATE TABLE conversations (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    name       TEXT    NOT NULL,
    is_group   INTEGER NOT NULL DEFAULT 0,                   -- 0 = rozmowa 1:1, 1 = grupa
    created_at TEXT    NOT NULL DEFAULT (datetime('now'))
);

-- TABELA ŁĄCZĄCA (many-to-many): którzy użytkownicy należą do których rozmów
CREATE TABLE conversation_user (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    conversation_id INTEGER NOT NULL,
    user_id         INTEGER NOT NULL,
    created_at      TEXT    NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)         REFERENCES users(id)         ON DELETE CASCADE,
    UNIQUE (conversation_id, user_id)
);

-- GŁÓWNY ZASÓB: wiadomości
CREATE TABLE messages (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    conversation_id INTEGER NOT NULL,
    user_id         INTEGER NOT NULL,                        -- nadawca
    type            TEXT    NOT NULL DEFAULT 'tekst',        -- tekst/obraz/film/plik/link
    body            TEXT,                                    -- treść wiadomości
    link_url        TEXT,                                    -- adres URL (dla typu 'link')
    file_path       TEXT,                                    -- ścieżka do wgranego pliku
    priority        INTEGER NOT NULL DEFAULT 1,              -- priorytet 1-5
    is_important    INTEGER NOT NULL DEFAULT 0,              -- oznaczenie "ważne"
    created_at      TEXT    NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)         REFERENCES users(id)         ON DELETE CASCADE
);

-- Kontakty użytkownika (kto kogo ma na liście kontaktów)
CREATE TABLE contacts (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id    INTEGER NOT NULL,
    contact_id INTEGER NOT NULL,
    created_at TEXT    NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (user_id)    REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (contact_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE (user_id, contact_id)
);
