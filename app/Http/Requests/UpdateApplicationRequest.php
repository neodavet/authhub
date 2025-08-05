<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateApplicationRequest extends FormRequest
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
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'callback_urls' => 'nullable|array',
            'callback_urls.*' => 'url',
            'allowed_scopes' => 'nullable|array',
            'allowed_scopes.*' => 'in:read,write,delete,admin',
            'rate_limit' => 'nullable|integer|min:1|max:10000',
            'is_active' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The application name is required.',
            'callback_urls.*.url' => 'Each callback URL must be a valid URL.',
            'allowed_scopes.*.in' => 'Invalid scope. Allowed scopes are: read, write, delete, admin.',
            'rate_limit.min' => 'Rate limit must be at least 1 request per hour.',
            'rate_limit.max' => 'Rate limit cannot exceed 10,000 requests per hour.',
        ];
    }
}
