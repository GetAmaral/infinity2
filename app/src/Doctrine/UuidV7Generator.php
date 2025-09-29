<?php
declare(strict_types=1);

namespace App\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Id\AbstractIdGenerator;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV7;

final class UuidV7Generator extends AbstractIdGenerator
{
    public function generateId(EntityManagerInterface $em, $entity): UuidV7
    {
        return Uuid::v7();
    }

    public function isPostInsertGenerator(): bool
    {
        return false;
    }
}