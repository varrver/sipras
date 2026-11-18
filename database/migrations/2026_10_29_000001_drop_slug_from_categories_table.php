<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The slug column was PRD-specified but never served anything: categories
     * route by id and the create/edit forms never carried it (the model just
     * derived it from the name). The PRD schema no longer lists it, so drop it
     * from databases that still carry it — fresh installs (and the in-memory
     * test database, whose initial migration never had the column) skip
     * through the hasColumn guard.
     */
    public function up(): void
    {
        if (Schema::hasColumn('categories', 'slug')) {
            Schema::table('categories', function (Blueprint $table): void {
                $table->dropUnique('categories_slug_unique');
                $table->dropColumn('slug');
            });
        }
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->string('slug')->unique();
        });
    }
};
