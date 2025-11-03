<?php
namespace App\Application\UseCase;

use App\Application\DTO\CourseProgressDto;
use App\Domain\Repository\CourseRepositoryInterface;
use App\Domain\Repository\EnrollmentRepositoryInterface;
use App\Domain\Repository\ModuleProgressRepositoryInterface;
use App\Domain\Repository\ModuleRepositoryInterface;

class ListCoursesUseCase
{
    /** @var CourseRepositoryInterface */
    private $courseRepository;

    /** @var ModuleRepositoryInterface */
    private $moduleRepository;

    /** @var EnrollmentRepositoryInterface */
    private $enrollmentRepository;

    /** @var ModuleProgressRepositoryInterface */
    private $moduleProgressRepository;

    public function __construct(
        CourseRepositoryInterface $courseRepository,
        ModuleRepositoryInterface $moduleRepository,
        EnrollmentRepositoryInterface $enrollmentRepository,
        ModuleProgressRepositoryInterface $moduleProgressRepository
    ) {
        $this->courseRepository = $courseRepository;
        $this->moduleRepository = $moduleRepository;
        $this->enrollmentRepository = $enrollmentRepository;
        $this->moduleProgressRepository = $moduleProgressRepository;
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
