<?php

namespace Hoksi\Ci4Crud\FieldTypes;

class EnumType extends AbstractFieldType
{
    public function getType(): string { return 'enum'; }

    public function toSchemaArray(): array
    {
        // options가 없으면 key=value 형태로 자동 생성
        $opts = $this->options;
        if (array_is_list($opts)) {
            $opts = array_combine($opts, $opts);
        }

        return ['type' => $this->getType(), 'options' => $opts];
    }
}
