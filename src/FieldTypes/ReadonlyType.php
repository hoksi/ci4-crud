<?php

namespace Hoksi\Ci4Crud\FieldTypes;

class ReadonlyType extends AbstractFieldType
{
    public function getType(): string { return 'readonly'; }

    public function isStorable(): bool { return false; }
}
