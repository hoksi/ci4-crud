<?php

namespace Hoksi\Ci4Crud\Export;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelExporter
{
    /**
     * Excel(.xlsx) 파일을 브라우저로 다운로드합니다.
     */
    public function download(array $data, string $filename = 'export', array $columnLabels = []): void
    {
        $spreadsheet = $this->build($data, $columnLabels);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '_' . date('Ymd_His') . '.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * 임시 파일로 저장하고 경로를 반환합니다.
     */
    public function save(array $data, string $path, array $columnLabels = []): string
    {
        $spreadsheet = $this->build($data, $columnLabels);
        $writer      = new Xlsx($spreadsheet);
        $writer->save($path);
        return $path;
    }

    private function build(array $data, array $columnLabels): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();

        if (empty($data)) {
            return $spreadsheet;
        }

        $headers = !empty($columnLabels)
            ? array_values($columnLabels)
            : array_keys($data[0]);

        // 헤더 행 작성
        foreach ($headers as $colIdx => $header) {
            $col = $this->colLetter($colIdx);
            $sheet->setCellValue($col . '1', $header);
        }

        // 헤더 스타일 (굵게 + 배경색)
        $headerRange = 'A1:' . $this->colLetter(count($headers) - 1) . '1';
        $sheet->getStyle($headerRange)->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // 데이터 행 작성
        foreach ($data as $rowIdx => $row) {
            $values = !empty($columnLabels)
                ? array_map(fn($k) => $row[$k] ?? '', array_keys($columnLabels))
                : array_values($row);

            foreach ($values as $colIdx => $value) {
                $sheet->setCellValue($this->colLetter($colIdx) . ($rowIdx + 2), $value);
            }
        }

        // 컬럼 너비 자동 조정
        foreach (range(0, count($headers) - 1) as $colIdx) {
            $sheet->getColumnDimension($this->colLetter($colIdx))->setAutoSize(true);
        }

        return $spreadsheet;
    }

    private function colLetter(int $index): string
    {
        $letters = '';
        $index++;
        while ($index > 0) {
            $mod      = ($index - 1) % 26;
            $letters  = chr(65 + $mod) . $letters;
            $index    = (int)(($index - $mod) / 26);
        }
        return $letters;
    }
}
