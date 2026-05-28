<?php

namespace Hoksi\Ci4Crud\Relations;

use CodeIgniter\Database\BaseConnection;

class ManyToManyRelation
{
    private ?BaseConnection $db;

    public function __construct(
        private readonly string $field,
        private readonly string $junctionTable,
        private readonly string $relatedTable,
        private readonly string $junctionFk,
        private readonly string $relatedFk,
        private readonly string $labelField,
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
            ->table($this->relatedTable)
            ->select("{$this->valueField} as value, {$this->labelField} as label");

        if ($search !== '') {
            $builder->like($this->labelField, $search);
        }

        $builder->orderBy($this->labelField, 'ASC')->limit($limit);

        return $builder->get()->getResultArray();
    }

    public function getSelectedIds(int|string $primaryId): array
    {
        $rows = $this->getDb()
            ->table($this->junctionTable)
            ->select($this->relatedFk)
            ->where($this->junctionFk, $primaryId)
            ->get()
            ->getResultArray();

        return array_column($rows, $this->relatedFk);
    }

    public function syncJunction(int|string $primaryId, array $relatedIds): void
    {
        if (empty($primaryId)) {
            return;
        }

        $db = $this->getDb();
        $db->transStart();

        // 기존 관계 전체 삭제
        $db->table($this->junctionTable)
            ->where($this->junctionFk, $primaryId)
            ->delete();

        // 새 관계 삽입
        if (!empty($relatedIds)) {
            $rows = array_map(
                fn($relatedId) => [
                    $this->junctionFk => $primaryId,
                    $this->relatedFk  => $relatedId,
                ],
                $relatedIds,
            );

            $db->table($this->junctionTable)->insertBatch($rows);
        }

        $db->transComplete();
    }

    public function toSchemaArray(): array
    {
        return [
            'type'          => 'relation_nton',
            'junctionTable' => $this->junctionTable,
            'relatedTable'  => $this->relatedTable,
            'junctionFk'    => $this->junctionFk,
            'relatedFk'     => $this->relatedFk,
            'labelField'    => $this->labelField,
            'valueField'    => $this->valueField,
            'multiple'      => true,
        ];
    }
}
