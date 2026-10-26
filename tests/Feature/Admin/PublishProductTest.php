<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\Variant;
use Shopwise\Contracts\Events\Catalog\ProductPublishedV1;

it('publishes a draft with its active variants and records catalog.product-published', function (): void {
    $product = Product::factory()->create(['slug' => 'merino-crew-tee', 'name' => 'Merino Crew Tee']);
    $variant = Variant::factory()->for($product)->withOptions(['size' => 'M'])->create(['sku' => 'TEE-M', 'name' => 'M']);
    Variant::factory()->for($product)->inactive()->create();

    $this->postJson("/api/admin/products/{$product->id}/publish", [], bearer())
        ->assertOk()
        ->assertJsonPath('data.status', 'published');

    $product->refresh();
    $events = recordedEvents('catalog.product-published');
    expect($product->published_at)->not->toBeNull()
        ->and($events)->toEqual([[
            'productId' => $product->id,
            'slug' => 'merino-crew-tee',
            'name' => 'Merino Crew Tee',
            'categoryId' => $product->category_id,
            'variants' => [['variantId' => $variant->id, 'sku' => 'TEE-M', 'name' => 'M', 'options' => [['name' => 'size', 'value' => 'M']]]],
            'publishedAt' => $product->published_at?->toIso8601ZuluString(),
        ]])
        ->and(array_keys($events[0]))->toEqualCanonicalizing(array_keys(contractExample('catalog.product-published.v1')->data))
        ->and(ProductPublishedV1::fromArray($events[0])->productId)->toBe($product->id);
});

it('refuses to publish twice', function (): void {
    $product = Product::factory()->published()->has(Variant::factory())->create();

    $this->postJson("/api/admin/products/{$product->id}/publish", [], bearer())->assertConflict();

    expect(recordedEvents('catalog.product-published'))->toBe([]);
});

it('refuses to publish a product without an active variant', function (): void {
    $product = Product::factory()->has(Variant::factory()->inactive())->create();

    $this->postJson("/api/admin/products/{$product->id}/publish", [], bearer())->assertConflict();

    expect($product->refresh()->published_at)->toBeNull();
});

it('returns 404 for an unknown product', function (): void {
    $this->postJson('/api/admin/products/01JBCDEFGHJKMNPQRSTVWXYZ00/publish', [], bearer())->assertNotFound();
});
