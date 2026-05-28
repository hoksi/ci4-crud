<?php

namespace Hoksi\Ci4Crud\FieldTypes;

class MultiSelectNativeType extends AbstractFieldType
{
    public function getType(): string { return 'multiselect_native'; }

    public function toSchemaArray(): array
    {
        return [
            'type'     => $this->getType(),
            'options'  => $this->options,
            'multiple' => true,
        ];
    }
}
