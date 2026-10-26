<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\VariantChangesData;
use App\Models\Variant;
use Illuminate\Support\Facades\DB;
use Shopwise\Contracts\Events\Catalog\VariantChangedV1;
use Shopwise\Kit\Messaging\Outbox;

final readonly class UpdateVariant
{
    public function handle(Variant $variant, VariantChangesData $changes): Variant
    {
        return DB::transaction(function () use ($variant, $changes): Variant {
            $variant = Variant::query()->with('options')->lockForUpdate()->findOrFail($variant->id);
            $before = $variant->changedEvent()->toArray();

            $variant->fill(array_filter(['name' => $changes->name, 'active' => $changes->active], fn (mixed $value): bool => $value !== null));
            $variant->save();
            if ($changes->options !== null) {
                $variant->replaceOptions($changes->options);
            }

            $event = $variant->changedEvent();
            // Nothing changed (changedAt aside): stay quiet instead of publishing a duplicate state.
            if (array_diff_key($event->toArray(), ['changedAt' => true]) !== array_diff_key($before, ['changedAt' => true])) {
                $variant->touch();
                Outbox::record(VariantChangedV1::TYPE, $variant->product_id, $variant->changedEvent()->toArray(), VariantChangedV1::SCHEMA);
            }

            return $variant->load('options');
        });
    }
}
