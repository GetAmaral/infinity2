<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\TalkMessageGenerated;
use App\Repository\TalkMessageRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * TalkMessage Entity
 *
 * 
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Luminai Code Generator
 */
#[ORM\Entity(repositoryClass: TalkMessageRepository::class)]
#[ORM\Table(name: 'talk_message')]
class TalkMessage extends TalkMessageGenerated
{
    // Add custom properties here

    // Add custom methods here
}
