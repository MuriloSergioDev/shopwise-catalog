<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('variants', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('product_id')->constrained()->cascadeOnDelete();
            // The join key with inventory and pricing: never reused, never changed.
            $table->string('sku', 64)->unique();
            $table->string('name', 200);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // One row per option instead of a JSON column so facets are a portable GROUP BY.
        Schema::create('variant_options', function (Blueprint $table): void {
            $table->id();
            $table->foreignUlid('variant_id')->constrained()->cascadeOnDelete();
            $table->string('name', 50);
            $table->string('value', 50);

            $table->unique(['variant_id', 'name']);
            $table->index(['name', 'value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variant_options');
        Schema::dropIfExists('variants');
    }
};
