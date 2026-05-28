<?php

namespace Hoksi\Ci4Crud\Core;

use Hoksi\Ci4Crud\Config\CrudConfig;

class InsertHandler
{
    public function __construct(private readonly CrudConfig $config) {}

    public function handle(array $data): array
    {
        // TODO: Phase 1 — CI4 Validation + DB Insert
        return ['success' => false, 'message' => 'Not implemented'];
    }
}
