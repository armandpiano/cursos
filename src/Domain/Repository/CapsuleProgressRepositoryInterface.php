<?php
namespace App\Domain\Repository;

use App\Domain\Entity\CapsuleProgress;

interface CapsuleProgressRepositoryInterface
{
    /**
     * @param int $moduleProgressId
     * @return CapsuleProgress[]
     */
    public function findByModuleProgress(int $moduleProgressId): array;

    public function findByProgressAndCapsule(int $moduleProgressId, int $capsuleId): ?CapsuleProgress;

    public function createPending(int $moduleProgressId, int $capsuleId): CapsuleProgress;

    public function markCompleted(int $progressId): CapsuleProgress;

    public function countCompleted(int $moduleProgressId): int;
}
