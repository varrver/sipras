<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name'])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }

    /**
     * The technicians assigned to this field — the staffing half of the
     * in-use deletion guard (a staffed category must be reassigned before
     * it may vanish; the FK restricts as belt, the flash as braces).
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'category_id');
    }
}
