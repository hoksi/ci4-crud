<?php

namespace Hoksi\Ci4Crud\Renderer;

use Hoksi\Ci4Crud\Config\CrudConfig;
use Hoksi\Ci4Crud\Core\QueryHandler;

class ExportRenderer
{
    public function __construct(private readonly CrudConfig $config) {}

    public function csv(): void
    {
        // 전체 데이터 조회 (페이지네이션 없이)
        $_GET['page'] = 1;
        $_GET['size'] = 999999;

        $result   = (new QueryHandler($this->config))->list();
        $rows     = $result['data'] ?? [];
        $filename = ($this->config->subject ?: $this->config->table) . '_' . date('Ymd_His');

        header('Content-Type: text/csv; charset=UTF-8');
        header("Content-Disposition: attachment; filename=\"{$filename}.csv\"");
        header('Pragma: no-cache');

        $output = fopen('php://output', 'w');
        fputs($output, "\xEF\xBB\xBF"); // UTF-8 BOM (Excel 한글 지원)

        // 헤더 행
        if (!empty($rows)) {
            fputcsv($output, array_keys($rows[0]));
        }

        foreach ($rows as $row) {
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }
}
