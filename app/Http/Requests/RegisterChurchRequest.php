<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterChurchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'churchName' => ['required', 'string', 'max:255', 'unique:churches,name'],
            'adminName' => ['required', 'string', 'max:255'],
            'adminEmail' => ['required', 'email', 'unique:users,email'],
            'adminPassword' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
