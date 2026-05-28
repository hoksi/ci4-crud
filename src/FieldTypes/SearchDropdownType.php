<?php

namespace Hoksi\Ci4Crud\FieldTypes;

class SearchDropdownType extends AbstractFieldType
{
    public function getType(): string { return 'dropdown_search'; }

    public function toSchemaArray(): array
    {
        return [
            'type'       => $this->getType(),
            'options'    => $this->options,
            'searchable' => true,
        ];
    }
}
