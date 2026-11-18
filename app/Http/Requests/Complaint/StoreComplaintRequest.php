<?php

namespace App\Http\Requests\Complaint;

use Illuminate\Foundation\Http\FormRequest;

class StoreComplaintRequest extends FormRequest
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
            'category_id' => ['required', 'exists:categories,id'],
            'room_id' => ['required', 'exists:rooms,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'evidence_image' => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
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
            'category_id.required' => 'Kategori wajib dipilih.',
            'category_id.exists' => 'Kategori yang dipilih tidak valid.',
            'room_id.required' => 'Ruang wajib dipilih.',
            'room_id.exists' => 'Ruang yang dipilih tidak valid.',
            'title.required' => 'Judul pengaduan wajib diisi.',
            'title.max' => 'Judul pengaduan maksimal :max karakter.',
            'description.required' => 'Deskripsi pengaduan wajib diisi.',
            'description.max' => 'Deskripsi pengaduan maksimal 5.000 karakter.',
            'evidence_image.required' => 'Foto bukti wajib diunggah.',
            'evidence_image.image' => 'Foto bukti harus berupa gambar.',
            'evidence_image.mimes' => 'Foto bukti harus berformat jpeg, png, atau jpg.',
            'evidence_image.max' => 'Foto bukti maksimal 2 MB.',
        ];
    }
}
