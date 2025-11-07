<?php
namespace App\Domain\Repository;

use App\Domain\Entity\Capsule;

interface CapsuleRepositoryInterface
{
    /**
     * @param int $moduleId
     * @return Capsule[]
     */
    public function findByModule(int $moduleId): array;

    public function countByModule(int $moduleId): int;
}
