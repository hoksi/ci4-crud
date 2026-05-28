<?php

namespace Hoksi\Ci4Crud\FieldTypes;

class DropdownType extends AbstractFieldType
{
    public function getType(): string { return 'dropdown'; }

    public function toSchemaArray(): array
    {
        return [
            'type'    => $this->getType(),
            'options' => $this->options,
        ];
    }
}
