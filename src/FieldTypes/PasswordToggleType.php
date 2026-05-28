<?php

namespace Hoksi\Ci4Crud\FieldTypes;

class PasswordToggleType extends AbstractFieldType
{
    public function getType(): string { return 'password_toggle'; }

    public function toSchemaArray(): array
    {
        return array_merge(parent::toSchemaArray(), ['toggle' => true]);
    }
}
