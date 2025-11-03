<?php
namespace App\Application\UseCase;

use App\Application\DTO\CourseModulesDto;
use App\Application\DTO\ModuleProgressDto;
use App\Domain\Entity\ModuleProgress;
use App\Domain\Repository\CourseRepositoryInterface;
use App\Domain\Repository\EnrollmentRepositoryInterface;
use App\Domain\Repository\ModuleProgressRepositoryInterface;
use App\Domain\Repository\ModuleRepositoryInterface;
use InvalidArgumentException;

class GetCourseModulesUseCase
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

    public function execute(int $userId, int $courseId): CourseModulesDto
    {
        $course = $this->courseRepository->findById($courseId);
        if (!$course) {
            throw new InvalidArgumentException('Curso no encontrado.');
        }

        $modules = $this->moduleRepository->findByCourse($courseId);
        if (empty($modules)) {
            return new CourseModulesDto(
                $course->getId(),
                $course->getTitle(),
                $course->getDescription(),
                $course->getImageUrl(),
                0.0,
                0,
                0,
                []
            );
        }

        $enrollment = $this->enrollmentRepository->findByUserAndCourse($userId, $courseId);
        if (!$enrollment) {
            $enrollment = $this->enrollmentRepository->create($userId, $courseId);
        }

        $progressRows = $this->moduleProgressRepository->findByEnrollment($enrollment->getId());
        $progressMap = [];
        foreach ($progressRows as $progress) {
            $progressMap[$progress->getModuleId()] = $progress;
        }

        $moduleDtos = [];
        $previousCompleted = true;
        $completedModules = 0;

        foreach ($modules as $index => $module) {
            $progress = $progressMap[$module->getId()] ?? null;
            if (!$progress) {
                $status = $previousCompleted ? ModuleProgress::STATUS_IN_PROGRESS : ModuleProgress::STATUS_LOCKED;
                $progress = $this->moduleProgressRepository->create($enrollment->getId(), $module->getId(), $status);
            }

            if (!$previousCompleted && $progress->getStatus() !== ModuleProgress::STATUS_LOCKED) {
                $progress = $this->moduleProgressRepository->updateStatus($progress->getId(), ModuleProgress::STATUS_LOCKED);
            }

            if ($previousCompleted && $progress->getStatus() === ModuleProgress::STATUS_LOCKED) {
                $progress = $this->moduleProgressRepository->updateStatus($progress->getId(), ModuleProgress::STATUS_IN_PROGRESS);
            }

            $isAvailable = $progress->getStatus() !== ModuleProgress::STATUS_LOCKED;
            if ($progress->getStatus() === ModuleProgress::STATUS_COMPLETED) {
                $previousCompleted = true;
                $completedModules++;
            } else {
                $previousCompleted = false;
            }

            $lastAttempt = $progress->getLastAttemptAt();
            $lastAttemptAt = $lastAttempt ? $lastAttempt->format('Y-m-d H:i') : null;

            $moduleDtos[] = new ModuleProgressDto(
                $progress->getId(),
                $module->getId(),
                $module->getTitle(),
                $module->getDescription(),
                $module->getOrderIndex(),
                $progress->getStatus(),
                $isAvailable,
                $progress->getBestScore(),
                $progress->getLastScore(),
                $lastAttemptAt,
                $progress->getAttempts(),
                $module->getPassScore(),
                $module->getMaxScore(),
                $module->getContentUrl()
            );
        }

        $totalModules = count($modules);
        $progressPercentage = $totalModules > 0 ? round(($completedModules / $totalModules) * 100, 2) : 0.0;

        return new CourseModulesDto(
            $course->getId(),
            $course->getTitle(),
            $course->getDescription(),
            $course->getImageUrl(),
            $progressPercentage,
            $completedModules,
            $totalModules,
            $moduleDtos
        );
    }
}
