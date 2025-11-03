<?php
namespace App\Application\DTO;

class ModuleProgressDto
{
    public function __construct(
        public readonly int $id,
        public readonly int $moduleId,
        public readonly string $title,
        public readonly ?string $description,
        public readonly int $orderIndex,
        public readonly string $status,
        public readonly bool $isAvailable,
        public readonly ?float $bestScore,
        public readonly ?float $lastScore,
        public readonly ?string $lastAttemptAt,
        public readonly int $attempts,
        public readonly int $passScore,
        public readonly int $maxScore,
        public readonly ?string $contentUrl
    ) {
    }
}
