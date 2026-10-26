<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Product;
use App\Models\Variant;

/**
 * Apparel > Tops (tee: Black/M + Navy/L, hoodie: Black/L), Apparel > Bottoms (jeans: Indigo/32), Kitchen (skillet),
 * plus a draft tee that must never show up.
 */
beforeEach(function (): void {
    $apparel = Category::factory()->create(['slug' => 'apparel', 'name' => 'Apparel']);
    $tops = Category::factory()->childOf($apparel)->create(['slug' => 'tops', 'name' => 'Tops']);
    $bottoms = Category::factory()->childOf($apparel)->create(['slug' => 'bottoms', 'name' => 'Bottoms']);
    $kitchen = Category::factory()->create(['slug' => 'kitchen', 'name' => 'Kitchen']);

    $product = fn (Category $category, string $slug, string $name, string $description, int $daysAgo): Product => Product::factory()->published()
        ->for($category)->create(['slug' => $slug, 'name' => $name, 'description' => $description, 'published_at' => now()->subDays($daysAgo)]);

    $tee = $product($tops, 'merino-tee', 'Merino Tee', 'Soft wool shirt for every season.', 4);
    Variant::factory()->for($tee)->withOptions(['color' => 'Black', 'size' => 'M'])->create();
    Variant::factory()->for($tee)->withOptions(['color' => 'Navy', 'size' => 'L'])->create();
    // Inactive variants do not count: nobody can buy a Red tee.
    Variant::factory()->for($tee)->inactive()->withOptions(['color' => 'Red', 'size' => 'M'])->create();

    $hoodie = $product($tops, 'fleece-hoodie', 'Fleece Hoodie', 'Warm cotton hoodie.', 3);
    Variant::factory()->for($hoodie)->withOptions(['color' => 'Black', 'size' => 'L'])->create();

    $jeans = $product($bottoms, 'selvedge-jeans', 'Selvedge Jeans', 'Raw denim.', 2);
    Variant::factory()->for($jeans)->withOptions(['color' => 'Indigo', 'size' => '32'])->create();

    $skillet = $product($kitchen, 'cast-iron-skillet', 'Cast Iron Skillet', 'Pan for searing.', 1);
    Variant::factory()->for($skillet)->create();

    Product::factory()->for($tops)->has(Variant::factory()->withOptions(['color' => 'Black']))->create(['name' => 'Draft Merino Tee']);
});

it('lists published products newest first with category facets', function (): void {
    $this->getJson('/api/products')
        ->assertOk()
        ->assertJsonPath('meta', ['page' => 1, 'perPage' => 24, 'total' => 4, 'lastPage' => 1])
        ->assertJsonPath('data.*.slug', ['cast-iron-skillet', 'selvedge-jeans', 'fleece-hoodie', 'merino-tee'])
        ->assertJsonPath('data.0.category', ['id' => Category::query()->where('slug', 'kitchen')->value('id'), 'slug' => 'kitchen', 'name' => 'Kitchen'])
        ->assertJsonPath('data.0.image', null)
        ->assertJsonPath('facets.categories', [
            ['slug' => 'bottoms', 'name' => 'Bottoms', 'count' => 1],
            ['slug' => 'kitchen', 'name' => 'Kitchen', 'count' => 1],
            ['slug' => 'tops', 'name' => 'Tops', 'count' => 2],
        ])
        ->assertJsonPath('facets.options', [
            ['name' => 'color', 'values' => [['value' => 'Black', 'count' => 2], ['value' => 'Indigo', 'count' => 1], ['value' => 'Navy', 'count' => 1]]],
            ['name' => 'size', 'values' => [['value' => '32', 'count' => 1], ['value' => 'L', 'count' => 2], ['value' => 'M', 'count' => 1]]],
        ]);
});

it('searches name and description', function (string $query, array $slugs): void {
    $this->getJson('/api/products?'.http_build_query(['q' => $query]))
        ->assertOk()
        ->assertJsonPath('data.*.slug', $slugs);
})->with([
    'name' => ['merino', ['merino-tee']],
    'description' => ['denim', ['selvedge-jeans']],
    'every word must match' => ['warm hoodie', ['fleece-hoodie']],
    'nothing' => ['bicycle', []],
]);

it('filters by category including its children', function (): void {
    $this->getJson('/api/products?category=apparel')
        ->assertOk()
        ->assertJsonPath('data.*.slug', ['selvedge-jeans', 'fleece-hoodie', 'merino-tee'])
        // The category facet ignores the category filter, so shoppers can switch.
        ->assertJsonCount(3, 'facets.categories');

    $this->getJson('/api/products?category=unknown')->assertOk()->assertJsonPath('meta.total', 0);
});

it('filters by variant options, counting each facet without its own filter', function (): void {
    $this->getJson('/api/products?'.http_build_query(['options' => ['color' => 'Black']]))
        ->assertOk()
        ->assertJsonPath('data.*.slug', ['fleece-hoodie', 'merino-tee'])
        ->assertJsonPath('facets.options.0', ['name' => 'color', 'values' => [
            ['value' => 'Black', 'count' => 2], ['value' => 'Indigo', 'count' => 1], ['value' => 'Navy', 'count' => 1],
        ]])
        ->assertJsonPath('facets.options.1', ['name' => 'size', 'values' => [['value' => 'L', 'count' => 2], ['value' => 'M', 'count' => 1]]]);

    $this->getJson('/api/products?'.http_build_query(['options' => ['color' => 'Red']]))->assertOk()->assertJsonPath('meta.total', 0);
});

it('paginates', function (): void {
    $this->getJson('/api/products?perPage=3&page=2')
        ->assertOk()
        ->assertJsonPath('meta', ['page' => 2, 'perPage' => 3, 'total' => 4, 'lastPage' => 2])
        ->assertJsonPath('data.*.slug', ['merino-tee']);
});

it('rejects invalid parameters', function (string $query, string $field): void {
    $this->getJson("/api/products?{$query}")->assertUnprocessable()->assertJsonValidationErrors($field);
})->with([
    'page size too big' => ['perPage=49', 'perPage'],
    'page zero' => ['page=0', 'page'],
    'bad option name' => ['options[Color]=Black', 'options'],
]);
