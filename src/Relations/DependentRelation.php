<?php

namespace Hoksi\Ci4Crud\Relations;

use CodeIgniter\Database\BaseConnection;

class DependentRelation
{
    private ?BaseConnection $db;

    public function __construct(
        private readonly string $childField,
        private readonly string $parentField,
        private readonly string $table,
        private readonly string $labelField,
        private readonly string $foreignKey,
        private readonly string $valueField = 'id',
        ?BaseConnection $db = null,
    ) {
        $this->db = $db;
    }

    private function getDb(): BaseConnection
    {
        return $this->db ??= db_connect();
    }

    public function getOptions(int|string $parentId, string $search = ''): array
    {
        if ($parentId === '' || $parentId === 0) {
            return [];
        }

        $builder = $this->getDb()
            ->table($this->table)
            ->select("{$this->valueField} as value, {$this->labelField} as label")
            ->where($this->foreignKey, $parentId);

        if ($search !== '') {
            $builder->like($this->labelField, $search);
        }

        $builder->orderBy($this->labelField, 'ASC')->limit(100);

        return $builder->get()->getResultArray();
    }

    public function toSchemaArray(): array
    {
        return [
            'type'        => 'dependent',
            'table'       => $this->table,
            'labelField'  => $this->labelField,
            'valueField'  => $this->valueField,
            'foreignKey'  => $this->foreignKey,
            'parentField' => $this->parentField,
        ];
    }
}
