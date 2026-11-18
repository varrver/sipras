<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The specialist's assignment: which complaint field this teknisi works.
     * Plain nullable ADD (no SQLite rebuild): both engines take it natively,
     * and only teknisi accounts ever carry a value — enforced at validation
     * (D2), not by the schema.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('category_id')
                ->nullable()
                ->after('role')
                ->constrained('categories')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });
    }
};
