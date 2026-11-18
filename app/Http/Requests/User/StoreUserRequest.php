<?php

namespace App\Http\Requests\User;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize the specialty before validation: only a teknisi may carry one,
     * so a stray or half-hearted category posted with another role is nulled
     * out entirely rather than validated (D2).
     */
    protected function prepareForValidation(): void
    {
        if ($this->input('role') !== 'teknisi') {
            $this->merge(['category_id' => null]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in(User::ROLES)],
            'category_id' => [
                Rule::requiredIf($this->input('role') === 'teknisi'),
                'nullable',
                Rule::exists('categories', 'id'),
            ],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'name.max' => 'Nama maksimal :max karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.max' => 'Email maksimal :max karakter.',
            'email.unique' => 'Email tersebut sudah digunakan.',
            'password.required' => 'Password wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min' => 'Password minimal :min karakter.',
            'role.required' => 'Peran (role) wajib dipilih.',
            'role.in' => 'Peran (role) tidak valid.',
            'category_id.required' => 'Spesialisasi kategori wajib dipilih untuk role Teknisi.',
            'category_id.exists' => 'Spesialisasi kategori tidak valid.',
        ];
    }
}
