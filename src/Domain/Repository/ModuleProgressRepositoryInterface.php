<?php
namespace App\Domain\Repository;

use App\Domain\Entity\ModuleProgress;

interface ModuleProgressRepositoryInterface
{
    /**
     * @return ModuleProgress[]
     */
    public function findByEnrollment(int $enrollmentId): array;

    public function findByEnrollmentAndModule(int $enrollmentId, int $moduleId): ?ModuleProgress;

    public function create(int $enrollmentId, int $moduleId, string $status): ModuleProgress;

    public function updateStatus(int $progressId, string $status): ModuleProgress;

    public function updateExamData(int $progressId, float $score, bool $passed): ModuleProgress;

    public function countCompleted(int $enrollmentId): int;

    public function countTotal(int $enrollmentId): int;
}
