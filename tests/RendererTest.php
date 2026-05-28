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

        $this->assertStringContainsString('ci4crud_form_errors', $html);
    }

    public function testFormRendererContainsMultipartEnctype(): void
    {
        $config             = new CrudConfig();
        $config->subject    = '상품';
        $config->primaryKey = 'id';

        $html = (new FormRenderer($config))->renderAdd();

        $this->assertStringContainsString('multipart/form-data', $html);
    }

    // =========================================================================
    // FormRenderer — 필드 타입별 렌더링 테스트
    // =========================================================================

    private function makeFormRendererWithField(string $fieldName, string $fieldType, array $options = []): FormRenderer
    {
        $config             = new CrudConfig();
        $config->subject    = '테스트';
        $config->primaryKey = 'id';
        $config->addFields  = [$fieldName];
        $config->fieldTypes[$fieldName] = ['type' => $fieldType, 'options' => $options, 'form' => 'all'];

        $mockField = (object)['name' => $fieldName, 'type' => 'varchar', 'max_length' => 255, 'primary_key' => false];

        return new FormRenderer($config);
    }

    public function testFormRendererRendersTextarea(): void
    {
        $config             = new CrudConfig();
        $config->subject    = '게시글';
        $config->primaryKey = 'id';
        $config->addFields  = ['content'];
        $config->fieldTypes['content'] = ['type' => 'textarea', 'options' => [], 'form' => 'all'];

        $html = (new FormRenderer($config))->renderAdd();

        $this->assertStringContainsString('<textarea', $html);
        $this->assertStringContainsString('name="content"', $html);
    }

    public function testFormRendererRendersDropdown(): void
    {
        $config             = new CrudConfig();
        $config->subject    = '주문';
        $config->primaryKey = 'id';
        $config->addFields  = ['status'];
        $config->fieldTypes['status'] = [
            'type'    => 'dropdown',
            'options' => ['active' => '활성', 'inactive' => '비활성'],
            'form'    => 'all',
        ];

        $html = (new FormRenderer($config))->renderAdd();

        $this->assertStringContainsString('<select', $html);
        $this->assertStringContainsString('활성', $html);
        $this->assertStringContainsString('비활성', $html);
    }

    public function testFormRendererRendersPasswordToggle(): void
    {
        $config             = new CrudConfig();
        $config->subject    = '사용자';
        $config->primaryKey = 'id';
        $config->addFields  = ['password'];
        $config->fieldTypes['password'] = ['type' => 'password_toggle', 'options' => [], 'form' => 'all'];

        $html = (new FormRenderer($config))->renderAdd();

        $this->assertStringContainsString('type="password"', $html);
        $this->assertStringContainsString('input-group', $html);
    }

    public function testFormRendererRendersUploadField(): void
    {
        $config             = new CrudConfig();
        $config->subject    = '파일';
        $config->primaryKey = 'id';
        $config->addFields  = ['attachment'];
        $config->fieldTypes['attachment'] = ['type' => 'upload_file', 'options' => [], 'form' => 'all'];

        $html = (new FormRenderer($config))->renderAdd();

        $this->assertStringContainsString('type="file"', $html);
    }

    public function testFormRendererRenderAssetsReturnsEmptyWhenNoSpecialTypes(): void
    {
        $config             = new CrudConfig();
        $config->primaryKey = 'id';
        $config->addFields  = ['name'];
        $config->fieldTypes['name'] = ['type' => 'string', 'options' => [], 'form' => 'all'];

        $assets = (new FormRenderer($config))->renderAssets('add');

        $this->assertSame('', $assets);
    }

    public function testFormRendererRenderAssetsIncludesFlatpickrForDate(): void
    {
        $config             = new CrudConfig();
        $config->primaryKey = 'id';
        $config->addFields  = ['birth_date'];
        $config->fieldTypes['birth_date'] = ['type' => 'date', 'options' => [], 'form' => 'all'];

        $assets = (new FormRenderer($config))->renderAssets('add');

        $this->assertStringContainsString('flatpickr', $assets);
    }

    public function testFormRendererRenderAssetsIncludesTomSelectForRelation(): void
    {
        $config             = new CrudConfig();
        $config->primaryKey = 'id';
        $config->addFields  = ['dept_id'];
        $config->fieldTypes['dept_id'] = ['type' => 'relation', 'options' => [], 'form' => 'all'];

        $assets = (new FormRenderer($config))->renderAssets('add');

        $this->assertStringContainsString('tom-select', $assets);
    }

    public function testFormRendererRenderAssetsIncludesCKEditorForWysiwyg(): void
    {
        $config             = new CrudConfig();
        $config->primaryKey = 'id';
        $config->addFields  = ['content'];
        $config->fieldTypes['content'] = ['type' => 'wysiwyg', 'options' => [], 'form' => 'all'];

        $assets = (new FormRenderer($config))->renderAssets('add');

        $this->assertStringContainsString('ckeditor', $assets);
    }

    public function testFormRendererDateFieldIncludesFlatpickrInit(): void
    {
        $config             = new CrudConfig();
        $config->primaryKey = 'id';
        $config->addFields  = ['start_date'];
        $config->fieldTypes['start_date'] = ['type' => 'date', 'options' => [], 'form' => 'all'];

        $html = (new FormRenderer($config))->renderAdd();

        $this->assertStringContainsString('flatpickr', $html);
    }

    public function testFormRendererColorFieldHasDefaultValue(): void
    {
        $config             = new CrudConfig();
        $config->primaryKey = 'id';
        $config->addFields  = ['bg_color'];
        $config->fieldTypes['bg_color'] = ['type' => 'color', 'options' => [], 'form' => 'all'];

        $html = (new FormRenderer($config))->renderAdd();

        $this->assertStringContainsString('type="color"', $html);
        $this->assertStringContainsString('#000000', $html);
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
