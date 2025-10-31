<?php
namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Entity\ModuleProgress;
use App\Domain\Repository\ModuleProgressRepositoryInterface;
use DateTimeImmutable;
use PDO;

class PdoModuleProgressRepository implements ModuleProgressRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findByEnrollment(int $enrollmentId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM module_progress WHERE enrollment_id = :enrollment');
        $stmt->execute([':enrollment' => $enrollmentId]);
        $rows = $stmt->fetchAll();
        return array_map(fn ($row) => $this->hydrate($row), $rows);
    }

    public function findByEnrollmentAndModule(int $enrollmentId, int $moduleId): ?ModuleProgress
    {
        $stmt = $this->pdo->prepare('SELECT * FROM module_progress WHERE enrollment_id = :enrollment AND module_id = :module LIMIT 1');
        $stmt->execute([':enrollment' => $enrollmentId, ':module' => $moduleId]);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    public function create(int $enrollmentId, int $moduleId, string $status): ModuleProgress
    {
        $stmt = $this->pdo->prepare('INSERT INTO module_progress (enrollment_id, module_id, status, created_at, updated_at) VALUES (:enrollment, :module, :status, NOW(), NOW())');
        $stmt->execute([':enrollment' => $enrollmentId, ':module' => $moduleId, ':status' => $status]);
        $id = (int) $this->pdo->lastInsertId();
        return $this->getById($id);
    }

    public function updateStatus(int $progressId, string $status): ModuleProgress
    {
        $stmt = $this->pdo->prepare('UPDATE module_progress SET status = :status, updated_at = NOW() WHERE id = :id');
        $stmt->execute([':status' => $status, ':id' => $progressId]);
        return $this->getById($progressId);
    }

    public function updateExamData(int $progressId, float $score, bool $passed): ModuleProgress
    {
        $set = 'last_score = :score, last_attempt_at = NOW(), attempts = attempts + 1, updated_at = NOW()';
        $params = [':score' => $score, ':id' => $progressId];
        if ($passed) {
            $set .= ', best_score = GREATEST(COALESCE(best_score, 0), :score), completed_at = COALESCE(completed_at, NOW()), status = :status';
            $params[':status'] = ModuleProgress::STATUS_COMPLETED;
        } else {
            $set .= ', best_score = GREATEST(COALESCE(best_score, 0), :score)';
        }

        $stmt = $this->pdo->prepare("UPDATE module_progress SET $set WHERE id = :id");
        $stmt->execute($params);
        return $this->getById($progressId);
    }

    public function countCompleted(int $enrollmentId): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM module_progress WHERE enrollment_id = :enrollment AND status = :status");
        $stmt->execute([':enrollment' => $enrollmentId, ':status' => ModuleProgress::STATUS_COMPLETED]);
        return (int) $stmt->fetchColumn();
    }

    public function countTotal(int $enrollmentId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM module_progress WHERE enrollment_id = :enrollment');
        $stmt->execute([':enrollment' => $enrollmentId]);
        return (int) $stmt->fetchColumn();
    }

    private function getById(int $id): ModuleProgress
    {
        $stmt = $this->pdo->prepare('SELECT * FROM module_progress WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $this->hydrate($row);
    }

    private function hydrate(array $row): ModuleProgress
    {
        return new ModuleProgress(
            (int) $row['id'],
            (int) $row['enrollment_id'],
            (int) $row['module_id'],
            (string) $row['status'],
            isset($row['best_score']) ? (float) $row['best_score'] : null,
            isset($row['last_score']) ? (float) $row['last_score'] : null,
            isset($row['last_attempt_at']) && $row['last_attempt_at'] ? new DateTimeImmutable($row['last_attempt_at']) : null,
            (int) ($row['attempts'] ?? 0),
            isset($row['completed_at']) && $row['completed_at'] ? new DateTimeImmutable($row['completed_at']) : null
        );
    }
}
