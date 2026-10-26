<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
final class CategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->word().' '.fake()->word());

        return [
            'parent_id' => null,
            'slug' => Str::slug($name),
            'name' => $name,
        ];
    }

    public function childOf(Category $parent): self
    {
        return $this->state(['parent_id' => $parent->id]);
    }
}
