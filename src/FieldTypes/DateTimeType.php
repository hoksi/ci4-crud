<?php

namespace Hoksi\Ci4Crud\FieldTypes;

class DateTimeType extends AbstractFieldType
{
    public function getType(): string { return 'datetime'; }

    public function toSchemaArray(): array
    {
        return array_merge(parent::toSchemaArray(), [
            'dateFormat' => $this->options['dateFormat'] ?? 'Y-m-d H:i:s',
        ]);
    }
}
