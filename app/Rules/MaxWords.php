<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MaxWords implements ValidationRule
{
    public function __construct(private readonly int $max, private readonly int $min = 0) {}

    public static function count(?string $text): int
    {
        $plain = trim(preg_replace('/\s+/u', ' ', strip_tags(html_entity_decode((string) $text))));

        return $plain === '' ? 0 : count(explode(' ', $plain));
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $n = self::count($value);

        if ($n > $this->max) {
            $fail("Please keep this to {$this->max} words or fewer (currently {$n}).");
        } elseif ($this->min && $n < $this->min) {
            $fail("This needs at least {$this->min} words (currently {$n}).");
        }
    }
}
