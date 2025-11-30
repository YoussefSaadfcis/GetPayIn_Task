<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WebHockRequest extends FormRequest
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
            'idempotency_key' => 'required|string',
            'order_id' => 'required|exists:orders,id',
            'status' => 'required|string',
        ];
    }
}
