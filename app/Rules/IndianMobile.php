<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Propaganistas\LaravelPhone\PhoneNumber;

/**
 * Validates that a value is a genuinely valid Indian mobile number, using
 * Google's libphonenumber (via propaganistas/laravel-phone) - not just "10
 * digits". Rejects invalid prefixes, landlines, and non-Indian numbers.
 *
 * The app stores phone numbers as bare 10-digit national strings (used as a
 * key across users/wallets), so this validates the national number against
 * region IN and does NOT reformat the value.
 */
class IndianMobile implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            $number = new PhoneNumber((string) $value, 'IN');

            if (! $number->isValid() || ! $number->isOfCountry('IN') || ! $number->isOfType('mobile')) {
                $fail('Enter a valid Indian mobile number.');
            }
        } catch (\Throwable) {
            $fail('Enter a valid Indian mobile number.');
        }
    }
}
