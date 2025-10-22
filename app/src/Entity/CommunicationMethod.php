<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\CommunicationMethodGenerated;
use App\Repository\CommunicationMethodRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * CommunicationMethod Entity
 *
 * Communication methods (Phone, Email, SMS, Video Call, etc.) *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Genmax Code Generator
 */
#[ORM\Entity(repositoryClass: CommunicationMethodRepository::class)]
#[ORM\Table(name: 'communication_method')]
class CommunicationMethod extends CommunicationMethodGenerated
{
    // Add custom properties here

    // Add custom methods here
}
