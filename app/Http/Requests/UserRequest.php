<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
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
            'name'   => 'required',
            'phone'   => 'required',
            'host'   => 'required',
            'email'     => 'required|string|max:255',
            'password'  => ['required', 'confirmed', 'min:8', 'regex:/[A-Z]/'],
            
        ];   
    }

     // custom message
     public function messages(): array
     {
         return [
            'required' => 'Field input tidak boleh kosong',
            'password.min'    => 'Password minimal harus 8 karakter',
            'password.regex'    => 'Password harus mengandung min 1 huruf besar dang kombinasi angka',
            'password.confirmed'    => 'Password konfirmasi tidak cocok',
         ];
     }
}
