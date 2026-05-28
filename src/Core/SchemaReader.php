<?php

namespace Hoksi\Ci4Crud\Core;

class SchemaReader
{
    public function getColumns(string $table): array
    {
        // TODO: Phase 1 — CI4 DB::getFieldData($table) 활용
        return [];
    }

    public function getPrimaryKey(string $table): string
    {
        // TODO: Phase 1 — 기본키 자동 감지
        return 'id';
    }

    public function inferFieldType(object $field): string
    {
        return match(true) {
            str_contains($field->type, 'int')      => 'numeric',
            str_contains($field->type, 'datetime') => 'datetime',
            str_contains($field->type, 'date')     => 'date',
            str_contains($field->type, 'text')     => 'textarea',
            str_contains($field->type, 'bool')     => 'boolean',
            isset($field->max_length) && $field->max_length > 500 => 'textarea',
            default                                 => 'string',
        };
    }
}
