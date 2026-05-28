<?php

namespace Hoksi\Ci4Crud\FieldTypes;

class MultiSelectSearchableType extends AbstractFieldType
{
    public function getType(): string { return 'multiselect_searchable'; }

    public function toSchemaArray(): array
    {
        return [
            'type'       => $this->getType(),
            'options'    => $this->options,
            'multiple'   => true,
            'searchable' => true,
        ];
    }
}
