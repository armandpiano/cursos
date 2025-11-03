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
    /** @var ModuleRepositoryInterface */
    private $moduleRepository;

    /** @var EnrollmentRepositoryInterface */
    private $enrollmentRepository;

    /** @var ModuleProgressRepositoryInterface */
    private $moduleProgressRepository;

    /** @var ExamAttemptRepositoryInterface */
    private $examAttemptRepository;

    public function __construct(
        ModuleRepositoryInterface $moduleRepository,
        EnrollmentRepositoryInterface $enrollmentRepository,
        ModuleProgressRepositoryInterface $moduleProgressRepository,
        ExamAttemptRepositoryInterface $examAttemptRepository
    ) {
        $this->moduleRepository = $moduleRepository;
        $this->enrollmentRepository = $enrollmentRepository;
        $this->moduleProgressRepository = $moduleProgressRepository;
        $this->examAttemptRepository = $examAttemptRepository;
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

        $lastAttempt = $updatedProgress->getLastAttemptAt();
        $lastAttemptAt = $lastAttempt ? $lastAttempt->format('Y-m-d H:i') : null;

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
            $lastAttemptAt,
            $updatedProgress->getAttempts(),
            $module->getPassScore(),
            $module->getMaxScore(),
            $module->getContentUrl()
        );
    }
}
