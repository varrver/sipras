<?php

namespace App\Http\Requests\Category;

use App\Models\Category;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class UpdateCategoryRequest extends FormRequest
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
            'name' => [
                'required', 'string', 'max:255',
                function (string $attribute, string $value, Closure $fail) {
                    Category::query()
                        ->whereRaw('LOWER(name) = ?', [Str::lower($value)])
                        ->when($this->route('category'), fn ($query, $category) => $query->where('id', '!=', $category->id))
                        ->exists()
                        && $fail('Kategori dengan nama tersebut sudah ada.');
                },
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
            'name.required' => 'Nama kategori wajib diisi.',
            'name.max' => 'Nama kategori maksimal :max karakter.',
        ];
    }
}
