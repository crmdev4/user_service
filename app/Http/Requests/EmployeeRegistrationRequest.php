<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeRegistrationRequest extends FormRequest
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
            'mobile_phone' => 'required|min:2|numeric',
            'employee_id_card' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ];
    }

    // custom message
    public function messages(): array
    {
        return [
            'mobile_phone.required' => 'Nomor telepon wajib diisi.',
            'mobile_phone.min' => 'Nomor telepon minimal 2 digit.',
            'mobile_phone.numeric' => 'Nomor telepon berupa nomor.',
            'employee_id_card.required' => 'ID Card wajib diisi.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Password harus sama dengan konfirmasi password.',
        ];
    }
}
