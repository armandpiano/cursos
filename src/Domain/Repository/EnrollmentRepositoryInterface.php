<?php
namespace App\Domain\Repository;

use App\Domain\Entity\Enrollment;

interface EnrollmentRepositoryInterface
{
    public function findByUserAndCourse(int $userId, int $courseId): ?Enrollment;

    public function create(int $userId, int $courseId): Enrollment;
}
