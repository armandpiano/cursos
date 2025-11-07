<?php
namespace App\Domain\Entity;

class Capsule
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
    private $title;

    /**
     * @var string|null
     */
    private $bodyHtml;

    /**
     * @var string|null
     */
    private $videoUrl;

    /**
     * @var int
     */
    private $orderIndex;

    public function __construct(
        int $id,
        int $moduleId,
        string $title,
        ?string $bodyHtml,
        ?string $videoUrl,
        int $orderIndex
    ) {
        $this->id = $id;
        $this->moduleId = $moduleId;
        $this->title = $title;
        $this->bodyHtml = $bodyHtml;
        $this->videoUrl = $videoUrl;
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

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getBodyHtml(): ?string
    {
        return $this->bodyHtml;
    }

    public function getVideoUrl(): ?string
    {
        return $this->videoUrl;
    }

    public function getOrderIndex(): int
    {
        return $this->orderIndex;
    }
}
