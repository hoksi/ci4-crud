<?php

namespace Hoksi\Ci4Crud\Tests;

use Hoksi\Ci4Crud\Config\CrudConfig;
use Hoksi\Ci4Crud\Core\SchemaReader;
use Hoksi\Ci4Crud\Core\SchemaSerializer;
use Hoksi\Ci4Crud\Core\StateManager;
use Hoksi\Ci4Crud\Renderer\DatagridRenderer;
use Hoksi\Ci4Crud\Renderer\FormRenderer;
use PHPUnit\Framework\TestCase;

class RendererTest extends TestCase
{
    protected function setUp(): void
    {
        $_GET  = [];
        $_POST = [];
        $_SERVER['REQUEST_URI'] = '/admin/users';
    }

    // =========================================================================
    // StateManager 테스트
    // =========================================================================

    public function testStateManagerDefaultIsList(): void
    {
        $this->assertSame('list', (new StateManager())->detect());
    }

    public function testStateManagerDetectsValidStates(): void
    {
        $validStates = ['list', 'ajax_list', 'add', 'insert', 'edit', 'update', 'delete', 'read', 'clone', 'export'];

        foreach ($validStates as $state) {
            $_GET['state'] = $state;
            $this->assertSame($state, (new StateManager())->detect(), "Failed for state: {$state}");
        }
    }

    public function testStateManagerRejectsInvalidState(): void
    {
        $_GET['state'] = 'drop_table';
        $this->assertSame('list', (new StateManager())->detect());
    }

    // =========================================================================
    // DatagridRenderer 테스트
    // =========================================================================

    public function testDatagridRendererReturnsString(): void
    {
        $config             = new CrudConfig();
        $config->table      = 'users';
        $config->subject    = '사용자';
        $config->primaryKey = 'id';

        $mockReader = $this->createMock(SchemaReader::class);
        $mockReader->method('getColumns')->willReturn([]);

        $renderer = new DatagridRenderer($config);
        $html     = $renderer->render();

        $this->assertIsString($html);
    }

    public function testDatagridRendererContainsSubject(): void
    {
        $config             = new CrudConfig();
        $config->table      = 'users';
        $config->subject    = '사용자';
        $config->primaryKey = 'id';

        $html = (new DatagridRenderer($config))->render();

        $this->assertStringContainsString('사용자', $html);
    }

    public function testDatagridRendererContainsBootstrapTable(): void
    {
        $config             = new CrudConfig();
        $config->table      = 'products';
        $config->subject    = '상품';
        $config->primaryKey = 'id';

        $html = (new DatagridRenderer($config))->render();

        $this->assertStringContainsString('<table', $html);
        $this->assertStringContainsString('table-bordered', $html);
    }

    public function testDatagridRendererContainsScript(): void
    {
        $config             = new CrudConfig();
        $config->table      = 'orders';
        $config->subject    = '주문';
        $config->primaryKey = 'id';

        $html = (new DatagridRenderer($config))->render();

        $this->assertStringContainsString('<script>', $html);
        $this->assertStringContainsString('ajax_list', $html);
    }

    public function testDatagridRendererHidesAddButtonWhenUnsetAdd(): void
    {
        $config             = new CrudConfig();
        $config->table      = 'users';
        $config->subject    = '사용자';
        $config->primaryKey = 'id';
        $config->canAdd     = false;

        $html = (new DatagridRenderer($config))->render();

        $this->assertStringContainsString('display:none', $html);
    }

    public function testDatagridRendererIncludesColumns(): void
    {
        $config             = new CrudConfig();
        $config->table      = 'users';
        $config->subject    = '사용자';
        $config->primaryKey = 'id';
        $config->columns    = ['name', 'email'];
        $config->labels     = ['name' => '이름', 'email' => '이메일'];

        $mockField1 = (object)['name' => 'name',  'type' => 'varchar', 'max_length' => 100, 'primary_key' => false];
        $mockField2 = (object)['name' => 'email', 'type' => 'varchar', 'max_length' => 255, 'primary_key' => false];

        $mockReader = $this->createMock(SchemaReader::class);
        $mockReader->method('getColumns')->willReturn([$mockField1, $mockField2]);

        $html = (new DatagridRenderer($config))->render();

        $this->assertStringContainsString('이름', $html);
        $this->assertStringContainsString('이메일', $html);
    }

    public function testDatagridRendererGeneratesUniqueId(): void
    {
        $config1        = new CrudConfig();
        $config1->table = 'users';

        $config2        = new CrudConfig();
        $config2->table = 'orders';

        $html1 = (new DatagridRenderer($config1))->render();
        $html2 = (new DatagridRenderer($config2))->render();

        // 각 인스턴스가 다른 고유 ID를 갖는지 확인
        preg_match('/id="(ci4crud_\w+)_wrapper"/', $html1, $m1);
        preg_match('/id="(ci4crud_\w+)_wrapper"/', $html2, $m2);

        $this->assertNotEmpty($m1[1] ?? '');
        $this->assertNotEmpty($m2[1] ?? '');
        $this->assertNotSame($m1[1], $m2[1]);
    }

    // =========================================================================
    // FormRenderer 테스트
    // =========================================================================

    public function testFormRendererRenderAddReturnsString(): void
    {
        $config             = new CrudConfig();
        $config->table      = 'users';
        $config->subject    = '사용자';
        $config->primaryKey = 'id';

        $html = (new FormRenderer($config))->renderAdd();

        $this->assertIsString($html);
        $this->assertStringContainsString('<form', $html);
    }

    public function testFormRendererContainsSubmitButton(): void
    {
        $config             = new CrudConfig();
        $config->table      = 'users';
        $config->subject    = '사용자';
        $config->primaryKey = 'id';

        $html = (new FormRenderer($config))->renderAdd();

        $this->assertStringContainsString('type="submit"', $html);
    }

    public function testFormRendererAddModeHasSubmit(): void
    {
        $config             = new CrudConfig();
        $config->table      = 'users';
        $config->subject    = '사용자';
        $config->primaryKey = 'id';

        // renderAdd는 DB 호출 없음
        $html = (new FormRenderer($config))->renderAdd();

        $this->assertStringContainsString('type="submit"', $html);
        $this->assertStringContainsString('추가', $html);
    }

    public function testFormRendererContainsValidationErrorContainer(): void
    {
        $config             = new CrudConfig();
        $config->table      = 'users';
        $config->subject    = '사용자';
        $config->primaryKey = 'id';

        $html = (new FormRenderer($config))->renderAdd();

        $this->assertStringContainsString('ci4crud_validation_errors', $html);
    }

    // =========================================================================
    // SchemaSerializer + 렌더러 통합 테스트
    // =========================================================================

    public function testSchemaSerializerDefaultPerPage(): void
    {
        $config             = new CrudConfig();
        $config->primaryKey = 'id';

        $mockReader = $this->createMock(SchemaReader::class);
        $mockReader->method('getColumns')->willReturn([]);

        $schema = (new SchemaSerializer($config, $mockReader))->toArray();

        $this->assertSame(20, $schema['perPage']);
    }

    public function testSchemaSerializerCustomPerPage(): void
    {
        $config             = new CrudConfig();
        $config->primaryKey = 'id';
        $config->perPage    = 50;

        $mockReader = $this->createMock(SchemaReader::class);
        $mockReader->method('getColumns')->willReturn([]);

        $schema = (new SchemaSerializer($config, $mockReader))->toArray();

        $this->assertSame(50, $schema['perPage']);
    }
}
