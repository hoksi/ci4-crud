<?php

namespace Hoksi\Ci4Crud\Relations;

class OneToManyRelation
{
    public function __construct(
        private readonly string $foreignKey,
        private readonly string $table,
        private readonly string $labelField,
        private readonly bool   $dynamic = false,
    ) {}

    public function getOptions(string $search = ''): array
    {
        // TODO: Phase 3 — 관계 데이터 조회
        return [];
    }

    public function toSchemaArray(): array
    {
        return [
            'table'      => $this->table,
            'labelField' => $this->labelField,
            'searchable' => $this->dynamic,
        ];
    }
}
