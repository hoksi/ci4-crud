<?php

namespace Hoksi\Ci4Crud\FieldTypes;

class FieldTypeRegistry
{
    private static array $types = [
        'string'                  => StringType::class,
        'textarea'                => TextareaType::class,
        'numeric'                 => NumericType::class,
        'float'                   => FloatType::class,
        'boolean'                 => BooleanType::class,
        'date'                    => DateType::class,
        'datetime'                => DateTimeType::class,
        'native_date'             => NativeDateType::class,
        'native_time'             => NativeTimeType::class,
        'dropdown'                => DropdownType::class,
        'dropdown_search'         => SearchDropdownType::class,
        'multiselect_native'      => MultiSelectNativeType::class,
        'multiselect_searchable'  => MultiSelectSearchableType::class,
        'enum'                    => EnumType::class,
        'password'                => PasswordType::class,
        'password_toggle'         => PasswordToggleType::class,
        'email'                   => EmailType::class,
        'color'                   => ColorType::class,
        'upload_file'             => UploadType::class,
        'hidden'                  => HiddenType::class,
        'invisible'               => InvisibleType::class,
        'virtual'                 => VirtualType::class,
        'readonly'                => ReadonlyType::class,
        'wysiwyg'                 => WysiwygType::class,
        'relation'                => RelationType::class,
        'relation_nton'           => RelationNtoNType::class,
    ];

    public static function make(string $type, array $options = []): FieldTypeInterface
    {
        $class = self::$types[$type] ?? StringType::class;

        return new $class($options);
    }

    public static function register(string $type, string $class): void
    {
        if (!is_a($class, FieldTypeInterface::class, true)) {
            throw new \InvalidArgumentException(
                "{$class} must implement " . FieldTypeInterface::class
            );
        }

        self::$types[$type] = $class;
    }

    public static function has(string $type): bool
    {
        return isset(self::$types[$type]);
    }

    public static function all(): array
    {
        return array_keys(self::$types);
    }
}
