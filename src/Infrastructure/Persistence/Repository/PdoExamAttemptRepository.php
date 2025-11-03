<?php
namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Entity\ExamAttempt;
use App\Domain\Repository\ExamAttemptRepositoryInterface;
use DateTimeImmutable;
use PDO;

class PdoExamAttemptRepository implements ExamAttemptRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function recordAttempt(int $moduleProgressId, float $score, float $maxScore, bool $passed): ExamAttempt
    {
        $stmt = $this->pdo->prepare('INSERT INTO exam_attempts (module_progress_id, score, max_score, passed, taken_at, created_at) VALUES (:progress, :score, :max, :passed, NOW(), NOW())');
        $stmt->execute([
            ':progress' => $moduleProgressId,
            ':score' => $score,
            ':max' => $maxScore,
            ':passed' => $passed ? 1 : 0,
        ]);
        $id = (int) $this->pdo->lastInsertId();
        return $this->getById($id);
    }

    public function findLatest(int $moduleProgressId): ?ExamAttempt
    {
        $stmt = $this->pdo->prepare('SELECT * FROM exam_attempts WHERE module_progress_id = :progress ORDER BY taken_at DESC, id DESC LIMIT 1');
        $stmt->execute([':progress' => $moduleProgressId]);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    private function getById(int $id): ExamAttempt
    {
        $stmt = $this->pdo->prepare('SELECT * FROM exam_attempts WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $this->hydrate($row);
    }

    private function hydrate(array $row): ExamAttempt
    {
        return new ExamAttempt(
            (int) $row['id'],
            (int) $row['module_progress_id'],
            (float) $row['score'],
            (float) $row['max_score'],
            (bool) $row['passed'],
            new DateTimeImmutable($row['taken_at'])
        );
    }
}
