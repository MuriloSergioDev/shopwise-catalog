<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Shape defined in shopwise-contracts/openapi/catalog.yaml (components.schemas.AdminProductSummary). Needs
 * `variants_count` and `active_variants_count` loaded (ListAdminProducts does).
 *
 * @mixin Product
 *
 * @property int $variants_count
 * @property int $active_variants_count
 */
final class AdminProductSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...ProductSummaryResource::make($this->resource)->toArray($request),
            'status' => $this->status->value,
            'publishedAt' => $this->published_at?->toIso8601ZuluString(),
            'variantCount' => (int) $this->variants_count,
            'activeVariantCount' => (int) $this->active_variants_count,
            'updatedAt' => $this->updated_at->toIso8601ZuluString(),
        ];
    }
}
