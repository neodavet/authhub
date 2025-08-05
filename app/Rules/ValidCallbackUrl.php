<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidCallbackUrl implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Check if the URL is valid
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            $fail('The :attribute must be a valid URL.');
            return;
        }

        // Parse the URL
        $parsedUrl = parse_url($value);
        
        // Check if the scheme is http or https
        if (!in_array($parsedUrl['scheme'] ?? '', ['http', 'https'])) {
            $fail('The :attribute must use http or https protocol.');
            return;
        }

        // Block localhost, 127.0.0.1, and private IPs for production security
        $host = $parsedUrl['host'] ?? '';
        $blockedHosts = ['localhost', '127.0.0.1', '0.0.0.0'];
        
        if (in_array($host, $blockedHosts)) {
            $fail('The :attribute cannot use localhost or private IP addresses.');
            return;
        }

        // Block private IP ranges (optional, for production security)
        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false && filter_var($host, FILTER_VALIDATE_IP)) {
            $fail('The :attribute cannot use private or reserved IP addresses.');
            return;
        }

        // Ensure the URL doesn't contain fragments (for security)
        if (isset($parsedUrl['fragment'])) {
            $fail('The :attribute cannot contain URL fragments.');
            return;
        }
    }
}
