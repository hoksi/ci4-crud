<?php

namespace Hoksi\Ci4Crud\Export;

class CsvExporter
{
    /**
     * CSV 파일을 브라우저로 다운로드합니다.
     */
    public function download(array $data, string $filename = 'export', array $columnLabels = []): void
    {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '_' . date('Ymd_His') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        fputs($output, "\xEF\xBB\xBF"); // UTF-8 BOM (Excel 한글 호환)

        if (!empty($data)) {
            $headers = !empty($columnLabels)
                ? array_values($columnLabels)
                : array_keys($data[0]);

            fputcsv($output, $headers);

            foreach ($data as $row) {
                $values = !empty($columnLabels)
                    ? array_map(fn($k) => $row[$k] ?? '', array_keys($columnLabels))
                    : array_values($row);

                fputcsv($output, $values);
            }
        }

        fclose($output);
        exit;
    }

    /**
     * CSV 문자열로 반환합니다 (테스트 및 응답 반환 용).
     */
    public function toString(array $data, array $columnLabels = []): string
    {
        $output = "\xEF\xBB\xBF";

        if (empty($data)) {
            return $output;
        }

        $handle = fopen('php://temp', 'r+');

        $headers = !empty($columnLabels)
            ? array_values($columnLabels)
            : array_keys($data[0]);

        fputcsv($handle, $headers);

        foreach ($data as $row) {
            $values = !empty($columnLabels)
                ? array_map(fn($k) => $row[$k] ?? '', array_keys($columnLabels))
                : array_values($row);

            fputcsv($handle, $values);
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return $output . $content;
    }
}
