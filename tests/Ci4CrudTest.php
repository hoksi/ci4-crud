<?php

namespace Hoksi\Ci4Crud\Tests;

use Hoksi\Ci4Crud\Ci4Crud;
use Hoksi\Ci4Crud\Config\CrudConfig;
use Hoksi\Ci4Crud\Core\ActionManager;
use Hoksi\Ci4Crud\Core\DeleteHandler;
use Hoksi\Ci4Crud\Core\SchemaReader;
use Hoksi\Ci4Crud\Core\SchemaSerializer;
use PHPUnit\Framework\TestCase;

class Ci4CrudTest extends TestCase
{
    private Ci4Crud $crud;

    protected function setUp(): void
    {
        $this->crud = new Ci4Crud();
        // 슈퍼글로벌 초기화
        $_GET  = [];
        $_POST = [];
    }

    // =========================================================================
    // Fluent API / Config 테스트 (CI4 불필요)
    // =========================================================================

    public function testFluentApiReturnsInstance(): void
    {
        $result = $this->crud
            ->setTable('users')
            ->setSubject('사용자')
            ->setPerPage(20);

        $this->assertInstanceOf(Ci4Crud::class, $result);
    }

    public function testSetTableStoresValue(): void
    {
        $this->crud->setTable('orders');
        $this->assertSame('orders', $this->crud->getConfig()->table);
    }

    public function testSetSubjectStoresValues(): void
    {
        $this->crud->setSubject('사용자', '사용자 목록');
        $this->assertSame('사용자', $this->crud->getConfig()->subject);
        $this->assertSame('사용자 목록', $this->crud->getConfig()->subjectPlural);
    }

    public function testSetSubjectDefaultPlural(): void
    {
        $this->crud->setSubject('상품');
        $this->assertSame('상품', $this->crud->getConfig()->subjectPlural);
    }

    public function testPermissionsDefault(): void
    {
        $config = $this->crud->getConfig();
        $this->assertTrue($config->canAdd);
        $this->assertTrue($config->canEdit);
        $this->assertTrue($config->canDelete);
        $this->assertFalse($config->canRead);
        $this->assertFalse($config->canClone);
        $this->assertTrue($config->canExport);
    }

    public function testSetReadAndClone(): void
    {
        $this->crud->setRead()->setClone();
        $this->assertTrue($this->crud->getConfig()->canRead);
        $this->assertTrue($this->crud->getConfig()->canClone);
    }

    public function testUnsetOperationsDisablesAll(): void
    {
        $this->crud->unsetOperations();
        $config = $this->crud->getConfig();
        $this->assertFalse($config->canAdd);
        $this->assertFalse($config->canEdit);
        $this->assertFalse($config->canDelete);
    }

    public function testColumnsStoresFields(): void
    {
        $this->crud->columns('name', 'email', 'status');
        $this->assertSame(['name', 'email', 'status'], $this->crud->getConfig()->columns);
    }

    public function testDisplayAsStoresLabel(): void
    {
        $this->crud->displayAs('created_at', '등록일');
        $this->assertSame('등록일', $this->crud->getConfig()->labels['created_at']);
    }

    public function testSetRelationStoresConfig(): void
    {
        $this->crud->setRelation('dept_id', 'departments', 'dept_name');
        $relation = $this->crud->getConfig()->relations['dept_id'];
        $this->assertSame('departments', $relation['table']);
        $this->assertSame('dept_name', $relation['label']);
        $this->assertFalse($relation['dynamic']);
    }

    public function testSetRulesStoresRules(): void
    {
        $this->crud->setRules(['email' => 'required|valid_email']);
        $this->assertSame('required|valid_email', $this->crud->getConfig()->rules['email']);
    }

    public function testCallbackBeforeInsertStored(): void
    {
        $fn = fn($data) => $data;
        $this->crud->callbackBeforeInsert($fn);
        $this->assertCount(1, $this->crud->getConfig()->callbacks['before_insert']);
    }

    public function testRenderSchemaReturnsArray(): void
    {
        $schema = $this->crud
            ->setTable('users')
            ->setSubject('사용자')
            ->renderSchema();

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('subject', $schema);
        $this->assertArrayHasKey('permissions', $schema);
        $this->assertArrayHasKey('primaryKey', $schema);
        $this->assertSame('사용자', $schema['subject']);
    }

    public function testDefaultOrderingStored(): void
    {
        $this->crud->defaultOrdering('created_at', 'DESC');
        $order = $this->crud->getConfig()->defaultOrder;
        $this->assertSame('created_at', $order['field']);
        $this->assertSame('desc', $order['dir']);
    }

    // =========================================================================
    // ActionManager 테스트
    // =========================================================================

    public function testActionManagerDefaultsList(): void
    {
        $manager = new ActionManager();
        $this->assertSame('list', $manager->detect());
    }

    public function testActionManagerDetectsSchema(): void
    {
        $_GET['action'] = 'schema';
        $this->assertSame('schema', (new ActionManager())->detect());
    }

    public function testActionManagerDetectsInsert(): void
    {
        $_GET['action'] = 'insert';
        $this->assertSame('insert', (new ActionManager())->detect());
    }

    public function testActionManagerRejectsInvalidAction(): void
    {
        $_GET['action'] = 'drop_table';
        $this->assertSame('list', (new ActionManager())->detect());
    }

    public function testActionManagerGetId(): void
    {
        $_GET['id'] = '42';
        $this->assertSame('42', (new ActionManager())->getId());
    }

    public function testActionManagerGetIdNullWhenMissing(): void
    {
        $this->assertNull((new ActionManager())->getId());
    }

