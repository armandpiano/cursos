<?php
namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Entity\Capsule;
use App\Domain\Repository\CapsuleRepositoryInterface;
use PDO;

class PdoCapsuleRepository implements CapsuleRepositoryInterface
{
    /** @var PDO */
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByModule(int $moduleId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM capsules WHERE module_id = :module ORDER BY order_index');
        $stmt->execute([':module' => $moduleId]);
        $rows = $stmt->fetchAll();
        return array_map([$this, 'hydrate'], $rows);
    }

    public function countByModule(int $moduleId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM capsules WHERE module_id = :module');
        $stmt->execute([':module' => $moduleId]);
        return (int) $stmt->fetchColumn();
    }

    private function hydrate(array $row): Capsule
    {
        return new Capsule(
            (int) $row['id'],
            (int) $row['module_id'],
            (string) $row['title'],
            isset($row['body_html']) ? $row['body_html'] : null,
            isset($row['video_url']) ? $row['video_url'] : null,
            (int) $row['order_index']
        );
    }
}
