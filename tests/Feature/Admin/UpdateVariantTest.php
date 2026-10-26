<?php

declare(strict_types=1);

use App\Models\Variant;

it('updates a variant and records its full new state', function (): void {
    $variant = Variant::factory()->withOptions(['color' => 'Black', 'size' => 'M'])->create(['name' => 'Black / M']);

    $this->patchJson("/api/admin/variants/{$variant->id}", ['options' => ['color' => 'Navy'], 'active' => false], bearer())
        ->assertOk()
        ->assertJsonPath('data.options', ['color' => 'Navy'])
        ->assertJsonPath('data.active', false)
        ->assertJsonPath('data.name', 'Black / M');

    $variant->refresh();
    expect(recordedEvents('catalog.variant-changed'))->toEqual([[
        'variantId' => $variant->id,
        'productId' => $variant->product_id,
        'sku' => $variant->sku,
        'name' => 'Black / M',
        'options' => [['name' => 'color', 'value' => 'Navy']],
        'active' => false,
        'changedAt' => $variant->updated_at->toIso8601ZuluString(),
    ]]);
});

it('records nothing when nothing changed', function (): void {
    $variant = Variant::factory()->withOptions(['size' => 'M'])->create(['name' => 'M']);

    $this->patchJson("/api/admin/variants/{$variant->id}", ['name' => 'M', 'options' => ['size' => 'M']], bearer())->assertOk();

    expect(recordedEvents('catalog.variant-changed'))->toBe([]);
});

it('needs at least one field and an admin', function (): void {
    $variant = Variant::factory()->create();

    $this->patchJson("/api/admin/variants/{$variant->id}", [], bearer())->assertUnprocessable()->assertJsonValidationErrors('name');
    $this->patchJson("/api/admin/variants/{$variant->id}", ['active' => false], bearer(['customer']))->assertForbidden();
});
