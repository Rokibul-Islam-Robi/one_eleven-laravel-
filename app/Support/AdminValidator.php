<?php

namespace App\Support;

use DateTime;

/** Port of the original admin Validator (admin/includes/helpers.php). */
class AdminValidator
{
    public array $errors = [];
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function required(string $field, string $label): self
    {
        if ($this->str($field) === '') {
            $this->errors[$field] = $label . ' is required.';
        }
        return $this;
    }

    public function email(string $field, string $label, bool $allowEmpty = true): self
    {
        $v = $this->str($field);
        if ($v === '' && $allowEmpty) { return $this; }
        if ($v === '' || !filter_var($v, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = $label . ' must be a valid email address.';
        }
        return $this;
    }

    public function phone(string $field, string $label, bool $allowEmpty = true): self
    {
        $v = $this->str($field);
        if ($v === '' && $allowEmpty) { return $this; }
        if (!preg_match('/^[0-9+\-\s()]{6,20}$/', $v)) {
            $this->errors[$field] = $label . ' must be a valid phone number.';
        }
        return $this;
    }

    public function max(string $field, int $len, string $label): self
    {
        $v = $this->str($field);
        if ($v !== '' && mb_strlen($v) > $len) {
            $this->errors[$field] = $label . " must be $len characters or fewer.";
        }
        return $this;
    }

    public function numeric(string $field, string $label, bool $allowEmpty = true, float $min = 0): self
    {
        $v = $this->str($field);
        if ($v === '' && $allowEmpty) { return $this; }
        if ($v === '' || !is_numeric($v) || (float)$v < $min) {
            $this->errors[$field] = $label . ' must be a number' . ($min > 0 ? ' of at least ' . $min : '') . '.';
        }
        return $this;
    }

    public function in(string $field, array $allowed, string $label): self
    {
        $v = $this->str($field);
        if ($v !== '' && !in_array($v, $allowed, true)) {
            $this->errors[$field] = $label . ' has an invalid value.';
        }
        return $this;
    }

    public function date(string $field, string $label, bool $allowEmpty = true): self
    {
        $v = $this->str($field);
        if ($v === '' && $allowEmpty) { return $this; }
        $d = DateTime::createFromFormat('Y-m-d', $v);
        if (!$d || $d->format('Y-m-d') !== $v) {
            $this->errors[$field] = $label . ' must be a valid date (YYYY-MM-DD).';
        }
        return $this;
    }

    public function str(string $field): string
    {
        return trim((string)($this->data[$field] ?? ''));
    }

    public function val(string $field, $default = null)
    {
        $v = $this->data[$field] ?? $default;
        return is_string($v) ? trim($v) : $v;
    }

    public function int(string $field, int $default = 0): int
    {
        return (int)($this->data[$field] ?? $default);
    }

    public function float(string $field, float $default = 0.0): float
    {
        return (float)($this->data[$field] ?? $default);
    }

    public function bool(string $field): bool
    {
        return !empty($this->data[$field]);
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }
}
