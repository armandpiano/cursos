<?php
namespace App\Domain\Entity;

use DateTimeImmutable;

class ExamAttempt
{
    public function __construct(
        private int $id,
        private int $moduleProgressId,
        private float $score,
        private float $maxScore,
        private bool $passed,
        private DateTimeImmutable $takenAt
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getModuleProgressId(): int
    {
        return $this->moduleProgressId;
    }

    public function getScore(): float
    {
        return $this->score;
    }

    public function getMaxScore(): float
    {
        return $this->maxScore;
    }

    public function isPassed(): bool
    {
        return $this->passed;
    }

    public function getTakenAt(): DateTimeImmutable
    {
        return $this->takenAt;
    }
}
