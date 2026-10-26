<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateProduct;
use App\Http\Requests\CreateProductRequest;
use App\Http\Resources\ProductResource;
use Illuminate\Http\JsonResponse;

final class CreateProductController
{
    public function __invoke(CreateProductRequest $request, CreateProduct $createProduct): JsonResponse
    {
        $product = $createProduct->handle($request->toData());

        return ProductResource::make($product)->response()->setStatusCode(201);
    }
}
