<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WorkflowActionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization logic (checking user role) should be handled in the controller or middleware.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'action' => [
                'required',
                'string',
                'in:APPROVE,REJECT,REQUEST_CHANGES'
            ],
            'comments' => [
                'nullable',
                'string',
                'max:500'
            ],
        ];
    }
}
