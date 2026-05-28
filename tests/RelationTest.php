<?php

namespace Hoksi\Ci4Crud\Tests;

use Hoksi\Ci4Crud\Config\CrudConfig;
use Hoksi\Ci4Crud\Core\RelationHandler;
use Hoksi\Ci4Crud\Relations\DependentRelation;
use Hoksi\Ci4Crud\Relations\ManyToManyRelation;
use Hoksi\Ci4Crud\Relations\OneToManyRelation;
use PHPUnit\Framework\TestCase;

class RelationTest extends TestCase
{
    // =========================================================================
    // OneToManyRelation 테스트
    // =========================================================================

    public function testOneToManyToSchemaArray(): void
    {
        $relation = new OneToManyRelation(
            foreignKey: 'dept_id',
            table:      'departments',
            labelField: 'dept_name',
            dynamic:    false,
        );

        $schema = $relation->toSchemaArray();

        $this->assertSame('dropdown', $schema['type']);
        $this->assertSame('departments', $schema['table']);
        $this->assertSame('dept_name', $schema['labelField']);
        $this->assertFalse($schema['dynamic']);
    }

    public function testOneToManyDynamicToSchemaArray(): void
    {
        $relation = new OneToManyRelation(
            foreignKey: 'country_id',
            table:      'countries',
            labelField: 'name',
            dynamic:    true,
        );

        $schema = $relation->toSchemaArray();

        $this->assertSame('relation', $schema['type']);
        $this->assertTrue($schema['dynamic']);
    }

    public function testOneToManyDefaultValueField(): void
    {
        $relation = new OneToManyRelation('fk', 'table', 'label');
        $schema   = $relation->toSchemaArray();
        $this->assertSame('id', $schema['valueField']);
    }

    // =========================================================================
    // ManyToManyRelation 테스트
    // =========================================================================

    public function testManyToManyToSchemaArray(): void
    {
        $relation = new ManyToManyRelation(
            field:         'actors',
            junctionTable: 'film_actor',
            relatedTable:  'actors',
            junctionFk:    'film_id',
            relatedFk:     'actor_id',
            labelField:    'full_name',
        );

        $schema = $relation->toSchemaArray();

        $this->assertSame('relation_nton', $schema['type']);
        $this->assertSame('film_actor', $schema['junctionTable']);
        $this->assertSame('actors', $schema['relatedTable']);
        $this->assertSame('film_id', $schema['junctionFk']);
        $this->assertSame('actor_id', $schema['relatedFk']);
        $this->assertSame('full_name', $schema['labelField']);
        $this->assertTrue($schema['multiple']);
    }

    public function testManyToManyDefaultValueField(): void
    {
        $relation = new ManyToManyRelation('f', 'jt', 'rt', 'jfk', 'rfk', 'label');
        $this->assertSame('id', $relation->toSchemaArray()['valueField']);
    }

    // =========================================================================
    // DependentRelation 테스트
    // =========================================================================

    public function testDependentRelationToSchemaArray(): void
    {
        $relation = new DependentRelation(
            childField:  'city_id',
            parentField: 'country_id',
            table:       'cities',
            labelField:  'city_name',
            foreignKey:  'country_id',
        );

        $schema = $relation->toSchemaArray();

        $this->assertSame('dependent', $schema['type']);
        $this->assertSame('cities', $schema['table']);
        $this->assertSame('city_name', $schema['labelField']);
        $this->assertSame('country_id', $schema['foreignKey']);
        $this->assertSame('country_id', $schema['parentField']);
    }

    public function testDependentRelationDefaultValueField(): void
    {
        $relation = new DependentRelation('child', 'parent', 'table', 'label', 'fk');
        $this->assertSame('id', $relation->toSchemaArray()['valueField']);
    }

    // =========================================================================
    // RelationHandler 테스트
    // =========================================================================

    public function testRelationHandlerReturnsEmptyForUnknownField(): void
    {
        $config  = new CrudConfig();
        $handler = new RelationHandler($config);

        $result = $handler->getOptions('nonexistent_field', 'search');
        $this->assertSame([], $result);
    }

    public function testRelationHandlerSyncNtoNSkipsWhenNoRelations(): void
    {
        $config  = new CrudConfig();
        $handler = new RelationHandler($config);

        // N:N 관계가 없으면 예외 없이 실행
        $handler->syncNtoN(1, ['name' => 'test']);
        $this->assertTrue(true);
    }

    public function testRelationHandlerSyncNtoNSkipsFieldNotInData(): void
    {
        $config                       = new CrudConfig();
        $config->relationsNtoN['tags'] = [
            'junctionTable' => 'post_tag',
            'relatedTable'  => 'tags',
            'junctionFk'    => 'post_id',
            'relatedFk'     => 'tag_id',
            'label'         => 'name',
        ];

        $handler = new RelationHandler($config);

        // tags 필드가 postData에 없으면 syncJunction 호출 안 됨
        $handler->syncNtoN(1, ['title' => 'Hello']);
        $this->assertTrue(true);
    }

    // =========================================================================
    // CrudConfig 관계 설정 테스트
    // =========================================================================

    public function testSetRelationNtoNStoresAllFields(): void
    {
        $config = new CrudConfig();
        $config->relationsNtoN['actors'] = [
            'junctionTable' => 'film_actor',
            'relatedTable'  => 'actors',
            'junctionFk'    => 'film_id',
            'relatedFk'     => 'actor_id',
            'label'         => 'full_name',
        ];

        $rel = $config->relationsNtoN['actors'];
        $this->assertSame('film_actor', $rel['junctionTable']);
        $this->assertSame('actors', $rel['relatedTable']);
        $this->assertSame('film_id', $rel['junctionFk']);
        $this->assertSame('actor_id', $rel['relatedFk']);
        $this->assertSame('full_name', $rel['label']);
    }

    public function testSetDependentRelationStoresConfig(): void
    {
        $config                         = new CrudConfig();
        $config->relations['city_id'] = [
            'type'        => 'dependent',
            'parentField' => 'country_id',
            'table'       => 'cities',
            'label'       => 'city_name',
            'fk'          => 'country_id',
        ];

        $rel = $config->relations['city_id'];
        $this->assertSame('dependent', $rel['type']);
        $this->assertSame('country_id', $rel['parentField']);
        $this->assertSame('cities', $rel['table']);
    }
}
