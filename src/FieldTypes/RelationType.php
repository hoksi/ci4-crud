<?php

namespace Hoksi\Ci4Crud\FieldTypes;

class RelationType extends AbstractFieldType
{
    public function getType(): string { return 'relation'; }

    public function toSchemaArray(): array
    {
        return [
            'type'     => $this->getType(),
            'table'    => $this->options['table']      ?? '',
            'label'    => $this->options['label']      ?? '',
            'dynamic'  => $this->options['dynamic']    ?? false,
        ];
    }
}
