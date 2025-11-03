<?php
namespace App\Domain\Repository;

use App\Domain\Entity\Module;

interface ModuleRepositoryInterface
{
    /**
     * @return Module[]
     */
    public function findByCourse(int $courseId): array;

    public function countByCourse(int $courseId): int;

    public function findById(int $id): ?Module;
}
