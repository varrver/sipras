<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class ImportUsersRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The uploaded FILE's honesty is validated here (shape, type, size); the
     * ROW contents are validated procedurally in the controller because every
     * row must be collected and reported at once — never a first-error exit.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
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
            'csv_file.required' => 'Berkas CSV wajib diunggah.',
            'csv_file.file' => 'Berkas CSV wajib diunggah.',
            'csv_file.mimes' => 'Berkas harus berformat CSV.',
            'csv_file.max' => 'Ukuran berkas maksimal :max KB.',
        ];
    }
}
