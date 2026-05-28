<?php

namespace Hoksi\Ci4Crud\Core;

use Hoksi\Ci4Crud\Config\CrudConfig;

class QueryHandler
{
    public function __construct(private readonly CrudConfig $config) {}

    public function list(): array
    {
        // TODO: Phase 1 — 페이지네이션·정렬·필터 처리
        return ['last_page' => 0, 'data' => []];
    }
}
