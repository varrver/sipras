<?php

namespace App\Http\Requests\Complaint;

use App\Models\Complaint;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateComplaintStatusRequest extends FormRequest
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
            'status' => [
                'required',
                Rule::in(Complaint::STATUSES),
                // Identical final-state refusal as the teknisi request's rule:
                // a flip out of a final state is a validation error, never an
                // unhandled model exception, while the same-state re-save
                // flows (note typo, proof replacement) keep working.
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $complaint = $this->route('complaint');

                    if (! $complaint instanceof Complaint) {
                        return;
                    }

                    if ($complaint->status !== $value
                        && (Complaint::TRANSITIONS[$complaint->status]['final'] ?? false)) {
                        $fail('Pengaduan sudah berakhir; statusnya tidak dapat diubah lagi.');
                    }
                },
            ],
            'admin_notes' => ['nullable', 'string', 'required_if:status,rejected'],
            'resolved_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
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
            'status.required' => 'Status wajib dipilih.',
            'status.in' => 'Status yang dipilih tidak valid.',
            'admin_notes.required_if' => 'Catatan admin wajib diisi saat menolak pengaduan.',
            'resolved_image.image' => 'Dokumentasi harus berupa gambar.',
            'resolved_image.mimes' => 'Dokumentasi harus berformat jpeg, png, atau jpg.',
            'resolved_image.max' => 'Dokumentasi maksimal 2 MB.',
        ];
    }
}
