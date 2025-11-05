<?php
namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Entity\ModuleExamQuestion;
use App\Domain\Repository\ModuleExamQuestionRepositoryInterface;
use PDO;

class PdoModuleExamQuestionRepository implements ModuleExamQuestionRepositoryInterface
{
    /** @var PDO */
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByModule(int $moduleId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM module_exam_questions WHERE module_id = :module ORDER BY order_index');
        $stmt->execute([':module' => $moduleId]);
        $rows = $stmt->fetchAll();
        return array_map([$this, 'hydrate'], $rows);
    }

    public function countByModule(int $moduleId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM module_exam_questions WHERE module_id = :module');
        $stmt->execute([':module' => $moduleId]);
        return (int) $stmt->fetchColumn();
    }

    private function hydrate(array $row): ModuleExamQuestion
    {
        return new ModuleExamQuestion(
            (int) $row['id'],
            (int) $row['module_id'],
            (string) $row['question_text'],
            isset($row['explanation']) ? $row['explanation'] : null,
            (bool) $row['correct_answer'],
            (int) $row['order_index']
        );
    }
}
