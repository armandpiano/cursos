<?php
namespace App\Domain\Entity;

use DateTimeImmutable;

class Enrollment
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var int
     */
    private $userId;

    /**
     * @var int
     */
    private $courseId;

    /**
     * @var DateTimeImmutable
     */
    private $enrolledAt;

    public function __construct(int $id, int $userId, int $courseId, DateTimeImmutable $enrolledAt)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->courseId = $courseId;
        $this->enrolledAt = $enrolledAt;
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
