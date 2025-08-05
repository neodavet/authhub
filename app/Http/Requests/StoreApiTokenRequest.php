<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreApiTokenRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'abilities' => 'required|array|min:1',
            'abilities.*' => 'string|in:read,write,delete,admin,user:read,user:write,application:read,application:write,token:read,token:write,token:delete',
            'expires_at' => 'nullable|date|after:now',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The token name is required.',
            'abilities.required' => 'At least one ability must be specified.',
            'abilities.min' => 'At least one ability must be specified.',
            'abilities.*.in' => 'Invalid ability. Available abilities: read, write, delete, admin, user:read, user:write, application:read, application:write, token:read, token:write, token:delete.',
            'expires_at.after' => 'The expiration date must be in the future.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Get the application from route parameter
            $application = $this->route('application');
            
            if ($application && $this->has('abilities')) {
                $requestedAbilities = $this->input('abilities', []);
                $allowedScopes = $application->allowed_scopes ?? [];
                
                // Check if all requested abilities are allowed by the application
                $unauthorizedAbilities = array_diff($requestedAbilities, $allowedScopes);
                
                if (!empty($unauthorizedAbilities)) {
                    $validator->errors()->add('abilities', 
                        'The following abilities are not allowed for this application: ' . 
                        implode(', ', $unauthorizedAbilities)
                    );
                }
            }
        });
    }
}
