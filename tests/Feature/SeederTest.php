<?php

declare(strict_types=1);

use App\Models\Product;
use Database\Seeders\DatabaseSeeder;

it('seeds a published demo catalog once, through the actions', function (): void {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    expect(Product::query()->published()->count())->toBe(7)
        ->and(recordedEvents('catalog.product-published'))->toHaveCount(7);

    $this->getJson('/api/products?category=apparel')->assertOk()->assertJsonPath('meta.total', 5);
});
