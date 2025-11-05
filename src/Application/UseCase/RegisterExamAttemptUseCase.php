<?php
namespace App\Application\UseCase;

use App\Application\DTO\ModuleProgressDto;
use App\Domain\Entity\CapsuleProgress;
use App\Domain\Entity\ModuleProgress;
use App\Domain\Repository\CapsuleProgressRepositoryInterface;
use App\Domain\Repository\CapsuleRepositoryInterface;
use App\Domain\Repository\EnrollmentRepositoryInterface;
use App\Domain\Repository\ExamAttemptRepositoryInterface;
use App\Domain\Repository\ModuleExamQuestionRepositoryInterface;
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

    /** @var CapsuleRepositoryInterface */
    private $capsuleRepository;

    /** @var CapsuleProgressRepositoryInterface */
    private $capsuleProgressRepository;

    /** @var ModuleExamQuestionRepositoryInterface */
    private $examQuestionRepository;

    public function __construct(
        ModuleRepositoryInterface $moduleRepository,
        EnrollmentRepositoryInterface $enrollmentRepository,
        ModuleProgressRepositoryInterface $moduleProgressRepository,
        ExamAttemptRepositoryInterface $examAttemptRepository,
        CapsuleRepositoryInterface $capsuleRepository,
        CapsuleProgressRepositoryInterface $capsuleProgressRepository,
        ModuleExamQuestionRepositoryInterface $examQuestionRepository
    ) {
        $this->moduleRepository = $moduleRepository;
        $this->enrollmentRepository = $enrollmentRepository;
        $this->moduleProgressRepository = $moduleProgressRepository;
        $this->examAttemptRepository = $examAttemptRepository;
        $this->capsuleRepository = $capsuleRepository;
        $this->capsuleProgressRepository = $capsuleProgressRepository;
        $this->examQuestionRepository = $examQuestionRepository;
    }

    /**
     * @param array<int,bool> $answers
     */
    public function execute(int $userId, int $moduleId, array $answers): ModuleProgressDto
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

        $capsules = $this->capsuleRepository->findByModule($moduleId);
        $capsuleCount = count($capsules);
        $completedCapsules = 0;
        foreach ($capsules as $capsule) {
            $capsuleProgress = $this->capsuleProgressRepository->findByProgressAndCapsule($progress->getId(), $capsule->getId());
            if (!$capsuleProgress) {
                $capsuleProgress = $this->capsuleProgressRepository->createPending($progress->getId(), $capsule->getId());
            }
            if ($capsuleProgress->getStatus() === CapsuleProgress::STATUS_COMPLETED) {
                $completedCapsules++;
            }
        }

        if ($capsuleCount > 0 && $completedCapsules !== $capsuleCount) {
            throw new RuntimeException('Debes concluir todas las cápsulas antes de presentar la evaluación.');
        }

        $questions = $this->examQuestionRepository->findByModule($moduleId);
        if (empty($questions)) {
            throw new RuntimeException('Este módulo aún no tiene evaluación configurada.');
        }

        $correct = 0;
        $total = count($questions);

        foreach ($questions as $question) {
            $selected = isset($answers[$question->getId()]) ? (bool) $answers[$question->getId()] : false;
            $isCorrect = $selected === $question->getCorrectAnswer();
            if ($isCorrect) {
                $correct++;
            }
        }

        $maxScore = (float) $module->getMaxScore();
        if ($maxScore <= 0) {
            $maxScore = 100.0;
        }

        $score = 0.0;
        if ($total > 0) {
            $score = round(($correct / $total) * $maxScore, 2);
        }

        $passed = $score >= $module->getPassScore();

        $attempt = $this->examAttemptRepository->recordAttempt($progress->getId(), $score, $maxScore, $passed);

        foreach ($questions as $question) {
            $selected = isset($answers[$question->getId()]) ? (bool) $answers[$question->getId()] : false;
            $isCorrect = $selected === $question->getCorrectAnswer();
            $this->examAttemptRepository->recordAnswer($attempt->getId(), $question->getId(), $selected, $isCorrect);
        }

        $updatedProgress = $this->moduleProgressRepository->updateExamData($progress->getId(), $score, $passed);

        $lastAttempt = $updatedProgress->getLastAttemptAt();
        $lastAttemptAt = $lastAttempt ? $lastAttempt->format('Y-m-d H:i') : null;

        $progressPercentage = 0.0;
        if ($capsuleCount > 0) {
            $progressPercentage = round(($completedCapsules / $capsuleCount) * 100, 2);
        }
        if ($updatedProgress->getStatus() === ModuleProgress::STATUS_COMPLETED) {
            $progressPercentage = 100.0;
        }

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
            $module->getContentUrl(),
            $completedCapsules,
            $capsuleCount,
            $progressPercentage,
            true,
            $updatedProgress->getStatus() === ModuleProgress::STATUS_COMPLETED
        );
    }
}
