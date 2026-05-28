<?php

namespace Hoksi\Ci4Crud\Core;

class ActionManager
{
    private static array $validActions = [
        'schema', 'list', 'read', 'insert', 'update',
        'delete', 'delete_multiple', 'relation', 'export',
    ];

    public function detect(): string
    {
        // TODO: Phase 1 — $_GET['action'] 파라미터 감지
        $action = $_GET['action'] ?? 'list';

        return in_array($action, self::$validActions, true) ? $action : 'list';
    }
}
