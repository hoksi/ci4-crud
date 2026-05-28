<?php

namespace Hoksi\Ci4Crud;

use Hoksi\Ci4Crud\Config\CrudConfig;
use Hoksi\Ci4Crud\Core\ActionManager;
use Hoksi\Ci4Crud\Core\DeleteHandler;
use Hoksi\Ci4Crud\Core\InsertHandler;
use Hoksi\Ci4Crud\Core\QueryHandler;
use Hoksi\Ci4Crud\Core\ReadHandler;
use Hoksi\Ci4Crud\Core\RelationHandler;
use Hoksi\Ci4Crud\Core\SchemaSerializer;
use Hoksi\Ci4Crud\Core\UpdateHandler;

class Ci4Crud
{
    private CrudConfig $config;

    public function __construct()
    {
        $this->config = new CrudConfig();
    }

    // -------------------------------------------------------------------------
    // 기본 설정
    // -------------------------------------------------------------------------

    public function setTable(string $table): static
    {
        $this->config->table = $table;
        return $this;
    }

    public function setSubject(string $singular, string $plural = ''): static
    {
        $this->config->subject       = $singular;
        $this->config->subjectPlural = $plural ?: $singular;
        return $this;
    }

    public function setPrimaryKey(string $field): static
    {
        $this->config->primaryKey = $field;
        return $this;
    }

    public function setModel(object $model): static
    {
        $this->config->model = $model;
        return $this;
    }

    public function setLanguage(string $lang): static
    {
        $this->config->language = $lang;
        return $this;
    }

    public function where(string|array $field, mixed $value = null): static
    {
        $this->config->where[] = is_array($field) ? $field : [$field => $value];
        return $this;
    }

    public function setPerPage(int $n): static
    {
        $this->config->perPage = $n;
        return $this;
    }

    public function setSoftDelete(bool $flag = true): static
    {
        $this->config->softDelete = $flag;
        return $this;
    }

    public function setTimestamps(bool $flag = true): static
    {
        $this->config->useTimestamps = $flag;
        return $this;
    }

    // -------------------------------------------------------------------------
    // 데이터그리드 제어
    // -------------------------------------------------------------------------

    public function columns(string ...$fields): static
    {
        $this->config->columns = $fields;
        return $this;
    }

    public function unsetColumns(string ...$fields): static
    {
        $this->config->hiddenColumns = array_merge($this->config->hiddenColumns, $fields);
        return $this;
    }

    public function displayAs(string $field, string $label): static
    {
        $this->config->labels[$field] = $label;
        return $this;
    }

    public function defaultOrdering(string $field, string $dir = 'asc'): static
    {
        $this->config->defaultOrder = ['field' => $field, 'dir' => strtolower($dir)];
        return $this;
    }

    public function defaultColumnWidth(string $field, int $px): static
    {
        $this->config->fieldOptions[$field]['width'] = $px;
        return $this;
    }

    public function setSearchable(string ...$fields): static
    {
        $this->config->searchableFields = $fields;
        return $this;
    }

    public function callbackColumn(string $field, callable $fn): static
    {
        $this->config->callbacks['column'][$field] = $fn;
        return $this;
    }

    public function setActionButton(string $label, string $url, string $icon = ''): static
    {
        $this->config->actionButtons[] = ['type' => 'single', 'label' => $label, 'url' => $url, 'icon' => $icon];
        return $this;
    }

    public function setActionButtonMultiple(string $label, string $url, string $icon = ''): static
    {
        $this->config->actionButtons[] = ['type' => 'multiple', 'label' => $label, 'url' => $url, 'icon' => $icon];
        return $this;
    }

    public function unsetExport(): static
    {
        $this->config->canExport = false;
        return $this;
    }

    public function unsetFilters(): static
    {
        $this->config->fieldOptions['_filters'] = false;
        return $this;
    }

    public function unsetPagination(): static
    {
        $this->config->fieldOptions['_pagination'] = false;
        return $this;
    }

    public function inlineEditFields(string ...$fields): static
    {
        $this->config->fieldOptions['_inlineEdit'] = $fields;
        return $this;
    }

