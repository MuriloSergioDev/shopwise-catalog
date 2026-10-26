<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

final class ProductNotPublishable extends RuntimeException
{
    public static function alreadyPublished(): self
    {
        return new self('The product is already published.');
    }

    public static function withoutActiveVariant(): self
    {
        return new self('A product needs at least one active variant to be published.');
    }

    public function render(): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], 409);
    }
}
