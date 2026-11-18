<?php

namespace App\Http\Requests\Teknisi;

use App\Models\Complaint;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The teknisi detail page's status-transition contract. Same vocabulary and
 * Indonesian messages as the admin request, with one role difference the
 * teacher's requirement carries: the technician MUST attach a repair photo
 * when finishing (Selesai), where the admin's own surface keeps it optional.
 */
class TeknisiComplaintStatusRequest extends FormRequest
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
                // A final state accepts no further changes — the re-save of its
                // own state (a note typo, re-attaching proof) stays allowed, but
                // a flip out of Selesai/Ditolak is refused here as a validation
                // error, so no crafted POST can turn the model's contract into
                // an unhandled exception (shared with the admin request).
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
                // The specialist's boundary (D4): mutating another field's
                // complaint is a validation error — the form keeps the
                // technician honest on the detail page they are already on.
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $complaint = $this->route('complaint');
                    $specialty = $this->user()?->category_id;

                    if (! $complaint instanceof Complaint || $specialty === null) {
                        return;
                    }

                    if ($complaint->category_id !== $specialty) {
                        $fail('Pengaduan ini di luar spesialisasi Anda.');
                    }
                },
            ],
            'admin_notes' => ['nullable', 'string', 'required_if:status,rejected'],
            'resolved_image' => ['required_if:status,resolved', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
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
            'admin_notes.required_if' => 'Alasan wajib diisi saat menolak pengaduan.',
            'resolved_image.required_if' => 'Foto bukti perbaikan wajib diunggah saat menyimpan selesai.',
            'resolved_image.image' => 'Bukti perbaikan harus berupa gambar.',
            'resolved_image.mimes' => 'Bukti perbaikan harus berformat jpeg, png, atau jpg.',
            'resolved_image.max' => 'Bukti perbaikan maksimal 2 MB.',
        ];
    }
}
