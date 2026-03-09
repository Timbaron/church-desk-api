<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\PaymentMethod;

class DisbursePaymentRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $details = $this->input('paymentDetails', []);

        if (!is_array($details)) {
            return;
        }

        $mapped = [
            'amount_paid' => $details['amount_paid'] ?? $details['amountPaid'] ?? null,
            'payment_method' => $details['payment_method'] ?? $details['paymentMethod'] ?? null,
            'payment_date' => $details['payment_date'] ?? $details['paymentDate'] ?? null,
            'reference_number' => $details['reference_number'] ?? $details['referenceNumber'] ?? null,
            'proof_file' => $details['proof_file'] ?? $details['proofFile'] ?? null,
        ];

        $this->merge([
            'paymentDetails' => array_merge($details, $mapped),
        ]);
    }

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
            'paymentDetails' => ['required', 'array'],
            'paymentDetails.amount_paid' => ['required', 'numeric', 'min:0.01'],
            'paymentDetails.payment_method' => [
                'required',
                'string',
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
            'paymentDetails.proof_file' => ['nullable', 'array'],
            'paymentDetails.proof_file.name' => ['nullable', 'string'],
            'paymentDetails.proof_file.url' => ['nullable', 'string'],
        ];
    }
}
