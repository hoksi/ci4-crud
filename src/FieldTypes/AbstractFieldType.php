<?php

namespace Hoksi\Ci4Crud\FieldTypes;

abstract class AbstractFieldType implements FieldTypeInterface
{
    public function __construct(protected array $options = []) {}

    public function isStorable(): bool
    {
        return true;
    }

    public function isVisible(): bool
    {
        return true;
    }

    public function toSchemaArray(): array
    {
        $schema = ['type' => $this->getType()];

        if (!empty($this->options)) {
            $schema['options'] = $this->options;
        }

        return $schema;
    }
}
