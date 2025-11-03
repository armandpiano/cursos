<?php
namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Entity\Module;
use App\Domain\Repository\ModuleRepositoryInterface;
use PDO;

class PdoModuleRepository implements ModuleRepositoryInterface
{
    /** @var PDO */
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByCourse(int $courseId): array
    {
        $stmt = $this->pdo->prepare('SELECT id, course_id, title, description, order_index, content_url, pass_score, max_score FROM modules WHERE course_id = :course ORDER BY order_index');
        $stmt->execute([':course' => $courseId]);
        $rows = $stmt->fetchAll();

        return array_map(function($row) {
            return $this->hydrate($row);
        }, $rows);
    }

    public function countByCourse(int $courseId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM modules WHERE course_id = :course');
        $stmt->execute([':course' => $courseId]);
        return (int)$stmt->fetchColumn();
    }

    public function findById(int $id): ?Module
    {
        $stmt = $this->pdo->prepare('SELECT id, course_id, title, description, order_index, content_url, pass_score, max_score FROM modules WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    private function hydrate(array $row): Module
    {
        return new Module(
            (int)$row['id'],
            (int)$row['course_id'],
            (string)$row['title'],
            $row['description'] ?? null,
            (int)$row['order_index'],
            $row['content_url'] ?? null,
            (int)$row['pass_score'],
            (int)$row['max_score']
        );
    }
}
