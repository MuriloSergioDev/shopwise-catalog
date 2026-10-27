<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Product;
use App\Models\Variant;

it('lists drafts and published products, newest first, with variant counts', function (): void {
    $category = Category::factory()->create(['slug' => 'tops', 'name' => 'Tops']);
    $this->travelTo(now()->subDay());
    $published = Product::factory()->published()->for($category)->create(['slug' => 'old-tee', 'name' => 'Old Tee']);
    Variant::factory()->for($published)->create();
    $this->travelBack();
    $draft = Product::factory()->for($category)->create([
        'slug' => 'new-tee',
        'name' => 'New Tee',
        'images' => [['url' => 'https://example.com/tee.jpg', 'alt' => 'Tee']],
    ]);
    Variant::factory()->for($draft)->create();
    Variant::factory()->for($draft)->inactive()->create();

    $this->getJson('/api/admin/products', bearer())
        ->assertOk()
        ->assertJsonPath('meta', ['page' => 1, 'perPage' => 25, 'total' => 2, 'lastPage' => 1])
        ->assertJsonPath('data.0', [
            'id' => $draft->id,
            'slug' => 'new-tee',
            'name' => 'New Tee',
            'category' => ['id' => $category->id, 'slug' => 'tops', 'name' => 'Tops'],
            'image' => ['url' => 'https://example.com/tee.jpg', 'alt' => 'Tee'],
            'status' => 'draft',
            'publishedAt' => null,
            'variantCount' => 2,
            'activeVariantCount' => 1,
            'updatedAt' => $draft->updated_at->toIso8601ZuluString(),
        ])
        ->assertJsonPath('data.1.id', $published->id)
        ->assertJsonPath('data.1.status', 'published')
        ->assertJsonPath('data.1.publishedAt', $published->published_at?->toIso8601ZuluString());
});

it('filters by status', function (): void {
    $draft = Product::factory()->create();
    Product::factory()->published()->create();

    $this->getJson('/api/admin/products?status=draft', bearer())
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $draft->id);
});

it('matches name, slug or a variant SKU, ignoring case', function (): void {
    $byName = Product::factory()->create(['name' => 'Merino Crew Tee', 'slug' => 'a-product']);
    $bySlug = Product::factory()->create(['name' => 'Other', 'slug' => 'linen-merino-shirt']);
    $bySku = Product::factory()->create(['name' => 'Socks', 'slug' => 'socks']);
    Variant::factory()->for($bySku)->create(['sku' => 'MERINO-SOCK-M']);
    Product::factory()->create(['name' => 'Mug', 'slug' => 'mug']);

    $ids = collect($this->getJson('/api/admin/products?q=MeRiNo', bearer())->assertOk()->json('data'))->pluck('id');

    expect($ids->all())->toEqualCanonicalizing([$byName->id, $bySlug->id, $bySku->id]);
});

it('filters by category including its children, and an unknown slug matches nothing', function (): void {
    $apparel = Category::factory()->create(['slug' => 'apparel']);
    $tops = Category::factory()->create(['slug' => 'tops', 'parent_id' => $apparel->id]);
    $tee = Product::factory()->for($tops)->create();
    Product::factory()->create();

    $this->getJson('/api/admin/products?category=apparel', bearer())
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $tee->id);
    $this->getJson('/api/admin/products?category=nope', bearer())->assertOk()->assertJsonCount(0, 'data');
});

it('pages the list', function (): void {
    Product::factory()->count(3)->create();

    $this->getJson('/api/admin/products?perPage=2&page=2', bearer())
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta', ['page' => 2, 'perPage' => 2, 'total' => 3, 'lastPage' => 2]);
});

it('rejects invalid query parameters', function (): void {
    $this->getJson('/api/admin/products?status=archived', bearer())->assertUnprocessable()->assertJsonValidationErrors('status');
    $this->getJson('/api/admin/products?perPage=101', bearer())->assertUnprocessable()->assertJsonValidationErrors('perPage');
});

it('needs an admin token', function (): void {
    $this->getJson('/api/admin/products')->assertUnauthorized();
    $this->getJson('/api/admin/products', bearer(['customer']))->assertForbidden();
});
