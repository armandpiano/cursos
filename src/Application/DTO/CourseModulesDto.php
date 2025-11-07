<?php
namespace App\Application\DTO;

class CourseModulesDto
{
    /** @var int */
    public $courseId;

    /** @var string */
    public $courseTitle;

    /** @var string|null */
    public $courseDescription;

    /** @var string|null */
    public $courseImageUrl;

    /** @var float */
    public $progressPercentage;

    /** @var int */
    public $completedModules;

    /** @var int */
    public $totalModules;

    /**
     * @var ModuleProgressDto[]
     */
    public $modules;

    /**
     * @param ModuleProgressDto[] $modules
     */
    public function __construct(
        int $courseId,
        string $courseTitle,
        ?string $courseDescription,
        ?string $courseImageUrl,
        float $progressPercentage,
        int $completedModules,
        int $totalModules,
        array $modules
    ) {
        $this->courseId = $courseId;
        $this->courseTitle = $courseTitle;
        $this->courseDescription = $courseDescription;
        $this->courseImageUrl = $courseImageUrl;
        $this->progressPercentage = $progressPercentage;
        $this->completedModules = $completedModules;
        $this->totalModules = $totalModules;
        $this->modules = $modules;
    }
}
