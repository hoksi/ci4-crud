<?php

namespace Hoksi\Ci4Crud\FieldTypes;

class UploadType extends AbstractFieldType
{
    public function getType(): string { return 'upload_file'; }

    public function isStorable(): bool { return false; }

    public function toSchemaArray(): array
    {
        return [
            'type'     => $this->getType(),
            'multiple' => $this->options['multiple'] ?? false,
            'path'     => $this->options['path']     ?? '',
        ];
    }
}
