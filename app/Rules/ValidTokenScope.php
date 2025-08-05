<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidTokenScope implements ValidationRule
{
    /**
     * Available token scopes
     */
    private array $availableScopes = [
        'read',
        'write', 
        'delete',
        'admin',
        'user:read',
        'user:write',
        'application:read',
        'application:write',
        'token:read',
        'token:write',
        'token:delete'
    ];

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Check if value is a string
        if (!is_string($value)) {
            $fail('The :attribute must be a valid scope string.');
            return;
        }

        // Check if the scope exists in available scopes
        if (!in_array($value, $this->availableScopes)) {
            $availableScopes = implode(', ', $this->availableScopes);
            $fail("The :attribute '{$value}' is not a valid scope. Available scopes: {$availableScopes}.");
            return;
        }

        // Additional business logic: admin scope requires special permission
        if ($value === 'admin') {
            // In a real application, you might check if the current user
            // has permission to grant admin scope
            // For now, we'll allow it but this is where you'd add the check
        }

        // Check for conflicting scopes (example: can't have both read and admin for the same resource)
        if (request()->has('abilities') && is_array(request('abilities'))) {
            $allScopes = request('abilities');
            
            // If requesting admin scope, remove other conflicting scopes
            if ($value === 'admin' && in_array('read', $allScopes)) {
                // Could add logic here to automatically handle scope conflicts
            }
        }
    }
}
