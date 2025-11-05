<?php
namespace App\Application\DTO;

class ExamQuestionDto
{
    /** @var int */
    public $id;

    /** @var string */
    public $questionText;

    /** @var string|null */
    public $explanation;

    public function __construct(int $id, string $questionText, ?string $explanation)
    {
        $this->id = $id;
        $this->questionText = $questionText;
        $this->explanation = $explanation;
    }
}
