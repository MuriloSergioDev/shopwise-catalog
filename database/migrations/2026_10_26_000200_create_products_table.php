<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('category_id')->constrained()->restrictOnDelete();
            $table->string('slug')->unique();
            $table->string('name', 200);
            $table->text('description');
            $table->json('images');
            $table->string('status', 20);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'published_at']);
        });

        // Full-text search (ProductSearch). SQLite test runs fall back to LIKE and have no such column.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
                ALTER TABLE products ADD COLUMN search tsvector GENERATED ALWAYS AS (
                    setweight(to_tsvector('english', name), 'A') || setweight(to_tsvector('english', description), 'B')
                ) STORED
                SQL);
            DB::statement('CREATE INDEX products_search_index ON products USING GIN (search)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
