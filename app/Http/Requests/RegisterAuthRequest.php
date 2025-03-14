<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rules\Password;

class RegisterAuthRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => [
                'required',
                Password::min(8)
                    ->letters()
                    ->numbers()
                    ->symbols()
                    ->mixedCase()
            ],
        ];
    }


    public function messages(): array
    {
        return [
            'name.required' => 'Nome é obrigatório',
            'name.string' => 'Nome deve ser uma string',
            'name.max' => 'Nome não deve ser maior que 255 caracteres',

            'email.required' => 'Email é obrigatório',
            'email.string' => 'Email deve ser uma string',
            'email.email' => 'Email deve ser um email válido',
            'email.max' => 'Email não deve ser maior que 255 caracteres',
            'email.unique' => 'Este Email já está em uso',

            'password.required' => 'Password é obrigatório',
            'password.string' => 'Password deve ser uma string',
            'password.min' => 'Password deve ter no mínimo 6 caracteres',
        ];
    }
}
