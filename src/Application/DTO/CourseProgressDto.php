<?php
namespace App\Application\DTO;

class CourseProgressDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly ?string $description,
        public readonly ?string $imageUrl,
        public readonly float $progressPercentage,
        public readonly int $completedModules,
        public readonly int $totalModules
    ) {
    }
}
