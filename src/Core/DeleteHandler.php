<?php

namespace Hoksi\Ci4Crud\Core;

use Hoksi\Ci4Crud\Config\CrudConfig;

class DeleteHandler
{
    public function __construct(private readonly CrudConfig $config) {}

    public function handle(int|string $id): array
    {
        // TODO: Phase 1 — 단건 삭제
        return ['success' => false, 'message' => 'Not implemented'];
    }

    public function handleMultiple(array $ids): array
    {
        // TODO: Phase 1 — 다중 삭제
        return ['success' => false, 'message' => 'Not implemented'];
    }
}
