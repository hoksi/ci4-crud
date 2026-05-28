<?php

namespace Hoksi\Ci4Crud\Tests;

use Hoksi\Ci4Crud\Config\CrudConfig;
use Hoksi\Ci4Crud\Core\UploadHandler;
use Hoksi\Ci4Crud\Export\CsvExporter;
use Hoksi\Ci4Crud\Export\ExcelExporter;
use PHPUnit\Framework\TestCase;

class ExportUploadTest extends TestCase
{
    // =========================================================================
    // CsvExporter 테스트
    // =========================================================================

    public function testCsvToStringReturnsUtf8Bom(): void
    {
        $exporter = new CsvExporter();
        $csv      = $exporter->toString([]);

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
    }

    public function testCsvToStringWithData(): void
    {
        $exporter = new CsvExporter();
        $data     = [
            ['id' => 1, 'name' => '홍길동', 'email' => 'hong@example.com'],
            ['id' => 2, 'name' => '김철수', 'email' => 'kim@example.com'],
        ];

        $csv = $exporter->toString($data);

        $this->assertStringContainsString('홍길동', $csv);
        $this->assertStringContainsString('김철수', $csv);
        $this->assertStringContainsString('hong@example.com', $csv);
    }

    public function testCsvToStringWithColumnLabels(): void
    {
        $exporter = new CsvExporter();
        $data     = [
            ['name' => '홍길동', 'email' => 'hong@example.com'],
        ];
        $labels   = ['name' => '이름', 'email' => '이메일'];

        $csv = $exporter->toString($data, $labels);

        $this->assertStringContainsString('이름', $csv);
        $this->assertStringContainsString('이메일', $csv);
        $this->assertStringContainsString('홍길동', $csv);
    }

    public function testCsvToStringEmptyData(): void
    {
        $exporter = new CsvExporter();
        $csv      = $exporter->toString([]);

        // BOM만 있어야 함
        $this->assertSame("\xEF\xBB\xBF", $csv);
    }

    public function testCsvToStringColumnOrder(): void
    {
        $exporter = new CsvExporter();
        $data     = [['b' => '2', 'a' => '1', 'c' => '3']];
        $labels   = ['a' => 'A컬럼', 'b' => 'B컬럼', 'c' => 'C컬럼'];

        $csv  = $exporter->toString($data, $labels);
        $lines = explode("\n", ltrim($csv, "\xEF\xBB\xBF"));

        // 헤더 순서가 labels 키 순서와 일치해야 함
        $this->assertStringContainsString('A컬럼', $lines[0]);
        $this->assertStringContainsString('B컬럼', $lines[0]);
    }

    // =========================================================================
    // ExcelExporter 테스트
    // =========================================================================

    public function testExcelExporterSavesToFile(): void
    {
        $exporter = new ExcelExporter();
        $data     = [
            ['id' => 1, 'name' => '홍길동'],
            ['id' => 2, 'name' => '김철수'],
        ];
        $tmpFile  = sys_get_temp_dir() . '/ci4crud_test_' . uniqid() . '.xlsx';

        $exporter->save($data, $tmpFile);

        $this->assertFileExists($tmpFile);
        $this->assertGreaterThan(0, filesize($tmpFile));

        unlink($tmpFile);
    }

    public function testExcelExporterWithColumnLabels(): void
    {
        $exporter = new ExcelExporter();
        $data     = [['name' => '홍길동', 'status' => 'active']];
        $labels   = ['name' => '이름', 'status' => '상태'];
        $tmpFile  = sys_get_temp_dir() . '/ci4crud_test_' . uniqid() . '.xlsx';

        $exporter->save($data, $tmpFile, $labels);

        $this->assertFileExists($tmpFile);
        unlink($tmpFile);
    }

    public function testExcelExporterEmptyData(): void
    {
        $exporter = new ExcelExporter();
        $tmpFile  = sys_get_temp_dir() . '/ci4crud_test_' . uniqid() . '.xlsx';

        $exporter->save([], $tmpFile);

        $this->assertFileExists($tmpFile);
        unlink($tmpFile);
    }

    public function testExcelColLetterConversion(): void
    {
        // 리플렉션으로 private colLetter() 테스트
        $exporter   = new ExcelExporter();
        $reflection = new \ReflectionClass($exporter);
        $method     = $reflection->getMethod('colLetter');
        $method->setAccessible(true);

        $this->assertSame('A',  $method->invoke($exporter, 0));
        $this->assertSame('B',  $method->invoke($exporter, 1));
        $this->assertSame('Z',  $method->invoke($exporter, 25));
        $this->assertSame('AA', $method->invoke($exporter, 26));
        $this->assertSame('AB', $method->invoke($exporter, 27));
    }

    // =========================================================================
    // UploadHandler 테스트
    // =========================================================================

    public function testUploadHandlerInjectSkipsWhenNoUploadFields(): void
    {
        $config  = new CrudConfig();
        $handler = new UploadHandler($config);

        $data   = ['name' => '홍길동'];
        $result = $handler->injectUploadedPaths($data);

        // 업로드 필드 없음 → 데이터 그대로 반환
        $this->assertSame($data, $result);
    }

    public function testUploadHandlerHandleSingleReturnsFalseWithoutCi4(): void
    {
        $config                               = new CrudConfig();
        $config->uploadFields['profile_img']  = ['path' => 'uploads/profiles/', 'multiple' => false];

        $handler = new UploadHandler($config);

        // CI4 service() 없는 환경 → false 반환
        $result = $handler->handleSingle('profile_img');
        $this->assertFalse($result);
    }

    public function testUploadHandlerHandleMultipleReturnsEmptyWithoutCi4(): void
    {
        $config                               = new CrudConfig();
        $config->uploadFields['attachments']  = ['path' => 'uploads/files/', 'multiple' => true];

        $handler = new UploadHandler($config);

        $result = $handler->handleMultiple('attachments');
        $this->assertSame([], $result);
    }

    public function testUploadHandlerReturnsFalseForUnknownField(): void
    {
        $config  = new CrudConfig();
        $handler = new UploadHandler($config);

        $this->assertFalse($handler->handleSingle('nonexistent_field'));
    }
}
