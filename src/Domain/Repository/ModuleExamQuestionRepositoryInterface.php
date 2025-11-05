<?php
namespace App\Domain\Repository;

use App\Domain\Entity\ModuleExamQuestion;

interface ModuleExamQuestionRepositoryInterface
{
    /**
     * @param int $moduleId
     * @return ModuleExamQuestion[]
     */
    public function findByModule(int $moduleId): array;

    public function countByModule(int $moduleId): int;
}