    // =========================================================================
    // SchemaSerializer 테스트 (DB 불필요 — SchemaReader 모킹)
    // =========================================================================

    public function testSchemaSerializerReturnsRequiredKeys(): void
    {
        $config  = new CrudConfig();
        $config->subject    = '주문';
        $config->primaryKey = 'id';
        $config->table      = 'orders';

        $mockReader = $this->createMock(SchemaReader::class);
        $mockReader->method('getColumns')->willReturn([]);
        $mockReader->method('getPrimaryKey')->willReturn('id');

        $schema = (new SchemaSerializer($config, $mockReader))->toArray();

        $this->assertArrayHasKey('subject', $schema);
        $this->assertArrayHasKey('primaryKey', $schema);
        $this->assertArrayHasKey('perPage', $schema);
        $this->assertArrayHasKey('permissions', $schema);
        $this->assertArrayHasKey('columns', $schema);
        $this->assertArrayHasKey('formFields', $schema);
        $this->assertArrayHasKey('defaultOrder', $schema);
    }

    public function testSchemaSerializerPermissionsReflectConfig(): void
    {
        $config             = new CrudConfig();
        $config->primaryKey = 'id';
        $config->canRead    = true;
        $config->canClone   = true;
        $config->canExport  = false;

        $mockReader = $this->createMock(SchemaReader::class);
        $mockReader->method('getColumns')->willReturn([]);

        $schema = (new SchemaSerializer($config, $mockReader))->toArray();

        $this->assertTrue($schema['permissions']['read']);
        $this->assertTrue($schema['permissions']['clone']);
        $this->assertFalse($schema['permissions']['export']);
    }

    public function testSchemaSerializerAppliesDisplayAs(): void
    {
        $config             = new CrudConfig();
        $config->primaryKey = 'id';
        $config->columns    = ['name', 'email'];
        $config->labels     = ['email' => '이메일 주소'];

        $mockField       = (object)['name' => 'email', 'type' => 'varchar', 'max_length' => 255, 'primary_key' => false];
        $mockNameField   = (object)['name' => 'name',  'type' => 'varchar', 'max_length' => 100, 'primary_key' => false];

        $mockReader = $this->createMock(SchemaReader::class);
        $mockReader->method('getColumns')->willReturn([$mockNameField, $mockField]);

        $schema = (new SchemaSerializer($config, $mockReader))->toArray();

        $emailCol = array_values(array_filter($schema['columns'], fn($c) => $c['field'] === 'email'))[0] ?? null;
        $this->assertNotNull($emailCol);
        $this->assertSame('이메일 주소', $emailCol['title']);
    }

    public function testSchemaSerializerFormFieldsContainModes(): void
    {
        $config             = new CrudConfig();
        $config->primaryKey = 'id';

        $mockReader = $this->createMock(SchemaReader::class);
        $mockReader->method('getColumns')->willReturn([]);

        $schema = (new SchemaSerializer($config, $mockReader))->toArray();

        $this->assertArrayHasKey('add',   $schema['formFields']);
        $this->assertArrayHasKey('edit',  $schema['formFields']);
        $this->assertArrayHasKey('read',  $schema['formFields']);
        $this->assertArrayHasKey('clone', $schema['formFields']);
    }

    // =========================================================================
    // DeleteHandler 콜백 테스트 (DB 불필요)
    // =========================================================================

    public function testDeleteHandlerCallbackCancels(): void
    {
        $config = new CrudConfig();
        $config->callbacks['before_delete'][] = fn($id) => false;

        $handler = new DeleteHandler($config);
        $result  = $handler->handle(1);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('취소', $result['message']);
    }

    public function testDeleteMultipleHandlerEmptyIds(): void
    {
        $config  = new CrudConfig();
        $handler = new DeleteHandler($config);
        $result  = $handler->handleMultiple([]);

        $this->assertFalse($result['success']);
    }

    public function testDeleteMultipleCallbackCancels(): void
    {
        $config = new CrudConfig();
        $config->callbacks['before_delete_multiple'][] = fn($ids) => false;

        $handler = new DeleteHandler($config);
        $result  = $handler->handleMultiple([1, 2, 3]);

        $this->assertFalse($result['success']);
    }

    // =========================================================================
    // SchemaReader 타입 추론 테스트
    // =========================================================================

    public function testSchemaReaderInfersNumericType(): void
    {
        $reader = new SchemaReader();
        $field  = (object)['type' => 'int', 'max_length' => 11];
        $this->assertSame('numeric', $reader->inferFieldType($field));
    }

    public function testSchemaReaderInfersDatetimeType(): void
    {
        $reader = new SchemaReader();
        $field  = (object)['type' => 'datetime', 'max_length' => 0];
        $this->assertSame('datetime', $reader->inferFieldType($field));
    }

    public function testSchemaReaderInfersBooleanType(): void
    {
        $reader = new SchemaReader();
        $field  = (object)['type' => 'tinyint', 'max_length' => 1];
        $this->assertSame('boolean', $reader->inferFieldType($field));
    }

    public function testSchemaReaderInfersTextareaForLongVarchar(): void
    {
        $reader = new SchemaReader();
        $field  = (object)['type' => 'varchar', 'max_length' => 1000];
        $this->assertSame('textarea', $reader->inferFieldType($field));
    }

    public function testSchemaReaderInfersStringDefault(): void
    {
        $reader = new SchemaReader();
        $field  = (object)['type' => 'varchar', 'max_length' => 255];
        $this->assertSame('string', $reader->inferFieldType($field));
    }
}
