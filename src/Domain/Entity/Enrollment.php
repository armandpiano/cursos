<?php
namespace App\Domain\Entity;

use DateTimeImmutable;

class Enrollment
{
    public function __construct(
        private int $id,
        private int $userId,
        private int $courseId,
        private DateTimeImmutable $enrolledAt
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getCourseId(): int
    {
        return $this->courseId;
    }

    public function getEnrolledAt(): DateTimeImmutable
    {
        return $this->enrolledAt;
    }
}
