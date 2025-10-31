<?php
namespace App\Domain\Entity;

use DateTimeImmutable;

class ModuleProgress
{
    public const STATUS_LOCKED = 'locked';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';

    public function __construct(
        private int $id,
        private int $enrollmentId,
        private int $moduleId,
        private string $status,
        private ?float $bestScore,
        private ?float $lastScore,
        private ?DateTimeImmutable $lastAttemptAt,
        private int $attempts,
        private ?DateTimeImmutable $completedAt
    ) {
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
