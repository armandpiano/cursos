<?php
namespace App\Domain\Entity;

class Module
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var int
     */
    private $courseId;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string|null
     */
    private $description;

    /**
     * @var int
     */
    private $orderIndex;

    /**
     * @var string|null
     */
    private $contentUrl;

    /**
     * @var int
     */
    private $passScore;

    /**
     * @var int
     */
    private $maxScore;

    public function __construct(
        int $id,
        int $courseId,
        string $title,
        ?string $description,
        int $orderIndex,
        ?string $contentUrl,
        int $passScore,
        int $maxScore
    ) {
        $this->id = $id;
        $this->courseId = $courseId;
        $this->title = $title;
        $this->description = $description;
        $this->orderIndex = $orderIndex;
        $this->contentUrl = $contentUrl;
        $this->passScore = $passScore;
        $this->maxScore = $maxScore;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCourseId(): int
    {
        return $this->courseId;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getOrderIndex(): int
    {
        return $this->orderIndex;
    }

    public function getContentUrl(): ?string
    {
        return $this->contentUrl;
    }

    public function getPassScore(): int
    {
        return $this->passScore;
    }

    public function getMaxScore(): int
    {
        return $this->maxScore;
    }
}
