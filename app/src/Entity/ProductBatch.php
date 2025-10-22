<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\ProductBatchGenerated;
use App\Repository\ProductBatchRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * ProductBatch Entity
 *
 * Tracks product batches with manufacturing dates, expiry dates, lot numbers, and inventory quantities for complete supply chain traceability *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Genmax Code Generator
 */
#[ORM\Entity(repositoryClass: ProductBatchRepository::class)]
#[ORM\Table(name: 'product_batch')]
class ProductBatch extends ProductBatchGenerated
{
    // Add custom properties here

    // Add custom methods here
}
