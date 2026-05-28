<?php

namespace Hoksi\Ci4Crud\Core;

use CodeIgniter\Database\BaseConnection;
use Hoksi\Ci4Crud\Config\CrudConfig;
use Hoksi\Ci4Crud\Core\RelationHandler;

class UpdateHandler
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

    public function handle(int|string $id, array $data): array
    {
        $pk   = $this->config->primaryKey ?? 'id';
        $data = $this->stripNonDbFields($data);

        // before_update 콜백
        foreach ($this->config->callbacks['before_update'] ?? [] as $fn) {
            $data = $fn($data, $id);
        }

        // 완전 대체 콜백
        if (isset($this->config->callbacks['update'])) {
            return ($this->config->callbacks['update'])($data, $id);
        }

        // CI4 Validation (수정 시 unique 규칙에 현재 ID 제외)
        $errors = $this->validate($data, $id);
        if ($errors !== []) {
            return ['success' => false, 'message' => '유효성 검사 실패', 'errors' => $errors];
        }

        if ($this->config->useTimestamps) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        // readonly 필드 제거
        foreach ($this->config->readOnlyFields as $field) {
            unset($data[$field]);
        }

        $this->getDb()->table($this->config->table)->where($pk, $id)->update($data);

        // N:N 관계 동기화
        (new RelationHandler($this->config, $this->db))->syncNtoN($id, $data);

        // after_update 콜백
        foreach ($this->config->callbacks['after_update'] ?? [] as $fn) {
            $fn($data, $id);
        }

        return ['success' => true, 'message' => '수정되었습니다.'];
    }

    private function validate(array $data, int|string $id): array
    {
        $rules = $this->config->rules;
        $table = $this->config->table;
        $pk    = $this->config->primaryKey ?? 'id';

        if ($this->config->validationGroup !== null) {
            $validation = service('validation');
            $validation->setRuleGroup($this->config->validationGroup);
            return $validation->run($data) ? [] : $validation->getErrors();
        }

        foreach ($this->config->requiredFields as $field) {
            $rules[$field] ??= 'required';
        }

        // 수정 시 unique 규칙: 현재 레코드 제외
        foreach ($this->config->uniqueFields as $field) {
            $rules[$field] = ($rules[$field] ?? 'required') . "|is_unique[{$table}.{$field},{$pk},{$id}]";
        }

        if (empty($rules)) {
            return [];
        }

        $validation = service('validation');
        $validation->reset()->setRules($rules);

        return $validation->run($data) ? [] : $validation->getErrors();
    }

    private function stripNonDbFields(array $data): array
    {
        foreach ($this->config->uploadFields as $field => $opts) {
            unset($data[$field]);
        }

        foreach ($this->config->fieldTypes as $key => $typeDef) {
            $field = explode(':', $key)[0];
            if ($typeDef['type'] === 'virtual' || $typeDef['type'] === 'invisible') {
                unset($data[$field]);
            }
        }

        return $data;
    }
}
