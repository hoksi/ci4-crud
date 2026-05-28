<?php

namespace Hoksi\Ci4Crud\Tests;

use Hoksi\Ci4Crud\FieldTypes\AbstractFieldType;
use Hoksi\Ci4Crud\FieldTypes\BooleanType;
use Hoksi\Ci4Crud\FieldTypes\ColorType;
use Hoksi\Ci4Crud\FieldTypes\DateTimeType;
use Hoksi\Ci4Crud\FieldTypes\DateType;
use Hoksi\Ci4Crud\FieldTypes\DropdownType;
use Hoksi\Ci4Crud\FieldTypes\EmailType;
use Hoksi\Ci4Crud\FieldTypes\EnumType;
use Hoksi\Ci4Crud\FieldTypes\FieldTypeInterface;
use Hoksi\Ci4Crud\FieldTypes\FieldTypeRegistry;
use Hoksi\Ci4Crud\FieldTypes\FloatType;
use Hoksi\Ci4Crud\FieldTypes\HiddenType;
use Hoksi\Ci4Crud\FieldTypes\InvisibleType;
use Hoksi\Ci4Crud\FieldTypes\MultiSelectNativeType;
use Hoksi\Ci4Crud\FieldTypes\MultiSelectSearchableType;
use Hoksi\Ci4Crud\FieldTypes\NativeDateType;
use Hoksi\Ci4Crud\FieldTypes\NativeTimeType;
use Hoksi\Ci4Crud\FieldTypes\NumericType;
use Hoksi\Ci4Crud\FieldTypes\PasswordToggleType;
use Hoksi\Ci4Crud\FieldTypes\PasswordType;
use Hoksi\Ci4Crud\FieldTypes\ReadonlyType;
use Hoksi\Ci4Crud\FieldTypes\RelationNtoNType;
use Hoksi\Ci4Crud\FieldTypes\RelationType;
use Hoksi\Ci4Crud\FieldTypes\SearchDropdownType;
use Hoksi\Ci4Crud\FieldTypes\StringType;
use Hoksi\Ci4Crud\FieldTypes\TextareaType;
use Hoksi\Ci4Crud\FieldTypes\UploadType;
use Hoksi\Ci4Crud\FieldTypes\VirtualType;
use Hoksi\Ci4Crud\FieldTypes\WysiwygType;
use PHPUnit\Framework\TestCase;

class FieldTypeTest extends TestCase
{
    // =========================================================================
    // FieldTypeInterface 구현 검증
    // =========================================================================

    /** @dataProvider allFieldTypeProvider */
    public function testImplementsInterface(FieldTypeInterface $type): void
    {
        $this->assertInstanceOf(FieldTypeInterface::class, $type);
    }

    /** @dataProvider allFieldTypeProvider */
    public function testToSchemaArrayContainsType(FieldTypeInterface $type): void
    {
        $schema = $type->toSchemaArray();
        $this->assertArrayHasKey('type', $schema);
        $this->assertSame($type->getType(), $schema['type']);
    }

    public static function allFieldTypeProvider(): array
    {
        return [
            'string'                 => [new StringType()],
            'textarea'               => [new TextareaType()],
            'numeric'                => [new NumericType()],
            'float'                  => [new FloatType()],
            'boolean'                => [new BooleanType()],
            'date'                   => [new DateType()],
            'datetime'               => [new DateTimeType()],
            'native_date'            => [new NativeDateType()],
            'native_time'            => [new NativeTimeType()],
            'dropdown'               => [new DropdownType()],
            'dropdown_search'        => [new SearchDropdownType()],
            'multiselect_native'     => [new MultiSelectNativeType()],
            'multiselect_searchable' => [new MultiSelectSearchableType()],
            'enum'                   => [new EnumType()],
            'password'               => [new PasswordType()],
            'password_toggle'        => [new PasswordToggleType()],
            'email'                  => [new EmailType()],
            'color'                  => [new ColorType()],
            'upload_file'            => [new UploadType()],
            'hidden'                 => [new HiddenType()],
            'invisible'              => [new InvisibleType()],
            'virtual'                => [new VirtualType()],
            'readonly'               => [new ReadonlyType()],
            'wysiwyg'                => [new WysiwygType()],
            'relation'               => [new RelationType()],
            'relation_nton'          => [new RelationNtoNType()],
        ];
    }

    // =========================================================================
    // 개별 필드 타입 검증
    // =========================================================================

    public function testFloatTypeHasStep(): void
    {
        $schema = (new FloatType())->toSchemaArray();
        $this->assertArrayHasKey('step', $schema);
        $this->assertSame('0.01', $schema['step']);
    }

    public function testDateTypeHasDateFormat(): void
    {
        $schema = (new DateType())->toSchemaArray();
        $this->assertArrayHasKey('dateFormat', $schema);
    }

    public function testDateTimeTypeHasDateFormat(): void
    {
        $schema = (new DateTimeType())->toSchemaArray();
        $this->assertSame('Y-m-d H:i:s', $schema['dateFormat']);
    }

    public function testDropdownTypeIncludesOptions(): void
    {
        $opts   = ['active' => '활성', 'inactive' => '비활성'];
        $schema = (new DropdownType($opts))->toSchemaArray();
        $this->assertSame($opts, $schema['options']);
    }

