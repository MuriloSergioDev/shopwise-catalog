<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Variant options are a map of lowercase option names ("color", "size") to values.
 */
final class OptionNames implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value)) {
            return;
        }

        foreach (array_keys($value) as $name) {
            if (! is_string($name) || preg_match('/^[a-z][a-z0-9_]{0,49}$/', $name) !== 1) {
                $fail('Option names must be lowercase words (a-z, 0-9, _), at most 50 characters.');

                return;
            }
        }
    }
}
