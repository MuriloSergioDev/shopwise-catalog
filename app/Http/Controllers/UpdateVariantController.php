<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\UpdateVariant;
use App\Http\Requests\UpdateVariantRequest;
use App\Http\Resources\VariantResource;
use App\Models\Variant;

final class UpdateVariantController
{
    public function __invoke(UpdateVariantRequest $request, Variant $variant, UpdateVariant $updateVariant): VariantResource
    {
        return VariantResource::make($updateVariant->handle($variant, $request->toData()));
    }
}
