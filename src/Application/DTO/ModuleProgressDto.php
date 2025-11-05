<?php
namespace App\Application\DTO;

class ModuleProgressDto
{
    /** @var int */
    public $id;

    /** @var int */
    public $moduleId;

    /** @var string */
    public $title;

    /** @var string|null */
    public $description;

    /** @var int */
    public $orderIndex;

    /** @var string */
    public $status;

    /** @var bool */
    public $isAvailable;

    /** @var float|null */
    public $bestScore;

    /** @var float|null */
    public $lastScore;

    /** @var string|null */
    public $lastAttemptAt;

    /** @var int */
    public $attempts;

    /** @var int */
    public $passScore;

    /** @var int */
    public $maxScore;

    /** @var string|null */
    public $contentUrl;

    /** @var int */
    public $capsulesCompleted;

    /** @var int */
    public $capsulesTotal;

    /** @var float */
    public $progressPercentage;

    /** @var bool */
    public $examAvailable;

    /** @var bool */
    public $examPassed;

    public function __construct(
        int $id,
        int $moduleId,
        string $title,
        ?string $description,
        int $orderIndex,
        string $status,
        bool $isAvailable,
        ?float $bestScore,
        ?float $lastScore,
        ?string $lastAttemptAt,
        int $attempts,
        int $passScore,
        int $maxScore,
        ?string $contentUrl,
        int $capsulesCompleted,
        int $capsulesTotal,
        float $progressPercentage,
        bool $examAvailable,
        bool $examPassed
    ) {
        $this->id = $id;
        $this->moduleId = $moduleId;
        $this->title = $title;
        $this->description = $description;
        $this->orderIndex = $orderIndex;
        $this->status = $status;
        $this->isAvailable = $isAvailable;
        $this->bestScore = $bestScore;
        $this->lastScore = $lastScore;
        $this->lastAttemptAt = $lastAttemptAt;
        $this->attempts = $attempts;
        $this->passScore = $passScore;
        $this->maxScore = $maxScore;
        $this->contentUrl = $contentUrl;
        $this->capsulesCompleted = $capsulesCompleted;
        $this->capsulesTotal = $capsulesTotal;
        $this->progressPercentage = $progressPercentage;
        $this->examAvailable = $examAvailable;
        $this->examPassed = $examPassed;
    }
}
