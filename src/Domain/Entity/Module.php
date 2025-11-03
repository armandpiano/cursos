<?php
namespace App\Domain\Entity;

class Module
{
    public function __construct(
        private int $id,
        private int $courseId,
        private string $title,
        private ?string $description,
        private int $orderIndex,
        private ?string $contentUrl,
        private int $passScore,
        private int $maxScore
    ) {
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
