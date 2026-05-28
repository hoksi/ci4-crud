<?php

namespace Hoksi\Ci4Crud\Tests;

use Hoksi\Ci4Crud\Ci4Crud;
use PHPUnit\Framework\TestCase;

class Ci4CrudTest extends TestCase
{
    private Ci4Crud $crud;

    protected function setUp(): void
    {
        $this->crud = new Ci4Crud();
    }

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
}
