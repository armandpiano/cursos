<?php
namespace App\Application\UseCase;

use App\Application\DTO\CapsuleDto;
use App\Application\DTO\ExamQuestionDto;
use App\Application\DTO\ModuleDetailDto;
use App\Domain\Entity\CapsuleProgress;
use App\Domain\Entity\ModuleProgress;
use App\Domain\Repository\CapsuleProgressRepositoryInterface;
use App\Domain\Repository\CapsuleRepositoryInterface;
use App\Domain\Repository\CourseRepositoryInterface;
use App\Domain\Repository\EnrollmentRepositoryInterface;
use App\Domain\Repository\ModuleExamQuestionRepositoryInterface;
use App\Domain\Repository\ModuleProgressRepositoryInterface;
use App\Domain\Repository\ModuleRepositoryInterface;
use InvalidArgumentException;
use RuntimeException;

class GetModuleDetailUseCase
{
    /** @var CourseRepositoryInterface */
    private $courseRepository;

    /** @var ModuleRepositoryInterface */
    private $moduleRepository;

    /** @var EnrollmentRepositoryInterface */
    private $enrollmentRepository;

    /** @var ModuleProgressRepositoryInterface */
    private $moduleProgressRepository;

    /** @var CapsuleRepositoryInterface */
    private $capsuleRepository;

    /** @var CapsuleProgressRepositoryInterface */
    private $capsuleProgressRepository;

    /** @var ModuleExamQuestionRepositoryInterface */
    private $examQuestionRepository;

    /** @var GetCourseModulesUseCase */
    private $courseModulesUseCase;

    public function __construct(
        CourseRepositoryInterface $courseRepository,
        ModuleRepositoryInterface $moduleRepository,
        EnrollmentRepositoryInterface $enrollmentRepository,
        ModuleProgressRepositoryInterface $moduleProgressRepository,
        CapsuleRepositoryInterface $capsuleRepository,
        CapsuleProgressRepositoryInterface $capsuleProgressRepository,
        ModuleExamQuestionRepositoryInterface $examQuestionRepository,
        GetCourseModulesUseCase $courseModulesUseCase
    ) {
        $this->courseRepository = $courseRepository;
        $this->moduleRepository = $moduleRepository;
        $this->enrollmentRepository = $enrollmentRepository;
        $this->moduleProgressRepository = $moduleProgressRepository;
        $this->capsuleRepository = $capsuleRepository;
        $this->capsuleProgressRepository = $capsuleProgressRepository;
        $this->examQuestionRepository = $examQuestionRepository;
        $this->courseModulesUseCase = $courseModulesUseCase;
    }

    public function execute(int $userId, int $courseId, int $moduleId): ModuleDetailDto
    {
        $course = $this->courseRepository->findById($courseId);
        if (!$course) {
            throw new InvalidArgumentException('Curso no encontrado.');
        }

        $module = $this->moduleRepository->findById($moduleId);
        if (!$module || $module->getCourseId() !== $course->getId()) {
            throw new InvalidArgumentException('Módulo no pertenece al curso.');
        }

        $courseDto = $this->courseModulesUseCase->execute($userId, $courseId);
        $moduleSnapshot = null;
        foreach ($courseDto->modules as $moduleDto) {
            if ($moduleDto->moduleId === $moduleId) {
                $moduleSnapshot = $moduleDto;
                break;
            }
        }

        if (!$moduleSnapshot) {
            throw new RuntimeException('No fue posible cargar el progreso del módulo.');
        }

        if (!$moduleSnapshot->isAvailable) {
            throw new RuntimeException('El módulo aún no está disponible. Completa primero los módulos anteriores.');
        }

        $enrollment = $this->enrollmentRepository->findByUserAndCourse($userId, $courseId);
        if (!$enrollment) {
            throw new RuntimeException('El usuario no está inscrito en el curso.');
        }

        $progress = $this->moduleProgressRepository->findByEnrollmentAndModule($enrollment->getId(), $moduleId);
        if (!$progress) {
            $progress = $this->moduleProgressRepository->create($enrollment->getId(), $moduleId, ModuleProgress::STATUS_IN_PROGRESS);
        }

        $capsules = $this->capsuleRepository->findByModule($moduleId);
        $capsuleDtos = [];
        $completedCapsules = 0;

        foreach ($capsules as $capsule) {
            $capsuleProgress = $this->capsuleProgressRepository->findByProgressAndCapsule($progress->getId(), $capsule->getId());
            if (!$capsuleProgress) {
                $capsuleProgress = $this->capsuleProgressRepository->createPending($progress->getId(), $capsule->getId());
            }

            if ($capsuleProgress->getStatus() === CapsuleProgress::STATUS_COMPLETED) {
                $completedCapsules++;
            }

            $capsuleDtos[] = new CapsuleDto(
                $capsule->getId(),
                $capsule->getTitle(),
                $capsule->getBodyHtml(),
                $capsule->getVideoUrl(),
                $capsule->getOrderIndex(),
                $capsuleProgress->getStatus()
            );
        }

        $questions = $this->examQuestionRepository->findByModule($moduleId);
        $questionDtos = [];
        foreach ($questions as $question) {
            $questionDtos[] = new ExamQuestionDto(
                $question->getId(),
                $question->getQuestionText(),
                $question->getExplanation()
            );
        }

        $progressPercentage = $moduleSnapshot->progressPercentage;
        if ($progress->getStatus() === ModuleProgress::STATUS_COMPLETED) {
            $progressPercentage = 100.0;
        }

        return new ModuleDetailDto(
            $course->getId(),
            $course->getTitle(),
            $module->getId(),
            $module->getTitle(),
            $module->getDescription(),
            $module->getOrderIndex(),
            $capsuleDtos,
            $questionDtos,
            $progressPercentage,
            $moduleSnapshot->examAvailable,
            $moduleSnapshot->examPassed,
            $progress->getAttempts(),
            $progress->getBestScore(),
            $progress->getLastScore(),
            $moduleSnapshot->lastAttemptAt,
            $moduleSnapshot->capsulesCompleted,
            $moduleSnapshot->capsulesTotal
        );
    }
}
