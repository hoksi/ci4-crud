<?php

namespace Hoksi\Ci4Crud\FieldTypes;

class FloatType extends AbstractFieldType
{
    public function getType(): string { return 'float'; }

    public function toSchemaArray(): array
    {
        return array_merge(parent::toSchemaArray(), ['step' => '0.01']);
    }
}
