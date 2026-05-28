<?php

namespace Hoksi\Ci4Crud\Core;

class ActionManager
{
    private const VALID_ACTIONS = [
        'schema', 'list', 'read', 'insert', 'update',
        'delete', 'delete_multiple', 'relation', 'export',
    ];

    public function detect(): string
    {
        $action = $_GET['action'] ?? 'list';

        return in_array($action, self::VALID_ACTIONS, true) ? $action : 'list';
    }

    public function getMethod(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function getId(): int|string|null
    {
        return $_GET['id'] ?? null;
    }
}
