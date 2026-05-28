<?php

namespace Hoksi\Ci4Crud\Core;

use Hoksi\Ci4Crud\Config\CrudConfig;

class SchemaSerializer
{
    private SchemaReader $reader;

    public function __construct(
        private readonly CrudConfig $config,
        ?SchemaReader $reader = null,
    ) {
        $this->reader = $reader ?? new SchemaReader();
    }

    public function toArray(): array
    {
        $primaryKey = $this->config->primaryKey ?? $this->resolvePrimaryKey();

        return [
            'subject'      => $this->config->subject,
            'primaryKey'   => $primaryKey,
            'perPage'      => $this->config->perPage,
            'permissions'  => $this->buildPermissions(),
            'columns'      => $this->buildColumns($primaryKey),
            'formFields'   => $this->buildFormFields($primaryKey),
            'defaultOrder' => $this->config->defaultOrder,
        ];
    }

    private function resolvePrimaryKey(): string
    {
        if (empty($this->config->table)) {
            return 'id';
        }

        try {
            return $this->reader->getPrimaryKey($this->config->table);
        } catch (\Throwable) {
            return 'id';
        }
    }

    private function buildPermissions(): array
    {
        return [
            'add'            => $this->config->canAdd,
            'edit'           => $this->config->canEdit,
            'delete'         => $this->config->canDelete,
            'read'           => $this->config->canRead,
            'clone'          => $this->config->canClone,
            'export'         => $this->config->canExport,
            'deleteMultiple' => $this->config->canDeleteMultiple,
        ];
    }

    private function buildColumns(string $primaryKey): array
    {
        $dbFields   = $this->resolveDbFields();
        $showFields = $this->config->columns ?: array_map(fn($f) => $f->name, $dbFields);
        $hidden     = $this->config->hiddenColumns;

        $columns = [];

        foreach ($showFields as $fieldName) {
            if (in_array($fieldName, $hidden, true)) {
                continue;
            }

            $columns[] = [
                'field'      => $fieldName,
                'title'      => $this->config->labels[$fieldName] ?? $fieldName,
                'sortable'   => true,
                'searchable' => in_array($fieldName, $this->config->searchableFields, true),
                'callback'   => isset($this->config->callbacks['column'][$fieldName]),
                'width'      => $this->config->fieldOptions[$fieldName]['width'] ?? null,
            ];
        }

        return $columns;
    }

    private function buildFormFields(string $primaryKey): array
    {
        $dbFields = $this->resolveDbFields();
        $modes    = ['add', 'edit', 'read', 'clone'];
        $result   = [];

        foreach ($modes as $mode) {
            $configKey  = $mode . 'Fields';
            $showFields = $this->config->{$configKey} ?: array_map(fn($f) => $f->name, $dbFields);

            $result[$mode] = [];

            foreach ($showFields as $fieldName) {
                if ($fieldName === $primaryKey) {
                    continue;
                }

                $result[$mode][] = $this->buildFieldDefinition($fieldName, $mode, $dbFields);
            }
        }

        return $result;
    }

    private function buildFieldDefinition(string $fieldName, string $mode, array $dbFields): array
    {
        $dbField   = $this->findDbField($dbFields, $fieldName);
        $typeKey   = $fieldName . ':' . $mode;
        $typeDef   = $this->config->fieldTypes[$typeKey]
                  ?? $this->config->fieldTypes[$fieldName]
                  ?? null;

        $type    = $typeDef['type']    ?? ($dbField ? $this->reader->inferFieldType($dbField) : 'string');
        $options = $typeDef['options'] ?? [];

        $def = [
            'field'    => $fieldName,
            'title'    => $this->config->labels[$fieldName] ?? $fieldName,
            'type'     => $type,
            'required' => in_array($fieldName, $this->config->requiredFields, true),
            'readonly' => in_array($fieldName, $this->config->readOnlyFields, true),
        ];

        if (!empty($options)) {
            $def['options'] = $options;
        }

        if (isset($this->config->relations[$fieldName])) {
            $def['relation'] = $this->config->relations[$fieldName];
            $def['type']     = $this->config->relations[$fieldName]['dynamic'] ? 'relation' : 'dropdown';
        }

        if (isset($this->config->relationsNtoN[$fieldName])) {
            $def['relation'] = $this->config->relationsNtoN[$fieldName];
            $def['type']     = 'relation_nton';
        }

        return $def;
    }

    private function resolveDbFields(): array
    {
        if (empty($this->config->table)) {
            return [];
        }

        try {
            return $this->reader->getColumns($this->config->table);
        } catch (\Throwable) {
            return [];
        }
    }

    private function findDbField(array $dbFields, string $name): ?object
    {
        foreach ($dbFields as $field) {
            if ($field->name === $name) {
                return $field;
            }
        }

        return null;
    }
}
