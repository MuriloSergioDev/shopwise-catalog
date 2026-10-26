<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Shape defined in shopwise-contracts/openapi/catalog.yaml (components.schemas.ProductSummary).
 *
 * @mixin Product
 */
final class ProductSummaryResource extends JsonResource
{
    /**
     * @return array{id: string, slug: string, name: string, category: array{id: string, slug: string, name: string}, image: array{url: string, alt: string}|null}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'category' => ['id' => $this->category->id, 'slug' => $this->category->slug, 'name' => $this->category->name],
            'image' => $this->images[0] ?? null,
        ];
    }
}
