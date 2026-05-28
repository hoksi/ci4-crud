<?php

namespace Hoksi\Ci4Crud\FieldTypes;

class WysiwygType extends AbstractFieldType
{
    public function getType(): string { return 'wysiwyg'; }

    public function toSchemaArray(): array
    {
        return array_merge(parent::toSchemaArray(), [
            'editor' => $this->options['editor'] ?? 'ckeditor5',
        ]);
    }
}
