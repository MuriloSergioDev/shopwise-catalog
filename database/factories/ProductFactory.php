<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Builds products directly (no outbox events). Tests that care about events go through the HTTP endpoints.
 *
 * @extends Factory<Product>
 */
final class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->word().' '.fake()->word().' '.fake()->word());

        return [
            'category_id' => Category::factory(),
            'slug' => Str::slug($name),
            'name' => $name,
            'description' => fake()->sentence(),
            'images' => [],
            'status' => ProductStatus::Draft,
            'published_at' => null,
        ];
    }

    public function published(): self
    {
        return $this->state(['status' => ProductStatus::Published, 'published_at' => now()]);
    }
}
