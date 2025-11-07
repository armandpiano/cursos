<?php
namespace App\Application\UseCase;

use App\Application\DTO\CapsuleDto;
use App\Domain\Entity\CapsuleProgress;
use App\Domain\Entity\ModuleProgress;
use App\Domain\Repository\CapsuleProgressRepositoryInterface;
use App\Domain\Repository\CapsuleRepositoryInterface;
use App\Domain\Repository\EnrollmentRepositoryInterface;
use App\Domain\Repository\ModuleProgressRepositoryInterface;
use App\Domain\Repository\ModuleRepositoryInterface;
use InvalidArgumentException;
use RuntimeException;

class CompleteCapsuleUseCase
{
    /** @var ModuleRepositoryInterface */
    private $moduleRepository;

    /** @var CapsuleRepositoryInterface */
    private $capsuleRepository;

    /** @var EnrollmentRepositoryInterface */
    private $enrollmentRepository;

    /** @var ModuleProgressRepositoryInterface */
    private $moduleProgressRepository;

    /** @var CapsuleProgressRepositoryInterface */
    private $capsuleProgressRepository;

    public function __construct(
        ModuleRepositoryInterface $moduleRepository,
        CapsuleRepositoryInterface $capsuleRepository,
        EnrollmentRepositoryInterface $enrollmentRepository,
        ModuleProgressRepositoryInterface $moduleProgressRepository,
        CapsuleProgressRepositoryInterface $capsuleProgressRepository
    ) {
        $this->moduleRepository = $moduleRepository;
        $this->capsuleRepository = $capsuleRepository;
        $this->enrollmentRepository = $enrollmentRepository;
        $this->moduleProgressRepository = $moduleProgressRepository;
        $this->capsuleProgressRepository = $capsuleProgressRepository;
    }

    public function execute(int $userId, int $moduleId, int $capsuleId): CapsuleDto
    {
        $module = $this->moduleRepository->findById($moduleId);
        if (!$module) {
            throw new InvalidArgumentException('Módulo no encontrado.');
        }

        $capsules = $this->capsuleRepository->findByModule($moduleId);
        $targetCapsule = null;
        foreach ($capsules as $capsule) {
            if ($capsule->getId() === $capsuleId) {
                $targetCapsule = $capsule;
                break;
            }
        }

        if (!$targetCapsule) {
            throw new InvalidArgumentException('La cápsula indicada no existe.');
        }

        $enrollment = $this->enrollmentRepository->findByUserAndCourse($userId, $module->getCourseId());
        if (!$enrollment) {
            throw new RuntimeException('El usuario no está inscrito en el curso.');
        }

        $moduleProgress = $this->moduleProgressRepository->findByEnrollmentAndModule($enrollment->getId(), $moduleId);
        if (!$moduleProgress) {
            $moduleProgress = $this->moduleProgressRepository->create($enrollment->getId(), $moduleId, ModuleProgress::STATUS_IN_PROGRESS);
        }

        if ($moduleProgress->getStatus() === ModuleProgress::STATUS_LOCKED) {
            throw new RuntimeException('El módulo aún no está disponible.');
        }

        $previousCompleted = true;
        foreach ($capsules as $capsule) {
            $capsuleProgress = $this->capsuleProgressRepository->findByProgressAndCapsule($moduleProgress->getId(), $capsule->getId());
            if (!$capsuleProgress) {
                $capsuleProgress = $this->capsuleProgressRepository->createPending($moduleProgress->getId(), $capsule->getId());
            }

            if ($capsule->getId() === $targetCapsule->getId()) {
                if (!$previousCompleted) {
                    throw new RuntimeException('Debes completar las cápsulas anteriores antes de continuar.');
                }

                if ($capsuleProgress->getStatus() !== CapsuleProgress::STATUS_COMPLETED) {
                    $capsuleProgress = $this->capsuleProgressRepository->markCompleted($capsuleProgress->getId());
                }

                return new CapsuleDto(
                    $targetCapsule->getId(),
                    $targetCapsule->getTitle(),
                    $targetCapsule->getBodyHtml(),
                    $targetCapsule->getVideoUrl(),
                    $targetCapsule->getOrderIndex(),
                    $capsuleProgress->getStatus()
                );
            }

            if ($capsuleProgress->getStatus() !== CapsuleProgress::STATUS_COMPLETED) {
                $previousCompleted = false;
            }
        }

        throw new RuntimeException('No fue posible actualizar la cápsula seleccionada.');
    }
}
