<?php
namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Entity\CapsuleProgress;
use App\Domain\Repository\CapsuleProgressRepositoryInterface;
use DateTimeImmutable;
use PDO;

class PdoCapsuleProgressRepository implements CapsuleProgressRepositoryInterface
{
    /** @var PDO */
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByModuleProgress(int $moduleProgressId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM capsule_progress WHERE module_progress_id = :progress');
        $stmt->execute([':progress' => $moduleProgressId]);
        $rows = $stmt->fetchAll();
        return array_map([$this, 'hydrate'], $rows);
    }

    public function findByProgressAndCapsule(int $moduleProgressId, int $capsuleId): ?CapsuleProgress
    {
        $stmt = $this->pdo->prepare('SELECT * FROM capsule_progress WHERE module_progress_id = :progress AND capsule_id = :capsule LIMIT 1');
        $stmt->execute([':progress' => $moduleProgressId, ':capsule' => $capsuleId]);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    public function createPending(int $moduleProgressId, int $capsuleId): CapsuleProgress
    {
        $stmt = $this->pdo->prepare('INSERT INTO capsule_progress (module_progress_id, capsule_id, status, created_at, updated_at) VALUES (:progress, :capsule, :status, NOW(), NOW())');
        $stmt->execute([
            ':progress' => $moduleProgressId,
            ':capsule' => $capsuleId,
            ':status' => CapsuleProgress::STATUS_PENDING,
        ]);
        $id = (int) $this->pdo->lastInsertId();
        return $this->getById($id);
    }

    public function markCompleted(int $progressId): CapsuleProgress
    {
        $stmt = $this->pdo->prepare('UPDATE capsule_progress SET status = :status, completed_at = NOW(), updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            ':status' => CapsuleProgress::STATUS_COMPLETED,
            ':id' => $progressId,
        ]);
        return $this->getById($progressId);
    }

    public function countCompleted(int $moduleProgressId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM capsule_progress WHERE module_progress_id = :progress AND status = :status');
        $stmt->execute([
            ':progress' => $moduleProgressId,
            ':status' => CapsuleProgress::STATUS_COMPLETED,
        ]);
        return (int) $stmt->fetchColumn();
    }

    private function getById(int $id): CapsuleProgress
    {
        $stmt = $this->pdo->prepare('SELECT * FROM capsule_progress WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $this->hydrate($row);
    }

    private function hydrate(array $row): CapsuleProgress
    {
        return new CapsuleProgress(
            (int) $row['id'],
            (int) $row['module_progress_id'],
            (int) $row['capsule_id'],
            (string) $row['status'],
            isset($row['completed_at']) && $row['completed_at'] ? new DateTimeImmutable($row['completed_at']) : null
        );
    }
}
