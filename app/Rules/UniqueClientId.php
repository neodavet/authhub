<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueClientId implements ValidationRule
{
    /**
     * The application ID to ignore during validation (for updates)
     */
    private ?int $ignoreId;

    /**
     * Create a new rule instance.
     */
    public function __construct(?int $ignoreId = null)
    {
        $this->ignoreId = $ignoreId;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Check if value is a string
        if (!is_string($value)) {
            $fail('The :attribute must be a string.');
            return;
        }

        // Check if the value is a valid UUID format
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value)) {
            $fail('The :attribute must be a valid UUID format.');
            return;
        }

        // Check if the client_id already exists in the database
        $query = \App\Models\Application::where('client_id', $value);
        
        // If we're updating an existing application, ignore its current record
        if ($this->ignoreId) {
            $query->where('id', '!=', $this->ignoreId);
        }

        if ($query->exists()) {
            $fail('The :attribute has already been taken.');
            return;
        }

        // Additional security check: ensure it's not a system reserved ID
        $reservedIds = [
            '00000000-0000-0000-0000-000000000000',
            '11111111-1111-1111-1111-111111111111',
            'system-reserved-id',
            'test-client-id'
        ];

        if (in_array(strtolower($value), array_map('strtolower', $reservedIds))) {
            $fail('The :attribute cannot use reserved values.');
            return;
        }
    }
}
