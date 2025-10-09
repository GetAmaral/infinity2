<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\CampaignGenerated;
use App\Repository\CampaignRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Campaign Entity
 *
 * 
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Luminai Code Generator
 */
#[ORM\Entity(repositoryClass: CampaignRepository::class)]
#[ORM\Table(name: 'campaign')]
class Campaign extends CampaignGenerated
{
    // Add custom properties here

    // Add custom methods here
}
