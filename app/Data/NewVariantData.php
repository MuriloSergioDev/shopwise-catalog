<?php

declare(strict_types=1);

namespace App\Data;

final readonly class NewVariantData
{
    /**
     * @param  array<string, string>  $options
     */
    public function __construct(
        public string $sku,
        public string $name,
        public array $options,
    ) {}
}
