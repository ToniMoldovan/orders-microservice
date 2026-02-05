<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'order_id' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3', 'alpha'], // alphanumeric and 3 characters long
            'created_at' => ['required', 'date'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'order_id.required' => 'order_id is required',
            'order_id.string' => 'order_id must be a string',
            'order_id.max' => 'order_id must not exceed 255 characters',
            'customer_email.required' => 'customer_email is required',
            'customer_email.email' => 'customer_email must be a valid email address',
            'total_amount.required' => 'total_amount is required',
            'total_amount.numeric' => 'total_amount must be a number',
            'total_amount.min' => 'total_amount must be greater than or equal to 0',
            'currency.required' => 'currency is required',
            'currency.string' => 'currency must be a string',
            'currency.size' => 'currency must be a 3-letter code like EUR',
            'currency.alpha' => 'currency must contain only letters',
            'created_at.required' => 'created_at is required',
            'created_at.date' => 'created_at must be a valid date',
        ];
    }
}
