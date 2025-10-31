<?php
namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Entity\Enrollment;
use App\Domain\Repository\EnrollmentRepositoryInterface;
use DateTimeImmutable;
use PDO;

class PdoEnrollmentRepository implements EnrollmentRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findByUserAndCourse(int $userId, int $courseId): ?Enrollment
    {
        $stmt = $this->pdo->prepare('SELECT id, user_id, course_id, enrolled_at FROM enrollments WHERE user_id = :user AND course_id = :course LIMIT 1');
        $stmt->execute([':user' => $userId, ':course' => $courseId]);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    public function create(int $userId, int $courseId): Enrollment
    {
        $stmt = $this->pdo->prepare('INSERT INTO enrollments (user_id, course_id, enrolled_at) VALUES (:user, :course, NOW())');
        $stmt->execute([':user' => $userId, ':course' => $courseId]);
        $id = (int) $this->pdo->lastInsertId();

        $stmt = $this->pdo->prepare('SELECT id, user_id, course_id, enrolled_at FROM enrollments WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $this->hydrate($row ?: ['id' => $id, 'user_id' => $userId, 'course_id' => $courseId, 'enrolled_at' => date('Y-m-d H:i:s')]);
    }

    private function hydrate(array $row): Enrollment
    {
        return new Enrollment(
            (int) $row['id'],
            (int) $row['user_id'],
            (int) $row['course_id'],
            new DateTimeImmutable($row['enrolled_at'])
        );
    }
}