    // -------------------------------------------------------------------------
    // 폼 필드 제어
    // -------------------------------------------------------------------------

    public function fields(string ...$fields): static
    {
        $this->config->addFields  = $fields;
        $this->config->editFields = $fields;
        $this->config->readFields = $fields;
        $this->config->cloneFields = $fields;
        return $this;
    }

    public function addFields(string ...$fields): static
    {
        $this->config->addFields = $fields;
        return $this;
    }

    public function editFields(string ...$fields): static
    {
        $this->config->editFields = $fields;
        return $this;
    }

    public function readFields(string ...$fields): static
    {
        $this->config->readFields = $fields;
        return $this;
    }

    public function cloneFields(string ...$fields): static
    {
        $this->config->cloneFields = $fields;
        return $this;
    }

    public function unsetFields(string ...$fields): static
    {
        $this->config->fieldOptions['_unset'] = $fields;
        return $this;
    }

    public function unsetAddFields(string ...$fields): static
    {
        $this->config->fieldOptions['_unsetAdd'] = $fields;
        return $this;
    }

    public function unsetEditFields(string ...$fields): static
    {
        $this->config->fieldOptions['_unsetEdit'] = $fields;
        return $this;
    }

    public function readOnlyFields(string ...$fields): static
    {
        $this->config->readOnlyFields = $fields;
        return $this;
    }

    public function readOnlyAddFields(string ...$fields): static
    {
        $this->config->fieldOptions['_readOnlyAdd'] = $fields;
        return $this;
    }

    public function readOnlyEditFields(string ...$fields): static
    {
        $this->config->fieldOptions['_readOnlyEdit'] = $fields;
        return $this;
    }

    public function readOnlyCloneFields(string ...$fields): static
    {
        $this->config->fieldOptions['_readOnlyClone'] = $fields;
        return $this;
    }

    public function groupFields(string $title, string ...$fields): static
    {
        $this->config->groupFields[] = ['title' => $title, 'fields' => $fields];
        return $this;
    }

    public function requiredFields(string ...$fields): static
    {
        $this->config->requiredFields = array_merge($this->config->requiredFields, $fields);
        return $this;
    }

    public function requiredAddFields(string ...$fields): static
    {
        $this->config->fieldOptions['_requiredAdd'] = $fields;
        return $this;
    }

    public function requiredEditFields(string ...$fields): static
    {
        $this->config->fieldOptions['_requiredEdit'] = $fields;
        return $this;
    }

    public function requiredCloneFields(string ...$fields): static
    {
        $this->config->fieldOptions['_requiredClone'] = $fields;
        return $this;
    }

    public function uniqueFields(string ...$fields): static
    {
        $this->config->uniqueFields = array_merge($this->config->uniqueFields, $fields);
        return $this;
    }

    // -------------------------------------------------------------------------
    // 필드 타입
    // -------------------------------------------------------------------------

    public function fieldType(string $field, string $type, array $options = []): static
    {
        $this->config->fieldTypes[$field] = ['type' => $type, 'options' => $options, 'form' => 'all'];
        return $this;
    }

    public function fieldTypeAddForm(string $field, string $type, array $options = []): static
    {
        $this->config->fieldTypes[$field . ':add'] = ['type' => $type, 'options' => $options, 'form' => 'add'];
        return $this;
    }

    public function fieldTypeEditForm(string $field, string $type, array $options = []): static
    {
        $this->config->fieldTypes[$field . ':edit'] = ['type' => $type, 'options' => $options, 'form' => 'edit'];
        return $this;
    }

    public function fieldTypeReadForm(string $field, string $type, array $options = []): static
    {
        $this->config->fieldTypes[$field . ':read'] = ['type' => $type, 'options' => $options, 'form' => 'read'];
        return $this;
    }

    public function fieldTypeCloneForm(string $field, string $type, array $options = []): static
    {
        $this->config->fieldTypes[$field . ':clone'] = ['type' => $type, 'options' => $options, 'form' => 'clone'];
        return $this;
    }

