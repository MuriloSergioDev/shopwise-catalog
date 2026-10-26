<?php

declare(strict_types=1);

use App\Models\Category;

it('lists every category by name with its parent', function (): void {
    $apparel = Category::factory()->create(['slug' => 'apparel', 'name' => 'Apparel']);
    $tops = Category::factory()->childOf($apparel)->create(['slug' => 'tops', 'name' => 'Tops']);

    $this->getJson('/api/categories')
        ->assertOk()
        ->assertExactJson(['data' => [
            ['id' => $apparel->id, 'slug' => 'apparel', 'name' => 'Apparel', 'parentId' => null],
            ['id' => $tops->id, 'slug' => 'tops', 'name' => 'Tops', 'parentId' => $apparel->id],
        ]]);
});
