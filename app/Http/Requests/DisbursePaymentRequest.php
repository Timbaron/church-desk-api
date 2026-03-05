<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\PaymentMethod;

class DisbursePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization logic (checking Finance/SuperAdmin role) is handled in the controller.
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
            // Corresponds to the 'paymentDetails' object in the request body
            'paymentDetails' => ['required', 'array'],
            'paymentDetails.amount_paid' => ['required', 'numeric', 'min:0.01'],
            'paymentDetails.payment_method' => [
                'required',
                'string',
                // Validates against the PaymentMethod Enum
                'in:' . implode(',', array_column(PaymentMethod::cases(), 'value'))
            ],
            'paymentDetails.payment_date' => [
                'required',
                'date_format:Y-m-d'
            ],
            'paymentDetails.reference_number' => [
                'nullable',
                'string',
                'max:255'
            ],
            // Mock validation for potential file attachment object
            'paymentDetails.proof_file' => ['nullable', 'array'],
            'paymentDetails.proof_file.name' => ['nullable', 'string'],
            'paymentDetails.proof_file.url' => ['nullable', 'string'],
        ];
    }
}
