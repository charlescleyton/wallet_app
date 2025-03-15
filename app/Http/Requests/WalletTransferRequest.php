<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class WalletTransferRequest extends FormRequest
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
            'amount' => ['required', 'numeric', 'min:1'],
            'target_user_id' => ['required', 'exists:users,id']
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422)
        );
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'O campo amount é obrigatório',
            'amount.numeric' => 'O campo amount deve ser um número',
            'amount.min' => 'O campo amount deve ser maior que 0',

            'target_user_id.required' => 'O campo target_user_id é obrigatório',
            'target_user_id.exists' => 'O campo target_user_id deve ser um usuário existente',
        ];
    }
}
