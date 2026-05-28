<?php

namespace Hoksi\Ci4Crud\Relations;

class DependentRelation
{
    public function __construct(
        private readonly string $childField,
        private readonly string $parentField,
        private readonly string $table,
        private readonly string $labelField,
        private readonly string $foreignKey,
    ) {}

    public function getOptions(int|string $parentId): array
    {
        // TODO: Phase 3 — 부모 값에 따른 종속 옵션 조회
        return [];
    }
}
