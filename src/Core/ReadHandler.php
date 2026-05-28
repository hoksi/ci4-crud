<?php

namespace Hoksi\Ci4Crud\Core;

use CodeIgniter\Database\BaseConnection;
use Hoksi\Ci4Crud\Config\CrudConfig;

class ReadHandler
{
    private ?BaseConnection $db;

    public function __construct(
        private readonly CrudConfig $config,
        ?BaseConnection $db = null,
    ) {
        $this->db = $db;
    }

    private function getDb(): BaseConnection
    {
        return $this->db ??= db_connect();
    }

    public function handle(int|string $id): array
    {
        $pk = $this->config->primaryKey ?? 'id';

        $builder = $this->getDb()->table($this->config->table)->where($pk, $id);

        // 전역 WHERE 조건
        foreach ($this->config->where as $condition) {
            $builder->where($condition);
        }

        $row = $builder->get()->getRowArray();

        if ($row === null) {
            return ['success' => false, 'message' => '데이터를 찾을 수 없습니다.'];
        }

        // readFields 필터링
        if (!empty($this->config->readFields)) {
            $row = array_intersect_key($row, array_flip($this->config->readFields));
        }

        return ['success' => true, 'data' => $row];
    }
}
