<?php

namespace Hoksi\Ci4Crud\Core;

use CodeIgniter\Database\BaseConnection;
use Hoksi\Ci4Crud\Config\CrudConfig;

class QueryHandler
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

    public function list(): array
    {
        $page   = (int)($_GET['page']   ?? 1);
        $size   = (int)($_GET['size']   ?? $this->config->perPage);
        $sort   = $_GET['sort']   ?? [];
        $filter = $_GET['filter'] ?? [];

        $builder = $this->getDb()->table($this->config->table);

        // 콜백으로 쿼리 직접 조작
        if (isset($this->config->callbacks['query'])) {
            $builder = ($this->config->callbacks['query'])($builder);
        }

        // 전역 WHERE 조건
        foreach ($this->config->where as $condition) {
            $builder->where($condition);
        }

        // Tabulator 필터 적용
        foreach ($filter as $f) {
            $this->applyFilter($builder, $f);
        }

        // 전체 수 카운트 (페이지네이션 전)
        $total    = $builder->countAllResults(false);
        $lastPage = $size > 0 ? (int)ceil($total / $size) : 1;

        // 정렬
        if (!empty($sort)) {
            foreach ($sort as $s) {
                if (!empty($s['field'])) {
                    $builder->orderBy($s['field'], strtoupper($s['dir'] ?? 'asc'));
                }
            }
        } elseif (!empty($this->config->defaultOrder)) {
            $builder->orderBy(
                $this->config->defaultOrder['field'],
                strtoupper($this->config->defaultOrder['dir'] ?? 'asc'),
            );
        }

        // 페이지네이션
        $builder->limit($size, ($page - 1) * $size);

        $rows = $builder->get()->getResultArray();

        // callbackColumn 적용
        $rows = $this->applyColumnCallbacks($rows);

        return ['last_page' => max(1, $lastPage), 'data' => $rows];
    }

    public function relation(string $field, string $search = ''): array
    {
        $relation = $this->config->relations[$field] ?? null;

        if ($relation === null) {
            return [];
        }

        $builder = $this->getDb()->table($relation['table']);

        if ($search !== '') {
            $builder->like($relation['label'], $search);
        }

        $builder->limit(50);

        return $builder->select("id, {$relation['label']} as label")->get()->getResultArray();
    }

    private function applyFilter(mixed $builder, array $filter): void
    {
        $field = $filter['field'] ?? '';
        $type  = $filter['type']  ?? 'like';
        $value = $filter['value'] ?? '';

        if ($field === '') {
            return;
        }

        match($type) {
            '=', 'eq'   => $builder->where($field, $value),
            '!=', '!='  => $builder->where("{$field} !=", $value),
            '<'         => $builder->where("{$field} <", $value),
            '>'         => $builder->where("{$field} >", $value),
            '<='        => $builder->where("{$field} <=", $value),
            '>='        => $builder->where("{$field} >=", $value),
            'starts'    => $builder->like($field, $value, 'after'),
            'ends'      => $builder->like($field, $value, 'before'),
            default     => $builder->like($field, $value),
        };
    }

    private function applyColumnCallbacks(array $rows): array
    {
        $callbacks = $this->config->callbacks['column'] ?? [];

        if (empty($callbacks)) {
            return $rows;
        }

        return array_map(function (array $row) use ($callbacks): array {
            foreach ($callbacks as $field => $fn) {
                if (array_key_exists($field, $row)) {
                    $row[$field] = $fn($row[$field], $row);
                }
            }
            return $row;
        }, $rows);
    }
}
