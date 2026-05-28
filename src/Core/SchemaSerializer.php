<?php

namespace Hoksi\Ci4Crud\Core;

use Hoksi\Ci4Crud\Config\CrudConfig;

class SchemaSerializer
{
    public function __construct(private readonly CrudConfig $config) {}

    public function toArray(): array
    {
        // TODO: Phase 1 — PHP 설정 → JSON 스키마 변환
        return [
            'subject'      => $this->config->subject,
            'primaryKey'   => $this->config->primaryKey ?? 'id',
            'perPage'      => $this->config->perPage,
            'permissions'  => [
                'add'            => $this->config->canAdd,
                'edit'           => $this->config->canEdit,
                'delete'         => $this->config->canDelete,
                'read'           => $this->config->canRead,
                'clone'          => $this->config->canClone,
                'export'         => $this->config->canExport,
                'deleteMultiple' => $this->config->canDeleteMultiple,
            ],
            'columns'      => [],
            'formFields'   => ['add' => [], 'edit' => [], 'read' => [], 'clone' => []],
            'defaultOrder' => $this->config->defaultOrder,
        ];
    }
}
