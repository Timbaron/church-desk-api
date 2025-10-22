<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RequisitionStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Assuming the user is authenticated and authorized via middleware
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        // Assuming the user is already authenticated via Laravel Sanctum or similar
        // We will infer church/section/dept from the authenticated user in the service
        return [
            'title' => ['required', 'string', 'max:255'],
            'department_id' => ['required', 'exists:departments,id'],
            'amount_requested' => ['required', 'numeric', 'min:0.01'],
            'category' => ['required', 'string', 'max:100'],
            'purpose' => ['required', 'string'],
            'date_needed' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'attachments' => ['nullable', 'array'],
            'attachments.*.name' => ['required_with:attachments', 'string'],
            'attachments.*.url' => ['required_with:attachments', 'url'],
        ];
    }
}
