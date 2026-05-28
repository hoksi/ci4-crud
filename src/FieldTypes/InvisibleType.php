<?php

namespace Hoksi\Ci4Crud\FieldTypes;

class InvisibleType extends AbstractFieldType
{
    public function getType(): string { return 'invisible'; }

    public function isVisible(): bool { return false; }

    public function isStorable(): bool { return false; }
}
