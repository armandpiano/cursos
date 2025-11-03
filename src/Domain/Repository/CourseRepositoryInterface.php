<?php
namespace App\Domain\Repository;

use App\Domain\Entity\Course;

interface CourseRepositoryInterface
{
    /**
     * @return Course[]
     */
    public function findAll(): array;

    public function findById(int $id): ?Course;
}
