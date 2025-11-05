<?php
namespace App\Application\DTO;

class ModuleDetailDto
{
    /** @var int */
    public $courseId;

    /** @var string */
    public $courseTitle;

    /** @var int */
    public $moduleId;

    /** @var string */
    public $moduleTitle;

    /** @var string|null */
    public $moduleDescription;

    /** @var int */
    public $orderIndex;

    /** @var CapsuleDto[] */
    public $capsules;

    /** @var ExamQuestionDto[] */
    public $questions;

    /** @var float */
    public $progressPercentage;

    /** @var bool */
    public $examAvailable;

    /** @var bool */
    public $examPassed;

    /** @var int */
    public $attempts;

    /** @var float|null */
    public $bestScore;

    /** @var float|null */
    public $lastScore;

    /** @var string|null */
    public $lastAttemptAt;

    /** @var int */
    public $capsulesCompleted;

    /** @var int */
    public $capsulesTotal;

    public function __construct(
        int $courseId,
        string $courseTitle,
        int $moduleId,
        string $moduleTitle,
        ?string $moduleDescription,
        int $orderIndex,
        array $capsules,
        array $questions,
        float $progressPercentage,
        bool $examAvailable,
        bool $examPassed,
        int $attempts,
        ?float $bestScore,
        ?float $lastScore,
        ?string $lastAttemptAt,
        int $capsulesCompleted,
        int $capsulesTotal
    ) {
        $this->courseId = $courseId;
        $this->courseTitle = $courseTitle;
        $this->moduleId = $moduleId;
        $this->moduleTitle = $moduleTitle;
        $this->moduleDescription = $moduleDescription;
        $this->orderIndex = $orderIndex;
        $this->capsules = $capsules;
        $this->questions = $questions;
        $this->progressPercentage = $progressPercentage;
        $this->examAvailable = $examAvailable;
        $this->examPassed = $examPassed;
        $this->attempts = $attempts;
        $this->bestScore = $bestScore;
        $this->lastScore = $lastScore;
        $this->lastAttemptAt = $lastAttemptAt;
        $this->capsulesCompleted = $capsulesCompleted;
        $this->capsulesTotal = $capsulesTotal;
    }
}
