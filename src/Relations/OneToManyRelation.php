<?php

namespace Hoksi\Ci4Crud\Relations;

use CodeIgniter\Database\BaseConnection;

class OneToManyRelation
{
    private ?BaseConnection $db;

    public function __construct(
        private readonly string $foreignKey,
        private readonly string $table,
        private readonly string $labelField,
        private readonly bool   $dynamic = false,
        private readonly string $valueField = 'id',
        ?BaseConnection $db = null,
    ) {
        $this->db = $db;
    }

    private function getDb(): BaseConnection
    {
        return $this->db ??= db_connect();
    }

    public function getOptions(string $search = '', int $limit = 100): array
    {
        $builder = $this->getDb()
            ->table($this->table)
            ->select("{$this->valueField} as value, {$this->labelField} as label");

        if ($search !== '') {
            $builder->like($this->labelField, $search);
        }

        $builder->orderBy($this->labelField, 'ASC')->limit($limit);

        return $builder->get()->getResultArray();
    }

    public function getLabelById(int|string $id): string
    {
        $row = $this->getDb()
            ->table($this->table)
            ->select($this->labelField)
            ->where($this->valueField, $id)
            ->get()
            ->getRowArray();

        return $row[$this->labelField] ?? '';
    }

    public function toSchemaArray(): array
    {
        return [
            'type'       => $this->dynamic ? 'relation' : 'dropdown',
            'table'      => $this->table,
            'labelField' => $this->labelField,
            'valueField' => $this->valueField,
            'dynamic'    => $this->dynamic,
        ];
    }
}
