<?php

declare(strict_types=1);

namespace App\Enums;

enum ProductStatus: string
{
    // Visible to admins only; variants already exist so inventory and pricing can prepare.
    case Draft = 'draft';
    case Published = 'published';
}
