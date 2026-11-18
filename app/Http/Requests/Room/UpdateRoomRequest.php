<?php

namespace App\Http\Requests\Room;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoomRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('rooms', 'code')->ignore($this->route('room'))],
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
            'name.required' => 'Nama ruangan wajib diisi.',
            'name.max' => 'Nama ruangan maksimal :max karakter.',
            'code.required' => 'Kode ruangan wajib diisi.',
            'code.max' => 'Kode ruangan maksimal :max karakter.',
            'code.unique' => 'Kode ruangan sudah digunakan.',
        ];
    }
}
