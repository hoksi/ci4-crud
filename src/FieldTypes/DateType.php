<?php

namespace Hoksi\Ci4Crud\FieldTypes;

class DateType extends AbstractFieldType
{
    public function getType(): string { return 'date'; }

    public function toSchemaArray(): array
    {
        return array_merge(parent::toSchemaArray(), [
            'dateFormat' => $this->options['dateFormat'] ?? 'Y-m-d',
        ]);
    }
}
