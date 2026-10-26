<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Shape defined in shopwise-contracts/openapi/catalog.yaml (components.schemas.Product). Renders the variants
 * that were loaded: the storefront endpoint loads only active ones.
 *
 * @mixin Product
 */
final class ProductResource extends JsonResource
{
    /**
     * @return array{id: string, slug: string, name: string, description: string, category: array{id: string, slug: string, name: string}, images: list<array{url: string, alt: string}>, variants: AnonymousResourceCollection, status: string, publishedAt: string|null}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'category' => ['id' => $this->category->id, 'slug' => $this->category->slug, 'name' => $this->category->name],
            'images' => $this->images,
            'variants' => VariantResource::collection($this->variants),
            'status' => $this->status->value,
            'publishedAt' => $this->published_at?->toIso8601ZuluString(),
        ];
    }
}
