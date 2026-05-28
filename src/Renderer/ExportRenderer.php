<?php

namespace Hoksi\Ci4Crud\Renderer;

use Hoksi\Ci4Crud\Config\CrudConfig;
use Hoksi\Ci4Crud\Core\QueryHandler;
use Hoksi\Ci4Crud\Core\SchemaSerializer;
use Hoksi\Ci4Crud\Core\SchemaReader;
use Hoksi\Ci4Crud\Export\CsvExporter;
use Hoksi\Ci4Crud\Export\ExcelExporter;

class ExportRenderer
{
    public function __construct(private readonly CrudConfig $config) {}

    public function csv(): void
    {
        [$rows, $labels, $filename] = $this->prepareData();
        (new CsvExporter())->download($rows, $filename, $labels);
    }

    public function excel(): void
    {
        [$rows, $labels, $filename] = $this->prepareData();
        (new ExcelExporter())->download($rows, $filename, $labels);
    }

    public function export(string $type = 'csv'): void
    {
        match($type) {
            'excel', 'xlsx' => $this->excel(),
            default          => $this->csv(),
        };
    }

    private function prepareData(): array
    {
        // 전체 데이터 조회 (페이지네이션 없이)
        $_GET['page'] = 1;
        $_GET['size'] = 999999;

        $result   = (new QueryHandler($this->config))->list();
        $rows     = $result['data'] ?? [];
        $filename = $this->config->subject ?: $this->config->table;

        // 스키마에서 컬럼 라벨 추출
        $schema   = (new SchemaSerializer($this->config, new SchemaReader()))->toArray();
        $labels   = [];
        foreach ($schema['columns'] as $col) {
            $labels[$col['field']] = $col['title'];
        }

        return [$rows, $labels, $filename];
    }
}
