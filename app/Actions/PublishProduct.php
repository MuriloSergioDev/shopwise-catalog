<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ProductStatus;
use App\Exceptions\ProductNotPublishable;
use App\Models\Product;
use App\Models\Variant;
use Illuminate\Support\Facades\DB;
use Shopwise\Contracts\Events\Catalog\ProductPublishedV1;
use Shopwise\Kit\Messaging\Outbox;

final readonly class PublishProduct
{
    public function handle(Product $product): Product
    {
        return DB::transaction(function () use ($product): Product {
            // Lock so two admins publishing at once record a single event.
            $product = Product::query()->lockForUpdate()->findOrFail($product->id);
            if ($product->status === ProductStatus::Published) {
                throw ProductNotPublishable::alreadyPublished();
            }

            $active = $product->variants()->where('active', true)->with('options')->get();
            if ($active->isEmpty()) {
                throw ProductNotPublishable::withoutActiveVariant();
            }

            $publishedAt = now();
            $product->update(['status' => ProductStatus::Published, 'published_at' => $publishedAt]);

            $event = new ProductPublishedV1(
                productId: $product->id,
                slug: $product->slug,
                name: $product->name,
                categoryId: $product->category_id,
                variants: $active->map(fn (Variant $variant): array => [
                    'variantId' => $variant->id,
                    'sku' => $variant->sku,
                    'name' => $variant->name,
                    'options' => $variant->eventOptions(),
                ])->values()->all(),
                publishedAt: $publishedAt->toIso8601ZuluString(),
            );
            Outbox::record(ProductPublishedV1::TYPE, $product->id, $event->toArray(), ProductPublishedV1::SCHEMA);

            return $product->load(['category', 'variants.options']);
        });
    }
}
