<?php
namespace App\Domain\Entity;

use DateTimeImmutable;

class CapsuleProgress
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';

    /**
     * @var int
     */
    private $id;

    /**
     * @var int
     */
    private $moduleProgressId;

    /**
     * @var int
     */
    private $capsuleId;

    /**
     * @var string
     */
    private $status;

    /**
     * @var DateTimeImmutable|null
     */
    private $completedAt;

    public function __construct(
        int $id,
        int $moduleProgressId,
        int $capsuleId,
        string $status,
        ?DateTimeImmutable $completedAt
    ) {
        $this->id = $id;
        $this->moduleProgressId = $moduleProgressId;
        $this->capsuleId = $capsuleId;
        $this->status = $status;
        $this->completedAt = $completedAt;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getModuleProgressId(): int
    {
        return $this->moduleProgressId;
    }

    public function getCapsuleId(): int
    {
        return $this->capsuleId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCompletedAt(): ?DateTimeImmutable
    {
        return $this->completedAt;
    }
}
