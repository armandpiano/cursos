<?php
namespace App\Application\UseCase;

use App\Application\DTO\ModuleProgressDto;
use App\Domain\Entity\ModuleProgress;
use App\Domain\Repository\EnrollmentRepositoryInterface;
use App\Domain\Repository\ExamAttemptRepositoryInterface;
use App\Domain\Repository\ModuleProgressRepositoryInterface;
use App\Domain\Repository\ModuleRepositoryInterface;
use InvalidArgumentException;
use RuntimeException;

class RegisterExamAttemptUseCase
{
    public function __construct(
        private readonly ModuleRepositoryInterface $moduleRepository,
        private readonly EnrollmentRepositoryInterface $enrollmentRepository,
        private readonly ModuleProgressRepositoryInterface $moduleProgressRepository,
        private readonly ExamAttemptRepositoryInterface $examAttemptRepository
    ) {
    }

    public function execute(int $userId, int $moduleId, float $score, float $maxScore): ModuleProgressDto
    {
        $module = $this->moduleRepository->findById($moduleId);
        if (!$module) {
            throw new InvalidArgumentException('Módulo no encontrado.');
        }

        $enrollment = $this->enrollmentRepository->findByUserAndCourse($userId, $module->getCourseId());
        if (!$enrollment) {
            throw new RuntimeException('El usuario no está inscrito en el curso.');
        }

        $progress = $this->moduleProgressRepository->findByEnrollmentAndModule($enrollment->getId(), $moduleId);
        if (!$progress) {
            $progress = $this->moduleProgressRepository->create($enrollment->getId(), $moduleId, ModuleProgress::STATUS_IN_PROGRESS);
        }

        if ($progress->getStatus() === ModuleProgress::STATUS_LOCKED) {
            throw new RuntimeException('El módulo aún no está disponible.');
        }

        $passed = $score >= $module->getPassScore();
        $score = min($score, $maxScore);

        $this->examAttemptRepository->recordAttempt($progress->getId(), $score, $maxScore, $passed);
        $updatedProgress = $this->moduleProgressRepository->updateExamData($progress->getId(), $score, $passed);

        return new ModuleProgressDto(
            $updatedProgress->getId(),
            $module->getId(),
            $module->getTitle(),
            $module->getDescription(),
            $module->getOrderIndex(),
            $updatedProgress->getStatus(),
            $updatedProgress->getStatus() !== ModuleProgress::STATUS_LOCKED,
            $updatedProgress->getBestScore(),
            $updatedProgress->getLastScore(),
            $updatedProgress->getLastAttemptAt()?->format('Y-m-d H:i'),
            $updatedProgress->getAttempts(),
            $module->getPassScore(),
            $module->getMaxScore(),
            $module->getContentUrl()
        );
    }
}