    public function fieldTypeFormFields(string $field, string $type, array $options = []): static
    {
        return $this->fieldType($field, $type, $options);
    }

    public function setTexteditor(string $field): static
    {
        return $this->fieldType($field, 'wysiwyg');
    }

    public function unsetTexteditor(string $field): static
    {
        unset($this->config->fieldTypes[$field]);
        return $this;
    }

    // -------------------------------------------------------------------------
    // 관계 설정
    // -------------------------------------------------------------------------

    public function setRelation(string $fk, string $table, string $label): static
    {
        $this->config->relations[$fk] = ['table' => $table, 'label' => $label, 'dynamic' => false];
        return $this;
    }

    public function setRelationDynamic(string $fk, string $table, string $label): static
    {
        $this->config->relations[$fk] = ['table' => $table, 'label' => $label, 'dynamic' => true];
        return $this;
    }

    public function setRelationNtoN(
        string $field,
        string $junctionTable,
        string $relatedTable,
        string $junctionFk,
        string $relatedFk,
        string $label,
    ): static {
        $this->config->relationsNtoN[$field] = compact(
            'junctionTable', 'relatedTable', 'junctionFk', 'relatedFk', 'label'
        );
        return $this;
    }

    public function setDependentRelation(
        string $childField,
        string $parentField,
        string $table,
        string $labelField,
        string $fk,
    ): static {
        $this->config->relations[$childField] = [
            'type'        => 'dependent',
            'parentField' => $parentField,
            'table'       => $table,
            'label'       => $labelField,
            'fk'          => $fk,
        ];
        return $this;
    }

    // -------------------------------------------------------------------------
    // 콜백
    // -------------------------------------------------------------------------

    public function callbackBeforeInsert(callable $fn): static  { $this->config->callbacks['before_insert'][]  = $fn; return $this; }
    public function callbackAfterInsert(callable $fn): static   { $this->config->callbacks['after_insert'][]   = $fn; return $this; }
    public function callbackInsert(callable $fn): static        { $this->config->callbacks['insert']           = $fn; return $this; }
    public function callbackBeforeUpdate(callable $fn): static  { $this->config->callbacks['before_update'][]  = $fn; return $this; }
    public function callbackAfterUpdate(callable $fn): static   { $this->config->callbacks['after_update'][]   = $fn; return $this; }
    public function callbackUpdate(callable $fn): static        { $this->config->callbacks['update']           = $fn; return $this; }
    public function callbackBeforeDelete(callable $fn): static  { $this->config->callbacks['before_delete'][]  = $fn; return $this; }
    public function callbackAfterDelete(callable $fn): static   { $this->config->callbacks['after_delete'][]   = $fn; return $this; }
    public function callbackDelete(callable $fn): static        { $this->config->callbacks['delete']           = $fn; return $this; }
    public function callbackBeforeDeleteMultiple(callable $fn): static { $this->config->callbacks['before_delete_multiple'][] = $fn; return $this; }
    public function callbackAfterDeleteMultiple(callable $fn): static  { $this->config->callbacks['after_delete_multiple'][]  = $fn; return $this; }
    public function callbackDeleteMultiple(callable $fn): static       { $this->config->callbacks['delete_multiple']          = $fn; return $this; }
    public function callbackAddField(string $field, callable $fn): static  { $this->config->callbacks['field_add'][$field]  = $fn; return $this; }
    public function callbackEditField(string $field, callable $fn): static { $this->config->callbacks['field_edit'][$field] = $fn; return $this; }
    public function callbackReadField(string $field, callable $fn): static { $this->config->callbacks['field_read'][$field] = $fn; return $this; }
    public function callbackCloneField(string $field, callable $fn): static { $this->config->callbacks['field_clone'][$field] = $fn; return $this; }
    public function callbackQuery(callable $fn): static         { $this->config->callbacks['query']            = $fn; return $this; }
    public function callbackBeforeUpload(callable $fn): static  { $this->config->callbacks['before_upload'][]  = $fn; return $this; }
    public function callbackAfterUpload(callable $fn): static   { $this->config->callbacks['after_upload'][]   = $fn; return $this; }
    public function callbackUpload(callable $fn): static        { $this->config->callbacks['upload']           = $fn; return $this; }

