<?php

namespace Hoksi\Ci4Crud\Core;

use CodeIgniter\Database\BaseConnection;

class SchemaReader
{
    private ?BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db;
    }

    private function getDb(): BaseConnection
    {
        return $this->db ??= db_connect();
    }

    public function getColumns(string $table): array
    {
        return $this->getDb()->getFieldData($table);
    }

    public function getPrimaryKey(string $table): string
    {
        $fields = $this->getColumns($table);

        foreach ($fields as $field) {
            if ($field->primary_key ?? false) {
                return $field->name;
            }
        }

        return 'id';
    }

    public function inferFieldType(object $field): string
    {
        $type = strtolower($field->type ?? '');
        $length = $field->max_length ?? 0;

        return match(true) {
            str_contains($type, 'tinyint') && $length === 1 => 'boolean',
            str_contains($type, 'int')                      => 'numeric',
            str_contains($type, 'decimal'),
            str_contains($type, 'float'),
            str_contains($type, 'double')                   => 'float',
            str_contains($type, 'datetime'),
            str_contains($type, 'timestamp')                => 'datetime',
            str_contains($type, 'date')                     => 'date',
            str_contains($type, 'time')                     => 'native_time',
            str_contains($type, 'text'),
            str_contains($type, 'longtext'),
            str_contains($type, 'mediumtext')               => 'textarea',
            $length > 500                                    => 'textarea',
            default                                          => 'string',
        };
    }
}
