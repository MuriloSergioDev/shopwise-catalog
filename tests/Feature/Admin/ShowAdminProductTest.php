<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Product;
use App\Models\Variant;

it('shows a draft with all its variants, inactive ones included', function (): void {
    $category = Category::factory()->create(['slug' => 'tops', 'name' => 'Tops']);
    $product = Product::factory()->for($category)->create(['slug' => 'merino-tee', 'name' => 'Merino Tee', 'description' => 'Soft.']);
    $active = Variant::factory()->for($product)->withOptions(['size' => 'M'])->create(['sku' => 'TEE-M', 'name' => 'M']);
    $inactive = Variant::factory()->for($product)->inactive()->create(['sku' => 'TEE-S', 'name' => 'S']);

    $this->getJson("/api/admin/products/{$product->id}", bearer())
        ->assertOk()
        ->assertExactJson(['data' => [
            'id' => $product->id,
            'slug' => 'merino-tee',
            'name' => 'Merino Tee',
            'description' => 'Soft.',
            'category' => ['id' => $category->id, 'slug' => 'tops', 'name' => 'Tops'],
            'images' => [],
            'variants' => [
                ['id' => $active->id, 'sku' => 'TEE-M', 'name' => 'M', 'options' => ['size' => 'M'], 'active' => true],
                ['id' => $inactive->id, 'sku' => 'TEE-S', 'name' => 'S', 'options' => [], 'active' => false],
            ],
            'status' => 'draft',
            'publishedAt' => null,
        ]]);
});

it('shows a published product too', function (): void {
    $product = Product::factory()->published()->has(Variant::factory())->create();

    $this->getJson("/api/admin/products/{$product->id}", bearer())
        ->assertOk()
        ->assertJsonPath('data.status', 'published');
});

it('returns 404 for an unknown product', function (): void {
    $this->getJson('/api/admin/products/01JBCDEFGHJKMNPQRSTVWXYZ00', bearer())->assertNotFound();
});

it('needs an admin token', function (): void {
    $product = Product::factory()->create();

    $this->getJson("/api/admin/products/{$product->id}")->assertUnauthorized();
    $this->getJson("/api/admin/products/{$product->id}", bearer(['customer']))->assertForbidden();
});