    // -------------------------------------------------------------------------
    // 유효성 검사
    // -------------------------------------------------------------------------

    public function setRule(string $field, string $rule): static
    {
        $this->config->rules[$field] = $rule;
        return $this;
    }

    public function setRules(array $rules): static
    {
        $this->config->rules = array_merge($this->config->rules, $rules);
        return $this;
    }

    public function setValidationGroup(string $group): static
    {
        $this->config->validationGroup = $group;
        return $this;
    }

    // -------------------------------------------------------------------------
    // 파일 업로드
    // -------------------------------------------------------------------------

    public function setFieldUpload(string $field, string $path): static
    {
        $this->config->uploadFields[$field] = ['path' => $path, 'multiple' => false];
        return $this;
    }

    public function setFieldUploadMultiple(string $field, string $path): static
    {
        $this->config->uploadFields[$field] = ['path' => $path, 'multiple' => true];
        return $this;
    }

    // -------------------------------------------------------------------------
    // 권한 제어
    // -------------------------------------------------------------------------

    public function setAdd(): static    { $this->config->canAdd    = true;  return $this; }
    public function unsetAdd(): static  { $this->config->canAdd    = false; return $this; }
    public function setEdit(): static   { $this->config->canEdit   = true;  return $this; }
    public function unsetEdit(): static { $this->config->canEdit   = false; return $this; }
    public function setDelete(): static    { $this->config->canDelete    = true;  return $this; }
    public function unsetDelete(): static  { $this->config->canDelete    = false; return $this; }
    public function setRead(): static      { $this->config->canRead      = true;  return $this; }
    public function unsetRead(): static    { $this->config->canRead      = false; return $this; }
    public function setClone(): static     { $this->config->canClone     = true;  return $this; }
    public function unsetClone(): static   { $this->config->canClone     = false; return $this; }
    public function setDeleteMultiple(): static   { $this->config->canDeleteMultiple = true;  return $this; }
    public function unsetDeleteMultiple(): static { $this->config->canDeleteMultiple = false; return $this; }

    public function unsetOperations(): static
    {
        $this->config->canAdd    = false;
        $this->config->canEdit   = false;
        $this->config->canDelete = false;
        return $this;
    }

    // -------------------------------------------------------------------------
    // 렌더링
    // -------------------------------------------------------------------------

    public function renderJson(): mixed
    {
        $manager = new ActionManager();
        $action  = $manager->detect();
        $id      = $manager->getId();

        $postData = $_POST;

        $result = match($action) {
            'schema'          => (new SchemaSerializer($this->config))->toArray(),
            'list'            => (new QueryHandler($this->config))->list(),
            'read'            => (new ReadHandler($this->config))->handle($id ?? 0),
            'insert'          => (new InsertHandler($this->config))->handle($postData),
            'update'          => (new UpdateHandler($this->config))->handle($id ?? 0, $postData),
            'delete'          => (new DeleteHandler($this->config))->handle($id ?? 0),
            'delete_multiple' => (new DeleteHandler($this->config))->handleMultiple($postData['ids'] ?? []),
            'relation'        => (new RelationHandler($this->config))->getOptions(
                                     $_GET['field']        ?? '',
                                     $_GET['q']            ?? '',
                                     $_GET['parent_value'] ?? '',
                                 ),
            default           => ['success' => false, 'message' => '지원하지 않는 액션입니다.'],
        };

        // CI4 환경에서는 ResponseInterface 반환, 아닌 경우 배열 반환
        if (function_exists('service')) {
            return service('response')->setJSON($result);
        }

        return $result;
    }

    public function render(): string
    {
        // TODO: Phase 1 (Mode B) — StateManager로 상태 감지 후 HTML 반환
        return '';
    }

    public function renderSchema(): array
    {
        return (new SchemaSerializer($this->config))->toArray();
    }

    public function getConfig(): CrudConfig
    {
        return $this->config;
    }
}
