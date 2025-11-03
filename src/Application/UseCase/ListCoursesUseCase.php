<?php
namespace App\Application\UseCase;

use App\Application\DTO\CourseProgressDto;
use App\Domain\Repository\CourseRepositoryInterface;
use App\Domain\Repository\EnrollmentRepositoryInterface;
use App\Domain\Repository\ModuleProgressRepositoryInterface;
use App\Domain\Repository\ModuleRepositoryInterface;

class ListCoursesUseCase
{
    public function __construct(
        private readonly CourseRepositoryInterface $courseRepository,
        private readonly ModuleRepositoryInterface $moduleRepository,
        private readonly EnrollmentRepositoryInterface $enrollmentRepository,
        private readonly ModuleProgressRepositoryInterface $moduleProgressRepository
    ) {
    }

    /**
     * @return CourseProgressDto[]
     */
    public function execute(int $userId): array
    {
        $courses = $this->courseRepository->findAll();
        $result = [];

        foreach ($courses as $course) {
            $totalModules = $this->moduleRepository->countByCourse($course->getId());
            $completedModules = 0;
            $progressPercentage = 0.0;

            $enrollment = $this->enrollmentRepository->findByUserAndCourse($userId, $course->getId());
            if ($enrollment && $totalModules > 0) {
                $completedModules = $this->moduleProgressRepository->countCompleted($enrollment->getId());
                $progressPercentage = round(($completedModules / $totalModules) * 100, 2);
            }

            $result[] = new CourseProgressDto(
                $course->getId(),
                $course->getTitle(),
                $course->getDescription(),
                $course->getImageUrl(),
                $progressPercentage,
                $completedModules,
                $totalModules
            );
        }

        return $result;
    }
}
