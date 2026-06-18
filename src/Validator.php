<?php
/**
 * Validator
 * ---------
 * Walidacja danych po stronie serwera. Zbiera błędy do tablicy
 * (klucz = nazwa pola, wartość = komunikat). Każda metoda sprawdza
 * jedną regułę i sama zapisuje błąd, jeśli reguła nie jest spełniona.
 *
 * Sposób użycia:
 *   $v = new Validator();
 *   $v->required('body', $body, 'Treść jest wymagana.');
 *   if ($v->passes()) { ... } else { $errors = $v->errors(); }
 */
class Validator
{
    /** Tablica błędów: ['pole' => 'komunikat']. */
    private array $errors = [];

    /** Pole nie może być puste. */
    public function required(string $field, ?string $value, string $message): self
    {
        if (trim((string) $value) === '') {
            $this->addError($field, $message);
        }
        return $this;
    }

    /** Minimalna i maksymalna długość tekstu. */
    public function length(string $field, ?string $value, int $min, int $max, string $message): self
    {
        $len = mb_strlen(trim((string) $value));
        if ($len < $min || $len > $max) {
            $this->addError($field, $message);
        }
        return $this;
    }

    /** Poprawny adres e-mail. */
    public function email(string $field, ?string $value, string $message): self
    {
        if (!filter_var((string) $value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, $message);
        }
        return $this;
    }

    /** Wartość musi należeć do dozwolonego zbioru (np. lista typów). */
    public function inList(string $field, ?string $value, array $allowed, string $message): self
    {
        if (!in_array($value, $allowed, true)) {
            $this->addError($field, $message);
        }
        return $this;
    }

    /** Liczba całkowita w zadanym zakresie. */
    public function intRange(string $field, $value, int $min, int $max, string $message): self
    {
        if (!is_numeric($value) || (int) $value < $min || (int) $value > $max) {
            $this->addError($field, $message);
        }
        return $this;
    }

    /** Poprawny adres URL (używane przy wiadomościach typu "link"). */
    public function url(string $field, ?string $value, string $message): self
    {
        if (!filter_var((string) $value, FILTER_VALIDATE_URL)) {
            $this->addError($field, $message);
        }
        return $this;
    }

    /** Ręczne dodanie błędu (np. po sprawdzeniu unikalności e-maila w bazie). */
    public function addError(string $field, string $message): self
    {
        // Zapamiętujemy tylko pierwszy błąd dla danego pola.
        $this->errors[$field] ??= $message;
        return $this;
    }

    /** Czy walidacja przeszła bez błędów. */
    public function passes(): bool
    {
        return $this->errors === [];
    }

    /** Zwraca wszystkie zebrane błędy. */
    public function errors(): array
    {
        return $this->errors;
    }
}
