<?php
namespace App\Domain\Entity;

class ModuleExamQuestion
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var int
     */
    private $moduleId;

    /**
     * @var string
     */
    private $questionText;

    /**
     * @var string|null
     */
    private $explanation;

    /**
     * @var bool
     */
    private $correctAnswer;

    /**
     * @var int
     */
    private $orderIndex;

    public function __construct(
        int $id,
        int $moduleId,
        string $questionText,
        ?string $explanation,
        bool $correctAnswer,
        int $orderIndex
    ) {
        $this->id = $id;
        $this->moduleId = $moduleId;
        $this->questionText = $questionText;
        $this->explanation = $explanation;
        $this->correctAnswer = $correctAnswer;
        $this->orderIndex = $orderIndex;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getModuleId(): int
    {
        return $this->moduleId;
    }

    public function getQuestionText(): string
    {
        return $this->questionText;
    }

    public function getExplanation(): ?string
    {
        return $this->explanation;
    }

    public function getCorrectAnswer(): bool
    {
        return $this->correctAnswer;
    }

    public function getOrderIndex(): int
    {
        return $this->orderIndex;
    }
}
