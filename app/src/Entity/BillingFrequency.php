<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\BillingFrequencyGenerated;
use App\Repository\BillingFrequencyRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Billing Frequency Entity
 *
 * Defines billing frequency options for subscriptions (Daily, Weekly, Biweekly, Monthly, Quarterly, Semi-Annual, Annual, Biennial). Controls recurring billing intervals with support for custom cycles and discount management. *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Genmax Code Generator
 */
#[ORM\Entity(repositoryClass: BillingFrequencyRepository::class)]
#[ORM\Table(name: 'billing_frequency')]
class BillingFrequency extends BillingFrequencyGenerated
{
    // Add custom properties here

    // Add custom methods here
}
