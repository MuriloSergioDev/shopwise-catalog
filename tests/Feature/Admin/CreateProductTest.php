<?php

declare(strict_types=1);

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\Variant;
use Shopwise\Kit\Messaging\OutboxMessage;

function newProductBody(Category $category, array $overrides = []): array
{
    return array_replace([
        'name' => 'Merino Crew Tee',
        'description' => 'Soft merino wool.',
        'categoryId' => $category->id,
        'images' => [['url' => 'https://example.com/tee.jpg', 'alt' => 'Merino tee']],
        'variants' => [
            ['sku' => 'TEE-BLK-M', 'name' => 'Black / M', 'options' => ['size' => 'M', 'color' => 'Black']],
            ['sku' => 'TEE-ONE', 'name' => 'One size'],
        ],
    ], $overrides);
}

it('creates a draft product and announces every variant', function (): void {
    $category = Category::factory()->create();

    $response = $this->postJson('/api/admin/products', newProductBody($category), bearer())
        ->assertCreated()
        ->assertJsonPath('data.slug', 'merino-crew-tee')
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonPath('data.publishedAt', null)
        ->assertJsonPath('data.category.id', $category->id)
        ->assertJsonPath('data.variants.0.options', ['color' => 'Black', 'size' => 'M']);

    // An empty option set must stay a JSON object in the API.
    expect($response->getContent())->toContain('"options":{}');

    $product = Product::query()->sole();
    $variant = Variant::query()->where('sku', 'TEE-BLK-M')->sole();
    $plain = Variant::query()->where('sku', 'TEE-ONE')->sole();
    expect($product->status)->toBe(ProductStatus::Draft)
        ->and(OutboxMessage::query()->pluck('subject')->unique()->values()->all())->toBe([$product->id])
        // toEqual: Postgres jsonb does not keep key order. Sorted by SKU: ULIDs in one millisecond are not ordered.
        ->and(collect(recordedEvents('catalog.variant-changed'))->sortBy('sku')->values()->all())->toEqual([
            [
                'variantId' => $variant->id,
                'productId' => $product->id,
                'sku' => 'TEE-BLK-M',
                'name' => 'Black / M',
                'options' => [['name' => 'color', 'value' => 'Black'], ['name' => 'size', 'value' => 'M']],
                'active' => true,
                'changedAt' => $variant->updated_at->toIso8601ZuluString(),
            ],
            [
                'variantId' => $plain->id,
                'productId' => $product->id,
                'sku' => 'TEE-ONE',
                'name' => 'One size',
                'options' => [],
                'active' => true,
                'changedAt' => $plain->updated_at->toIso8601ZuluString(),
            ],
        ]);
});

it('records events with exactly the fields of the contract', function (): void {
    $this->postJson('/api/admin/products', newProductBody(Category::factory()->create()), bearer())->assertCreated();

    $contract = contractExample('catalog.variant-changed.v1')->data;
    expect(array_keys(recordedEvents('catalog.variant-changed')[0]))->toEqualCanonicalizing(array_keys($contract));
});

it('requires an admin token', function (): void {
    $body = newProductBody(Category::factory()->create());

    $this->postJson('/api/admin/products', $body)->assertUnauthorized();
    $this->postJson('/api/admin/products', $body, bearer(['customer']))->assertForbidden();

    expect(Product::query()->count())->toBe(0);
});

it('rejects invalid products', function (array $overrides, string $field): void {
    $category = Category::factory()->create();
    Product::factory()->create(['slug' => 'taken']);
    Variant::factory()->create(['sku' => 'TAKEN-SKU']);

    $this->postJson('/api/admin/products', newProductBody($category, $overrides), bearer())
        ->assertUnprocessable()->assertJsonValidationErrors($field);

    expect(OutboxMessage::query()->count())->toBe(0);
})->with([
    'duplicate slug' => [['slug' => 'taken'], 'slug'],
    'bad slug' => [['slug' => 'Not A Slug'], 'slug'],
    'unknown category' => [['categoryId' => '01JBCDEFGHJKMNPQRSTVWXYZ00'], 'categoryId'],
    'no variants' => [['variants' => []], 'variants'],
    'existing sku' => [['variants' => [['sku' => 'TAKEN-SKU', 'name' => 'x']]], 'variants.0.sku'],
    'repeated sku' => [['variants' => [['sku' => 'A', 'name' => 'x'], ['sku' => 'A', 'name' => 'y']]], 'variants.0.sku'],
    'bad option name' => [['variants' => [['sku' => 'A', 'name' => 'x', 'options' => ['Color' => 'Black']]]], 'variants.0.options'],
    'image without alt' => [['images' => [['url' => 'https://example.com/a.jpg']]], 'images.0.alt'],
]);
