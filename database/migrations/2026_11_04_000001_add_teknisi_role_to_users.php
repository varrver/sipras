<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the `teknisi` role to the users table's enum. SQLite (the engine
     * every feature test runs on) cannot alter a CHECK constraint in place,
     * so the table is rebuilt: an empty new table with the widened enum,
     * every row copied back, then a rename. MariaDB's ALTER … MODIFY executes
     * the same widening natively.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::create('users_technician_widened', function (Blueprint $table) {
                $this->columns($table);
            });

            DB::statement('INSERT INTO users_technician_widened
                (id, name, email, email_verified_at, password, role,
                 remember_token, created_at, updated_at)
                SELECT id, name, email, email_verified_at, password, role,
                       remember_token, created_at, updated_at FROM users');

            Schema::drop('users');
            Schema::rename('users_technician_widened', 'users');
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->enum('role', ['admin', 'teknisi', 'siswa'])->default('siswa')->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::create('users_technician_narrowed', function (Blueprint $table) {
                $this->columns($table);
            });

            // A narrowing must not strand teknisi rows: they keep living, on
            // the closest legal role (siswa), because deleting real people is
            // not a downgrade's business.
            DB::statement("INSERT INTO users_technician_narrowed
                (id, name, email, email_verified_at, password, role,
                 remember_token, created_at, updated_at)
                SELECT id, name, email, email_verified_at, password,
                       CASE role WHEN 'teknisi' THEN 'siswa' ELSE role END,
                       remember_token, created_at, updated_at FROM users");

            Schema::drop('users');
            Schema::rename('users_technician_narrowed', 'users');
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->enum('role', ['admin', 'siswa'])->default('siswa')->change();
            });
        }
    }

    /**
     * The users table's columns in its 2026-11-04 shape, shared by both
     * rebuild legs so each keeps every constraint the original had.
     */
    private function columns(Blueprint $table): void
    {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password');
        $table->enum('role', ['admin', 'teknisi', 'siswa'])->default('siswa');
        $table->rememberToken();
        $table->timestamps();
    }
};
