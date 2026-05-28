<?php

namespace Hoksi\Ci4Crud\FieldTypes;

class NativeTimeType extends AbstractFieldType
{
    public function getType(): string { return 'native_time'; }
}
