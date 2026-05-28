<?php

namespace Hoksi\Ci4Crud\Relations;

class ManyToManyRelation
{
    public function __construct(
        private readonly string $field,
        private readonly string $junctionTable,
        private readonly string $relatedTable,
        private readonly string $junctionFk,
        private readonly string $relatedFk,
        private readonly string $labelField,
    ) {}

    public function getOptions(string $search = ''): array
    {
        // TODO: Phase 3 — N:N 관계 데이터 조회
        return [];
    }

    public function syncJunction(int|string $primaryId, array $relatedIds): void
    {
        // TODO: Phase 3 — Junction 테이블 동기화 (트랜잭션)
    }
}
