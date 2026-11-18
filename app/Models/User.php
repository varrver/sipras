<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'email', 'password', 'role', 'category_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /**
     * The applied role vocabulary — the form rules, the role selects, and any
     * role-validating code read THIS list, so a new role lands everywhere at
     * once instead of in three scattered arrays. A static call site reaches it
     * as `collect(...)->map(...)` — the constants are an array, and the
     * pickers consume collections. The order is the pickers' display order:
     * admin first (the school's operators), then teknisi, then siswa.
     */
    public const ROLES = ['admin', 'teknisi', 'siswa'];

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'category_id' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // The complaints FK is cascadeOnDelete (PRD), so the database alone
        // would wipe the rows without firing a single Eloquent event — every
        // evidence and resolved image would silently orphan on the public
        // disk, and both delete paths (admin master-data and the profile's
        // self-delete) would leak identically. Delete through Eloquent instead
        // so complaint events stay available to future listeners, sweep the
        // files, and clear the now-stale session rows.
        //
        // Known tradeoff: files are unlinked before the surrounding statement
        // (if any) commits, so a rollback that restores the rows cannot
        // restore their images. Chosen over DB::afterCommit deliberately:
        // after-commit callbacks never run under RefreshDatabase's wrapping
        // transaction, which would make this sweep untestable.
        static::deleting(function (User $user): void {
            $user->complaints->each(function (Complaint $complaint): void {
                Storage::disk('public')->delete(array_filter([
                    $complaint->evidence_image,
                    $complaint->resolved_image,
                ]));

                $complaint->delete();
            });

            DB::table('sessions')->where('user_id', $user->id)->delete();
        });
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }
}
