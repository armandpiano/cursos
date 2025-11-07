<?php
namespace App\Application\DTO;

class CourseProgressDto
{
    /** @var int */
    public $id;

    /** @var string */
    public $title;

    /** @var string|null */
    public $description;

    /** @var string|null */
    public $imageUrl;

    /** @var float */
    public $progressPercentage;

    /** @var int */
    public $completedModules;

    /** @var int */
    public $totalModules;

    public function __construct(
        int $id,
        string $title,
        ?string $description,
        ?string $imageUrl,
        float $progressPercentage,
        int $completedModules,
        int $totalModules
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->description = $description;
        $this->imageUrl = $imageUrl;
        $this->progressPercentage = $progressPercentage;
        $this->completedModules = $completedModules;
        $this->totalModules = $totalModules;
    }
}
