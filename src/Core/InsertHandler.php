<?php

namespace Hoksi\Ci4Crud\Core;

use CodeIgniter\Database\BaseConnection;
use Hoksi\Ci4Crud\Config\CrudConfig;
use Hoksi\Ci4Crud\Core\RelationHandler;
use Hoksi\Ci4Crud\Core\UploadHandler;

class InsertHandler
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

    public function handle(array $data): array
    {
        $data = $this->stripNonDbFields($data);

        // 파일 업로드 처리
        if (!empty($this->config->uploadFields)) {
            $data = (new UploadHandler($this->config))->injectUploadedPaths($data);
        }

        // before_insert 콜백
        foreach ($this->config->callbacks['before_insert'] ?? [] as $fn) {
            $data = $fn($data);
        }

        // 완전 대체 콜백
        if (isset($this->config->callbacks['insert'])) {
            return ($this->config->callbacks['insert'])($data);
        }

        // CI4 Validation
        $errors = $this->validate($data, 'add');
        if ($errors !== []) {
            return ['success' => false, 'message' => '유효성 검사 실패', 'errors' => $errors];
        }

        if ($this->config->useTimestamps) {
            $now = date('Y-m-d H:i:s');
            $data['created_at'] ??= $now;
            $data['updated_at'] ??= $now;
        }

        $this->getDb()->table($this->config->table)->insert($data);
        $insertId = $this->getDb()->insertID();

        // N:N 관계 동기화
        (new RelationHandler($this->config, $this->db))->syncNtoN($insertId, $data);

        // after_insert 콜백
        foreach ($this->config->callbacks['after_insert'] ?? [] as $fn) {
            $fn($data, $insertId);
        }

        return ['success' => true, 'message' => '등록되었습니다.', 'data' => ['id' => $insertId]];
    }

    private function validate(array $data, string $mode): array
    {
        $rules = $this->config->rules;

        if ($this->config->validationGroup !== null) {
            $validation = service('validation');
            $validation->setRuleGroup($this->config->validationGroup);
            return $validation->run($data) ? [] : $validation->getErrors();
        }

        // requiredFields → 자동 규칙 추가
        foreach ($this->config->requiredFields as $field) {
            $rules[$field] ??= 'required';
        }

        // uniqueFields → 자동 규칙 추가
        foreach ($this->config->uniqueFields as $field) {
            $table = $this->config->table;
            $rules[$field] = ($rules[$field] ?? 'required') . "|is_unique[{$table}.{$field}]";
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
        // 파일 필드, virtual 필드 제거
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
