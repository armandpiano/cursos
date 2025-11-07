<?php
namespace App\Domain\Entity;

use DateTimeImmutable;

class ModuleProgress
{
    public const STATUS_LOCKED = 'locked';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';

    /**
     * @var int
     */
    private $id;

    /**
     * @var int
     */
    private $enrollmentId;

    /**
     * @var int
     */
    private $moduleId;

    /**
     * @var string
     */
    private $status;

    /**
     * @var float|null
     */
    private $bestScore;

    /**
     * @var float|null
     */
    private $lastScore;

    /**
     * @var DateTimeImmutable|null
     */
    private $lastAttemptAt;

    /**
     * @var int
     */
    private $attempts;

    /**
     * @var DateTimeImmutable|null
     */
    private $completedAt;

    public function __construct(
        int $id,
        int $enrollmentId,
        int $moduleId,
        string $status,
        ?float $bestScore,
        ?float $lastScore,
        ?DateTimeImmutable $lastAttemptAt,
        int $attempts,
        ?DateTimeImmutable $completedAt
    ) {
        $this->id = $id;
        $this->enrollmentId = $enrollmentId;
        $this->moduleId = $moduleId;
        $this->status = $status;
        $this->bestScore = $bestScore;
        $this->lastScore = $lastScore;
        $this->lastAttemptAt = $lastAttemptAt;
        $this->attempts = $attempts;
        $this->completedAt = $completedAt;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getEnrollmentId(): int
    {
        return $this->enrollmentId;
    }

    public function getModuleId(): int
    {
        return $this->moduleId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getBestScore(): ?float
    {
        return $this->bestScore;
    }

    public function getLastScore(): ?float
    {
        return $this->lastScore;
    }

    public function getLastAttemptAt(): ?DateTimeImmutable
    {
        return $this->lastAttemptAt;
    }

    public function getAttempts(): int
    {
        return $this->attempts;
    }

    public function getCompletedAt(): ?DateTimeImmutable
    {
        return $this->completedAt;
    }
}
