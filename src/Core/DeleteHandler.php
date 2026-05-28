<?php

namespace Hoksi\Ci4Crud\Core;

use CodeIgniter\Database\BaseConnection;
use Hoksi\Ci4Crud\Config\CrudConfig;

class DeleteHandler
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

        // before_delete 콜백 (false 반환 시 취소)
        foreach ($this->config->callbacks['before_delete'] ?? [] as $fn) {
            if ($fn($id) === false) {
                return ['success' => false, 'message' => '삭제가 취소되었습니다.'];
            }
        }

        // 완전 대체 콜백
        if (isset($this->config->callbacks['delete'])) {
            return ($this->config->callbacks['delete'])($id);
        }

        $builder = $this->getDb()->table($this->config->table)->where($pk, $id);

        if ($this->config->softDelete) {
            $builder->update(['deleted_at' => date('Y-m-d H:i:s')]);
        } else {
            $builder->delete();
        }

        // after_delete 콜백
        foreach ($this->config->callbacks['after_delete'] ?? [] as $fn) {
            $fn($id);
        }

        return ['success' => true, 'message' => '삭제되었습니다.'];
    }

    public function handleMultiple(array $ids): array
    {
        if (empty($ids)) {
            return ['success' => false, 'message' => '삭제할 항목을 선택해주세요.'];
        }

        $pk = $this->config->primaryKey ?? 'id';

        // before_delete_multiple 콜백
        foreach ($this->config->callbacks['before_delete_multiple'] ?? [] as $fn) {
            if ($fn($ids) === false) {
                return ['success' => false, 'message' => '삭제가 취소되었습니다.'];
            }
        }

        // 완전 대체 콜백
        if (isset($this->config->callbacks['delete_multiple'])) {
            return ($this->config->callbacks['delete_multiple'])($ids);
        }

        $db      = $this->getDb();
        $builder = $db->table($this->config->table)->whereIn($pk, $ids);

        if ($this->config->softDelete) {
            $builder->update(['deleted_at' => date('Y-m-d H:i:s')]);
        } else {
            $builder->delete();
        }

        // after_delete_multiple 콜백
        foreach ($this->config->callbacks['after_delete_multiple'] ?? [] as $fn) {
            $fn($ids);
        }

        return ['success' => true, 'message' => count($ids) . '건이 삭제되었습니다.'];
    }
}
