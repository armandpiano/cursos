<?php
namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Entity\Course;
use App\Domain\Repository\CourseRepositoryInterface;
use PDO;

class PdoCourseRepository implements CourseRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query('SELECT id, slug, title, description, image_url FROM courses ORDER BY title');
        $rows = $stmt->fetchAll();
        return array_map(fn ($row) => $this->hydrate($row), $rows);
    }

    public function findById(int $id): ?Course
    {
        $stmt = $this->pdo->prepare('SELECT id, slug, title, description, image_url FROM courses WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    private function hydrate(array $row): Course
    {
        return new Course(
            (int) $row['id'],
            (string) $row['slug'],
            (string) $row['title'],
            $row['description'] ?? null,
            $row['image_url'] ?? null
        );
    }
}
