<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\VariantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Shopwise\Contracts\Events\Catalog\VariantChangedV1;

/**
 * @property string $id
 * @property string $product_id
 * @property string $sku
 * @property string $name
 * @property bool $active
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Product $product
 * @property-read Collection<int, VariantOption> $options
 */
#[Fillable(['sku', 'name', 'active'])]
final class Variant extends Model
{
    /** @use HasFactory<VariantFactory> */
    use HasFactory;

    use HasUlids;

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return HasMany<VariantOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(VariantOption::class)->orderBy('name');
    }

    /**
     * Option name => value, sorted by name.
     *
     * @return array<string, string>
     */
    public function optionMap(): array
    {
        return $this->options->sortBy('name')->mapWithKeys(fn (VariantOption $option): array => [$option->name => $option->value])->all();
    }

    /**
     * Options as the events carry them: a name/value list sorted by name.
     *
     * @return list<array{name: string, value: string}>
     */
    public function eventOptions(): array
    {
        return array_map(
            fn (string $name, string $value): array => ['name' => $name, 'value' => $value],
            array_keys($this->optionMap()),
            array_values($this->optionMap()),
        );
    }

    public function changedEvent(): VariantChangedV1
    {
        return new VariantChangedV1(
            variantId: $this->id,
            productId: $this->product_id,
            sku: $this->sku,
            name: $this->name,
            options: $this->eventOptions(),
            active: $this->active,
            changedAt: $this->updated_at->toIso8601ZuluString(),
        );
    }

    /**
     * @param  array<string, string>  $options
     */
    public function replaceOptions(array $options): void
    {
        $this->options()->delete();
        ksort($options);
        foreach ($options as $name => $value) {
            $this->options()->create(['name' => $name, 'value' => $value]);
        }

        $this->unsetRelation('options');
    }

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }
}
