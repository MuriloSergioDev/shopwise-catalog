<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\Variant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Variant>
 */
final class VariantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'sku' => mb_strtoupper(fake()->unique()->bothify('SKU-####-??')),
            'name' => fake()->words(2, true),
            'active' => true,
        ];
    }

    /**
     * @param  array<string, string>  $options
     */
    public function withOptions(array $options): self
    {
        return $this->afterCreating(fn (Variant $variant) => $variant->replaceOptions($options));
    }

    public function inactive(): self
    {
        return $this->state(['active' => false]);
    }
}
