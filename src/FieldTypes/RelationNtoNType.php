<?php

namespace Hoksi\Ci4Crud\FieldTypes;

class RelationNtoNType extends AbstractFieldType
{
    public function getType(): string { return 'relation_nton'; }

    public function isStorable(): bool { return false; }

    public function toSchemaArray(): array
    {
        return [
            'type'          => $this->getType(),
            'junctionTable' => $this->options['junctionTable'] ?? '',
            'relatedTable'  => $this->options['relatedTable']  ?? '',
            'junctionFk'    => $this->options['junctionFk']    ?? '',
            'relatedFk'     => $this->options['relatedFk']     ?? '',
            'label'         => $this->options['label']         ?? '',
            'multiple'      => true,
        ];
    }
}
