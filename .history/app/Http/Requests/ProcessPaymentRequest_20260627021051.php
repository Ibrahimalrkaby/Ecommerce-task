<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProcessPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Add your auth logic here (e.g., $this->user() !== null)
    }

    public function rules(): array
    {
        return [
            'order_id'       => ['required', 'integer', 'exists:orders,id'],
            'payment_method' => ['required', 'string', 'in:credit_card,paypal,stripe,cash'],
            'amount'         => ['nullable', 'numeric', 'min:0.01'],
            'currency'       => ['nullable', 'string', 'size:3'],
            'email'          => ['nullable', 'email'],   // For PayPal
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required'       => 'Order ID is required.',
            'order_id.exists'         => 'The specified order does not exist.',
            'payment_method.required' => 'Payment method is required.',
            'payment_method.in'       => 'Invalid payment method. Allowed: credit_card, paypal, stripe, cash.',
            'amount.numeric'          => 'Amount must be a valid number.',
            'amount.min'              => 'Amount must be greater than 0.',
        ];
    }
}
