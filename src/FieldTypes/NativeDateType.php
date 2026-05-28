<?php

namespace Hoksi\Ci4Crud\FieldTypes;

class NativeDateType extends AbstractFieldType
{
    public function getType(): string { return 'native_date'; }
}
