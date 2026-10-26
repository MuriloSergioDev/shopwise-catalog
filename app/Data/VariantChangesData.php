<?php

declare(strict_types=1);

namespace App\Data;

/**
 * Partial update of a variant: null means "leave as is".
 */
final readonly class VariantChangesData
{
    /**
     * @param  array<string, string>|null  $options
     */
    public function __construct(
        public ?string $name = null,
        public ?array $options = null,
        public ?bool $active = null,
    ) {}
}
