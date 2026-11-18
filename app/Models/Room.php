<?php

namespace App\Models;

use Database\Factories\RoomFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'code'])]
class Room extends Model
{
    /** @use HasFactory<RoomFactory> */
    use HasFactory;

    /**
     * The building/floor groups the create-form room picker renders, in the
     * order a reader walks the floor plan: numbered classrooms floor by
     * floor, then labs, then workshops, then offices and facilities.
     */
    public const CREATE_FORM_GROUPS = [
        'Gedung Utama — Lantai 1',
        'Gedung Utama — Lantai 2',
        'Laboratorium',
        'Bengkel',
        'RPS — Ruang Praktik Siswa',
        'Kantor & Fasilitas',
    ];

    /**
     * The group this room files under in the create form's optgroups.
     *
     * Function names rule over code prefixes: the plan seats Bengkel
     * SKT/PSA/SKE/Otomasi inside Rg 2.13–2.17 (so their codes carry `R-2.x`)
     * and pairs `Lab. ASM`/`T. ASM` with floor-1 numbers — a code-only split
     * would scatter the workshops across the classroom floors.
     *
     * Everything unmatchable (offices, toilets, LSP, the DPIB drawing room)
     * lands in "Kantor & Fasilitas" rather than erroring, so master data
     * added later can never break the form.
     */
    public function groupLabel(): string
    {
        if (str_starts_with($this->name, 'Bengkel')) {
            return 'Bengkel';
        }

        if (str_starts_with($this->name, 'Lab.') || str_starts_with($this->name, 'T. ASM')) {
            return 'Laboratorium';
        }

        return match (true) {
            str_starts_with($this->code, 'RPS-') => 'RPS — Ruang Praktik Siswa',
            str_starts_with($this->code, 'R-1.') => 'Gedung Utama — Lantai 1',
            str_starts_with($this->code, 'R-2.') => 'Gedung Utama — Lantai 2',
            default => 'Kantor & Fasilitas',
        };
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }
}
