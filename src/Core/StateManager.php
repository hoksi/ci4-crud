<?php

namespace Hoksi\Ci4Crud\Core;

class StateManager
{
    private static array $validStates = [
        'list', 'ajax_list', 'add', 'insert', 'edit',
        'update', 'delete', 'read', 'clone', 'export',
    ];

    public function detect(): string
    {
        // TODO: Phase 1 — ?state= URL 파라미터 감지
        $state = $_GET['state'] ?? 'list';

        return in_array($state, self::$validStates, true) ? $state : 'list';
    }
}
