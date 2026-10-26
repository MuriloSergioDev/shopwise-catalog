<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Variant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Shape defined in shopwise-contracts/openapi/catalog.yaml (components.schemas.Variant).
 *
 * @mixin Variant
 */
final class VariantResource extends JsonResource
{
    /**
     * @return array{id: string, sku: string, name: string, options: object, active: bool}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            // An object so that "no options" is {} in JSON, not [].
            'options' => (object) $this->optionMap(),
            'active' => $this->active,
        ];
    }
}
