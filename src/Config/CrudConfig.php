<?php

namespace Hoksi\Ci4Crud\Config;

class CrudConfig
{
    public string  $table           = '';
    public string  $subject         = '';
    public string  $subjectPlural   = '';
    public ?string $primaryKey      = null;
    public array   $columns         = [];
    public array   $addFields       = [];
    public array   $editFields      = [];
    public array   $readFields      = [];
    public array   $cloneFields     = [];
    public array   $labels          = [];
    public array   $fieldTypes      = [];
    public array   $fieldOptions    = [];
    public array   $relations       = [];
    public array   $relationsNtoN   = [];
    public array   $callbacks       = [];
    public array   $where           = [];
    public array   $rules           = [];
    public ?string $validationGroup = null;
    public array   $requiredFields  = [];
    public array   $uniqueFields    = [];
    public array   $readOnlyFields  = [];
    public array   $uploadFields    = [];
    public array   $groupFields     = [];
    public array   $actionButtons   = [];
    public array   $defaultOrder    = [];
    public array   $searchableFields = [];
    public array   $hiddenColumns   = [];
    public bool    $canAdd          = true;
    public bool    $canEdit         = true;
    public bool    $canDelete       = true;
    public bool    $canRead         = false;
    public bool    $canClone        = false;
    public bool    $canExport       = true;
    public bool    $canDeleteMultiple = true;
    public bool    $softDelete      = false;
    public bool    $useTimestamps   = false;
    public int     $perPage         = 20;
    public string  $language        = 'Korean';
    public ?object $model           = null;
}
