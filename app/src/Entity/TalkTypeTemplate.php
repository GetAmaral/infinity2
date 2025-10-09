<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\TalkTypeTemplateGenerated;
use App\Repository\TalkTypeTemplateRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * TalkTypeTemplate Entity
 *
 * 
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Luminai Code Generator
 */
#[ORM\Entity(repositoryClass: TalkTypeTemplateRepository::class)]
#[ORM\Table(name: 'talk_type_template')]
class TalkTypeTemplate extends TalkTypeTemplateGenerated
{
    // Add custom properties here

    // Add custom methods here
}
