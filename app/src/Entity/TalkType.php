<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\TalkTypeGenerated;
use App\Repository\TalkTypeRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * TalkType Entity
 *
 * Communication types for the organization *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Genmax Code Generator
 */
#[ORM\Entity(repositoryClass: TalkTypeRepository::class)]
#[ORM\Table(name: 'talk_type')]
class TalkType extends TalkTypeGenerated
{
    // Add custom properties here

    // Add custom methods here
}
