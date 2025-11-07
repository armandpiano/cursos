<?php
namespace App\Domain\Entity;

use DateTimeImmutable;

class ExamAttempt
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var int
     */
    private $moduleProgressId;

    /**
     * @var float
     */
    private $score;

    /**
     * @var float
     */
    private $maxScore;

    /**
     * @var bool
     */
    private $passed;

    /**
     * @var DateTimeImmutable
     */
    private $takenAt;

    public function __construct(
        int $id,
        int $moduleProgressId,
        float $score,
        float $maxScore,
        bool $passed,
        DateTimeImmutable $takenAt
    ) {
        $this->id = $id;
        $this->moduleProgressId = $moduleProgressId;
        $this->score = $score;
        $this->maxScore = $maxScore;
        $this->passed = $passed;
        $this->takenAt = $takenAt;
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
