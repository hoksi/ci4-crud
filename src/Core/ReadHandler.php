<?php

namespace Hoksi\Ci4Crud\Core;

use Hoksi\Ci4Crud\Config\CrudConfig;

class ReadHandler
{
    public function __construct(private readonly CrudConfig $config) {}

    public function handle(int|string $id): array
    {
        // TODO: Phase 1 — 단건 조회
        return ['success' => false, 'message' => 'Not implemented'];
    }
}
