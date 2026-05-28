<?php

namespace Hoksi\Ci4Crud\Core;

use Hoksi\Ci4Crud\Config\CrudConfig;

class UpdateHandler
{
    public function __construct(private readonly CrudConfig $config) {}

    public function handle(int|string $id, array $data): array
    {
        // TODO: Phase 1 — CI4 Validation + DB Update
        return ['success' => false, 'message' => 'Not implemented'];
    }
}
