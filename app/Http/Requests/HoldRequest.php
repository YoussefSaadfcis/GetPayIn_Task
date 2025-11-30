<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HoldRequest extends FormRequest
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
            'qty' => 'required|integer|min:1',
            'product_id' => ['required',Rule::exists('products', 'id')->where(function ($query) {
                $query->where('id', $this->input('product_id'))
                      ->where('stock', '>=', $this->input('qty'));
            }),
        ],
        ];
    }
}
