<?php

namespace Hoksi\Ci4Crud\FieldTypes;

interface FieldTypeInterface
{
    public function getType(): string;

    public function toSchemaArray(): array;

    public function isStorable(): bool;

    public function isVisible(): bool;
}
