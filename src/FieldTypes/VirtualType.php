<?php

namespace Hoksi\Ci4Crud\FieldTypes;

class VirtualType extends AbstractFieldType
{
    public function getType(): string { return 'virtual'; }

    public function isStorable(): bool { return false; }
}
