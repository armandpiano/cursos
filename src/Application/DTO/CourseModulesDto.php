<?php
namespace App\Application\DTO;

class CourseModulesDto
{
    /**
     * @param ModuleProgressDto[] $modules
     */
    public function __construct(
        public readonly int $courseId,
        public readonly string $courseTitle,
        public readonly ?string $courseDescription,
        public readonly ?string $courseImageUrl,
        public readonly float $progressPercentage,
        public readonly int $completedModules,
        public readonly int $totalModules,
        public readonly array $modules
    ) {
    }
}
