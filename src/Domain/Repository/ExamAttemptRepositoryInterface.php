<?php
namespace App\Domain\Repository;

use App\Domain\Entity\ExamAttempt;

interface ExamAttemptRepositoryInterface
{
    public function recordAttempt(int $moduleProgressId, float $score, float $maxScore, bool $passed): ExamAttempt;

    public function findLatest(int $moduleProgressId): ?ExamAttempt;

    public function recordAnswer(int $attemptId, int $questionId, bool $selectedAnswer, bool $isCorrect): void;
}
