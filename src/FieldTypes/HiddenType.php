<?php

namespace Hoksi\Ci4Crud\FieldTypes;

class HiddenType extends AbstractFieldType
{
    public function getType(): string { return 'hidden'; }

    public function isVisible(): bool { return false; }
}