    public function testSearchDropdownTypeIsSearchable(): void
    {
        $schema = (new SearchDropdownType())->toSchemaArray();
        $this->assertTrue($schema['searchable']);
    }

    public function testMultiSelectNativeIsMultiple(): void
    {
        $schema = (new MultiSelectNativeType())->toSchemaArray();
        $this->assertTrue($schema['multiple']);
    }

    public function testMultiSelectSearchableIsMultipleAndSearchable(): void
    {
        $schema = (new MultiSelectSearchableType())->toSchemaArray();
        $this->assertTrue($schema['multiple']);
        $this->assertTrue($schema['searchable']);
    }

    public function testEnumTypeConvertsListToKeyValue(): void
    {
        $schema = (new EnumType(['apple', 'banana']))->toSchemaArray();
        $this->assertSame(['apple' => 'apple', 'banana' => 'banana'], $schema['options']);
    }

    public function testEnumTypeKeepsAssocArray(): void
    {
        $opts   = ['a' => 'Apple', 'b' => 'Banana'];
        $schema = (new EnumType($opts))->toSchemaArray();
        $this->assertSame($opts, $schema['options']);
    }

    public function testPasswordToggleHasToggle(): void
    {
        $schema = (new PasswordToggleType())->toSchemaArray();
        $this->assertTrue($schema['toggle']);
    }

    public function testUploadTypeIsNotStorable(): void
    {
        $this->assertFalse((new UploadType())->isStorable());
    }

    public function testUploadTypeSchema(): void
    {
        $schema = (new UploadType(['path' => 'uploads/', 'multiple' => true]))->toSchemaArray();
        $this->assertSame('uploads/', $schema['path']);
        $this->assertTrue($schema['multiple']);
    }

    public function testHiddenTypeIsNotVisible(): void
    {
        $this->assertFalse((new HiddenType())->isVisible());
        $this->assertTrue((new HiddenType())->isStorable());
    }

    public function testInvisibleTypeIsNeitherVisibleNorStorable(): void
    {
        $type = new InvisibleType();
        $this->assertFalse($type->isVisible());
        $this->assertFalse($type->isStorable());
    }

    public function testVirtualTypeIsNotStorable(): void
    {
        $this->assertFalse((new VirtualType())->isStorable());
    }

    public function testReadonlyTypeIsNotStorable(): void
    {
        $this->assertFalse((new ReadonlyType())->isStorable());
    }

    public function testWysiwygTypeHasEditor(): void
    {
        $schema = (new WysiwygType())->toSchemaArray();
        $this->assertArrayHasKey('editor', $schema);
        $this->assertSame('ckeditor5', $schema['editor']);
    }

    public function testRelationTypeSchema(): void
    {
        $schema = (new RelationType([
            'table'   => 'departments',
            'label'   => 'dept_name',
            'dynamic' => true,
        ]))->toSchemaArray();

        $this->assertSame('departments', $schema['table']);
        $this->assertSame('dept_name', $schema['label']);
        $this->assertTrue($schema['dynamic']);
    }

    public function testRelationNtoNTypeIsMultiple(): void
    {
        $schema = (new RelationNtoNType())->toSchemaArray();
        $this->assertTrue($schema['multiple']);
        $this->assertFalse((new RelationNtoNType())->isStorable());
    }

    // =========================================================================
    // FieldTypeRegistry 검증
    // =========================================================================

    public function testRegistryMakesStringType(): void
    {
        $this->assertInstanceOf(StringType::class, FieldTypeRegistry::make('string'));
    }

    public function testRegistryFallsBackToStringForUnknown(): void
    {
        $this->assertInstanceOf(StringType::class, FieldTypeRegistry::make('nonexistent_type'));
    }

    public function testRegistryHasAllBuiltinTypes(): void
    {
        $expected = [
            'string', 'textarea', 'numeric', 'float', 'boolean',
            'date', 'datetime', 'native_date', 'native_time',
            'dropdown', 'dropdown_search', 'multiselect_native', 'multiselect_searchable',
            'enum', 'password', 'password_toggle', 'email', 'color',
            'upload_file', 'hidden', 'invisible', 'virtual', 'readonly', 'wysiwyg',
            'relation', 'relation_nton',
        ];

        foreach ($expected as $type) {
            $this->assertTrue(FieldTypeRegistry::has($type), "Missing type: {$type}");
        }
    }

    public function testRegistryPassesOptionsToType(): void
    {
        $opts = ['active' => '활성'];
        $type = FieldTypeRegistry::make('dropdown', $opts);
        $this->assertSame($opts, $type->toSchemaArray()['options']);
    }

    public function testRegistryCustomTypeRegistration(): void
    {
        $customClass = new class(['value' => 'test']) extends AbstractFieldType {
            public function getType(): string { return 'custom_test'; }
        };

        FieldTypeRegistry::register('custom_test', $customClass::class);
        $this->assertTrue(FieldTypeRegistry::has('custom_test'));

        $instance = FieldTypeRegistry::make('custom_test');
        $this->assertSame('custom_test', $instance->getType());
    }

    public function testRegistryRejectsInvalidClass(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        FieldTypeRegistry::register('bad_type', \stdClass::class);
    }

    public function testAllMethodReturnsTypeNames(): void
    {
        $all = FieldTypeRegistry::all();
        $this->assertIsArray($all);
        $this->assertContains('string', $all);
        $this->assertContains('wysiwyg', $all);
    }
}
