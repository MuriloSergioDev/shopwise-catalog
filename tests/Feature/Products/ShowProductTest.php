<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Product;
use App\Models\Variant;

it('shows a published product with its active variants only', function (): void {
    $category = Category::factory()->create(['slug' => 'tops', 'name' => 'Tops']);
    $product = Product::factory()->published()->for($category)->create([
        'slug' => 'merino-tee',
        'name' => 'Merino Tee',
        'description' => 'Soft.',
        'images' => [['url' => 'https://example.com/tee.jpg', 'alt' => 'Tee']],
    ]);
    $variant = Variant::factory()->for($product)->withOptions(['size' => 'M', 'color' => 'Black'])->create(['sku' => 'TEE-M', 'name' => 'Black / M']);
    Variant::factory()->for($product)->inactive()->create();

    $this->getJson('/api/products/merino-tee')
        ->assertOk()
        ->assertExactJson(['data' => [
            'id' => $product->id,
            'slug' => 'merino-tee',
            'name' => 'Merino Tee',
            'description' => 'Soft.',
            'category' => ['id' => $category->id, 'slug' => 'tops', 'name' => 'Tops'],
            'images' => [['url' => 'https://example.com/tee.jpg', 'alt' => 'Tee']],
            'variants' => [['id' => $variant->id, 'sku' => 'TEE-M', 'name' => 'Black / M', 'options' => ['color' => 'Black', 'size' => 'M'], 'active' => true]],
            'status' => 'published',
            'publishedAt' => $product->published_at?->toIso8601ZuluString(),
        ]]);
});

it('hides drafts and unknown slugs', function (): void {
    Product::factory()->create(['slug' => 'secret']);

    $this->getJson('/api/products/secret')->assertNotFound();
    $this->getJson('/api/products/nope')->assertNotFound();
});
