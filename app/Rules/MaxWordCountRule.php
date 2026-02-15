<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MaxWordCountRule implements ValidationRule
{
    public function __construct(private readonly int $maxWords = 300)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || trim((string) $value) === '') {
            return;
        }

        preg_match_all("/[\p{L}\p{N}']+/u", (string) $value, $matches);
        $wordCount = count($matches[0] ?? []);

        if ($wordCount > $this->maxWords) {
            $fail("The {$attribute} field may not contain more than {$this->maxWords} words.");
        }
    }
}
