<?php
namespace App\Domain\Entity;

class Course
{
    public function __construct(
        private int $id,
        private string $slug,
        private string $title,
        private ?string $description = null,
        private ?string $imageUrl = null
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }
}
