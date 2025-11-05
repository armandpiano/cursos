<?php
namespace App\Application\DTO;

class CapsuleDto
{
    /** @var int */
    public $id;

    /** @var string */
    public $title;

    /** @var string|null */
    public $bodyHtml;

    /** @var string|null */
    public $videoUrl;

    /** @var int */
    public $orderIndex;

    /** @var string */
    public $status;

    public function __construct(
        int $id,
        string $title,
        ?string $bodyHtml,
        ?string $videoUrl,
        int $orderIndex,
        string $status
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->bodyHtml = $bodyHtml;
        $this->videoUrl = $videoUrl;
        $this->orderIndex = $orderIndex;
        $this->status = $status;
    }
}
