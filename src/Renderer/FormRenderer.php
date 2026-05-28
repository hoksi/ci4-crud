<?php

namespace Hoksi\Ci4Crud\Renderer;

use Hoksi\Ci4Crud\Config\CrudConfig;
use Hoksi\Ci4Crud\Core\ReadHandler;
use Hoksi\Ci4Crud\Core\SchemaReader;
use Hoksi\Ci4Crud\Core\SchemaSerializer;

class FormRenderer
{
    private array  $schema;
    private array  $initScripts = [];

    public function __construct(private readonly CrudConfig $config)
    {
        $this->schema = (new SchemaSerializer($config, new SchemaReader()))->toArray();
    }

    // -------------------------------------------------------------------------
    // 공개 진입점
    // -------------------------------------------------------------------------

    public function renderAdd(): string
    {
        return $this->renderForm('add', [], '추가');
    }

    public function renderEdit(int|string $id): string
    {
        $result = (new ReadHandler($this->config))->handle($id);
        return $this->renderForm('edit', $result['data'] ?? [], '수정', $id);
    }

    public function renderRead(int|string $id): string
    {
        $result = (new ReadHandler($this->config))->handle($id);
        return $this->renderForm('read', $result['data'] ?? [], '조회', $id, readonly: true);
    }

    public function renderClone(int|string $id): string
    {
        $result = (new ReadHandler($this->config))->handle($id);
        return $this->renderForm('clone', $result['data'] ?? [], '복제');
    }

    /**
     * 레이아웃 <head>에 포함할 CDN 에셋 반환
     * 사용 중인 필드 타입에 따라 필요한 CSS/JS만 반환합니다.
     */
    public function renderAssets(string $mode = 'add'): string
    {
        $fields = $this->schema['formFields'][$mode] ?? [];
        $types  = array_column($fields, 'type');
        $assets = '';

        // flatpickr — date, datetime
        if (array_intersect($types, ['date', 'datetime'])) {
            $assets .= '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">' . "\n";
            $assets .= '<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>' . "\n";
            $assets .= '<script src="https://npmcdn.com/flatpickr/dist/l10n/ko.js"></script>' . "\n";
        }

        // Tom Select — dropdown_search, multiselect_searchable, relation, relation_nton, dependent
        $tomTypes = ['dropdown_search', 'multiselect_searchable', 'relation', 'relation_nton', 'dependent'];
        if (array_intersect($types, $tomTypes)) {
            $assets .= '<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">' . "\n";
            $assets .= '<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>' . "\n";
        }

        // CKEditor 5 — wysiwyg
        if (in_array('wysiwyg', $types, true)) {
            $assets .= '<script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>' . "\n";
        }

        return $assets;
    }

    // -------------------------------------------------------------------------
    // 폼 렌더링
    // -------------------------------------------------------------------------

    private function renderForm(
        string     $mode,
        array      $data,
        string     $label,
        int|string $id = 0,
        bool       $readonly = false,
    ): string {
        $this->initScripts = [];

        $subject = htmlspecialchars($this->schema['subject'], ENT_QUOTES, 'UTF-8');
        $base    = htmlspecialchars(strtok($_SERVER['REQUEST_URI'] ?? '', '?'), ENT_QUOTES, 'UTF-8');
        $action  = match($mode) {
            'edit'  => "{$base}?state=update&id={$id}",
            'read'  => '#',
            default => "{$base}?state=insert",
        };

        $fields = $this->schema['formFields'][$mode] ?? [];
        $inputs = $this->buildInputs($fields, $data, $readonly, $base);
        $inits  = $this->initScripts ? '<script>' . implode("\n", $this->initScripts) . '</script>' : '';

        $btnHtml = $readonly
            ? "<a href=\"{$base}\" class=\"btn btn-secondary\">목록으로</a>"
            : "<button type=\"submit\" class=\"btn btn-primary\">{$label}</button>
               <a href=\"{$base}\" class=\"btn btn-secondary ms-2\">취소</a>";

        return <<<HTML
<div class="ci4crud-form-wrapper">
  <h5 class="mb-3">{$subject} {$label}</h5>
  <div id="ci4crud_form_errors" class="alert alert-danger d-none"></div>
  <form id="ci4crud_form" method="post" action="{$action}" enctype="multipart/form-data">
    {$inputs}
    <div class="mt-3">{$btnHtml}</div>
  </form>
  {$inits}
  <script>
  document.getElementById('ci4crud_form')?.addEventListener('submit', async e => {
    e.preventDefault();
    const form = e.target;
    const res  = await fetch(form.action, {
      method:  'POST',
      body:    new FormData(form),
      headers: { 'Accept': 'application/json' },
    });
    const json = await res.json();
    const errEl = document.getElementById('ci4crud_form_errors');
    if (json.success) {
      location.href = '{$base}';
    } else {
      errEl.classList.remove('d-none');
      errEl.innerHTML = '<ul class="mb-0">'
        + Object.values(json.errors ?? { msg: json.message })
            .map(m => '<li>' + m + '</li>').join('')
        + '</ul>';
      window.scrollTo(0, errEl.getBoundingClientRect().top + window.scrollY - 20);
    }
  });
  </script>
</div>
HTML;
    }

    // -------------------------------------------------------------------------
    // 필드 입력 빌더
    // -------------------------------------------------------------------------

    private function buildInputs(array $fields, array $data, bool $readonly, string $base): string
    {
        $html = '';
        foreach ($fields as $field) {
            $name  = $field['field'];
            $title = htmlspecialchars($field['title'], ENT_QUOTES, 'UTF-8');
            $value = (string)($data[$name] ?? '');
            $type  = $field['type'];
            $req   = ($field['required'] ?? false) ? 'required' : '';
            $ro    = ($readonly || ($field['readonly'] ?? false));

            $input = $this->buildInput($name, $type, $value, $field, $req, $ro, $base);

            if (in_array($type, ['hidden', 'invisible', 'virtual'], true)) {
                $html .= $input;
                continue;
            }

            $reqBadge = ($field['required'] ?? false) ? ' <span class="text-danger">*</span>' : '';
            $html    .= <<<HTML
<div class="mb-3">
  <label class="form-label fw-semibold">{$title}{$reqBadge}</label>
  {$input}
</div>
HTML;
        }
        return $html;
    }

    private function buildInput(
        string $name,
        string $type,
        string $value,
        array  $field,
        string $req,
        bool   $ro,
        string $base,
    ): string {
        $eName  = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $eValue = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        $roAttr = $ro ? 'readonly disabled' : '';
        $uid    = 'ci4f_' . $name . '_' . substr(md5($name . $type), 0, 6);

        return match($type) {
            'string'     => "<input type=\"text\" id=\"{$uid}\" name=\"{$eName}\" class=\"form-control\" value=\"{$eValue}\" {$req} {$roAttr}>",
            'email'      => "<input type=\"email\" id=\"{$uid}\" name=\"{$eName}\" class=\"form-control\" value=\"{$eValue}\" {$req} {$roAttr}>",
            'numeric'    => "<input type=\"number\" id=\"{$uid}\" name=\"{$eName}\" class=\"form-control\" value=\"{$eValue}\" {$req} {$roAttr}>",
            'float'      => "<input type=\"number\" step=\"0.01\" id=\"{$uid}\" name=\"{$eName}\" class=\"form-control\" value=\"{$eValue}\" {$req} {$roAttr}>",
            'color'      => "<input type=\"color\" id=\"{$uid}\" name=\"{$eName}\" class=\"form-control form-control-color\" value=\"" . ($eValue ?: '#000000') . "\" {$roAttr}>",
            'textarea'   => "<textarea id=\"{$uid}\" name=\"{$eName}\" class=\"form-control\" rows=\"4\" {$req} {$roAttr}>{$eValue}</textarea>",
            'native_date'    => "<input type=\"date\" id=\"{$uid}\" name=\"{$eName}\" class=\"form-control\" value=\"{$eValue}\" {$req} {$roAttr}>",
            'native_time'    => "<input type=\"time\" id=\"{$uid}\" name=\"{$eName}\" class=\"form-control\" value=\"{$eValue}\" {$req} {$roAttr}>",

            'date'     => $this->buildFlatpickr($uid, $eName, $eValue, $req, $roAttr, false),
            'datetime' => $this->buildFlatpickr($uid, $eName, $eValue, $req, $roAttr, true),

            'boolean' => "<div class=\"form-check\"><input type=\"checkbox\" id=\"{$uid}\" name=\"{$eName}\" class=\"form-check-input\" value=\"1\""
                       . ($value ? ' checked' : '') . " {$roAttr}></div>",

            'password'       => "<input type=\"password\" id=\"{$uid}\" name=\"{$eName}\" class=\"form-control\" {$req} {$roAttr}>",
            'password_toggle'=> $this->buildPasswordToggle($uid, $eName, $req, $roAttr),

            'dropdown', 'enum' => $this->buildSelect($uid, $eName, $field['options'] ?? [], $value, $req, $roAttr),
            'dropdown_search'  => $this->buildTomSelect($uid, $eName, $field['options'] ?? [], $value, $req, $ro, false),

            'multiselect_native'      => $this->buildMultiSelectNative($uid, $eName, $field['options'] ?? [], $value, $ro),
            'multiselect_searchable'  => $this->buildTomSelect($uid, $eName, $field['options'] ?? [], $value, $req, $ro, true),

            'relation'      => $this->buildRelationField($uid, $eName, $field, $value, $req, $ro, $base, false),
            'relation_nton' => $this->buildRelationField($uid, $eName, $field, $value, $req, $ro, $base, true),
            'dependent'     => $this->buildDependentField($uid, $eName, $field, $value, $req, $ro, $base),

            'upload_file' => $this->buildUpload($uid, $eName, $eValue, $ro, $field['multiple'] ?? false),

            'wysiwyg'  => $this->buildWysiwyg($uid, $eName, $value, $ro),
            'readonly' => "<p class=\"form-control-plaintext\">{$eValue}</p>",
            'hidden'   => "<input type=\"hidden\" name=\"{$eName}\" value=\"{$eValue}\">",
            'invisible','virtual' => '',

            default => "<input type=\"text\" id=\"{$uid}\" name=\"{$eName}\" class=\"form-control\" value=\"{$eValue}\" {$req} {$roAttr}>",
        };
    }

    // -------------------------------------------------------------------------
    // 특수 필드 빌더
    // -------------------------------------------------------------------------

    private function buildFlatpickr(string $uid, string $name, string $value, string $req, string $ro, bool $withTime): string
    {
        $opts = $withTime ? '{ enableTime: true, dateFormat: "Y-m-d H:i:S", locale: "ko" }'
                          : '{ dateFormat: "Y-m-d", locale: "ko" }';

        $this->initScripts[] = "if(typeof flatpickr!=='undefined') flatpickr('#{$uid}', {$opts});";

        return "<input type=\"text\" id=\"{$uid}\" name=\"{$name}\" class=\"form-control\" value=\"{$value}\" {$req} {$ro} autocomplete=\"off\">";
    }

    private function buildPasswordToggle(string $uid, string $name, string $req, string $ro): string
    {
        return <<<HTML
<div class="input-group">
  <input type="password" id="{$uid}" name="{$name}" class="form-control" {$req} {$ro}>
  <button type="button" class="btn btn-outline-secondary"
    onclick="const i=document.getElementById('{$uid}');i.type=i.type==='password'?'text':'password'">
    <i class="bi bi-eye"></i>
  </button>
</div>
HTML;
    }

    private function buildSelect(string $uid, string $name, array $options, string $current, string $req, string $ro): string
    {
        $opts = "<option value=\"\">-- 선택 --</option>";
        foreach ($options as $key => $label) {
            $sel   = $current === (string)$key ? 'selected' : '';
            $opts .= "<option value=\"{$key}\" {$sel}>" . htmlspecialchars((string)$label, ENT_QUOTES, 'UTF-8') . "</option>";
        }
        return "<select id=\"{$uid}\" name=\"{$name}\" class=\"form-select\" {$req} {$ro}>{$opts}</select>";
    }

    private function buildTomSelect(string $uid, string $name, array $options, string $current, string $req, bool $ro, bool $multiple): string
    {
        $multiAttr = $multiple ? 'multiple' : '';
        $opts = "<option value=\"\">-- 선택 --</option>";
        foreach ($options as $key => $label) {
            $sel   = $current === (string)$key ? 'selected' : '';
            $opts .= "<option value=\"{$key}\" {$sel}>" . htmlspecialchars((string)$label, ENT_QUOTES, 'UTF-8') . "</option>";
        }

        $tsOpts = $multiple
            ? '{ plugins: ["remove_button"], create: false }'
            : '{ create: false }';

        if (!$ro) {
            $this->initScripts[] = "if(typeof TomSelect!=='undefined') new TomSelect('#{$uid}', {$tsOpts});";
        }

        return "<select id=\"{$uid}\" name=\"{$name}[]\" class=\"form-select\" {$req} {$multiAttr}>{$opts}</select>";
    }

    private function buildMultiSelectNative(string $uid, string $name, array $options, string $current, bool $ro): string
    {
        $roAttr  = $ro ? 'disabled' : '';
        $opts    = '';
        $selected = explode(',', $current);
        foreach ($options as $key => $label) {
            $sel   = in_array((string)$key, $selected, true) ? 'selected' : '';
            $opts .= "<option value=\"{$key}\" {$sel}>" . htmlspecialchars((string)$label, ENT_QUOTES, 'UTF-8') . "</option>";
        }
        return "<select id=\"{$uid}\" name=\"{$name}[]\" class=\"form-select\" multiple {$roAttr}>{$opts}</select>";
    }

    private function buildRelationField(
        string $uid, string $name, array $field,
        string $value, string $req, bool $ro,
        string $base, bool $multiple,
    ): string {
        $relation = $field['relation'] ?? [];
        $ajaxUrl  = "{$base}?state=ajax_list&action=relation&field={$name}&q=";
        $multiAttr = $multiple ? 'multiple' : '';
        $roAttr    = $ro ? 'disabled' : '';

        $tsOpts = json_encode([
            'valueField'   => 'value',
            'labelField'   => 'label',
            'searchField'  => 'label',
            'plugins'      => $multiple ? ['remove_button'] : [],
            'load'         => null, // overridden below
        ], JSON_UNESCAPED_UNICODE);

        if (!$ro) {
            $this->initScripts[] = <<<JS
if(typeof TomSelect!=='undefined') {
  new TomSelect('#{$uid}', {
    valueField: 'value',
    labelField: 'label',
    searchField: 'label',
    plugins: ['remove_button'],
    load: function(query, callback) {
      fetch('{$ajaxUrl}' + encodeURIComponent(query), { headers: { Accept: 'application/json' } })
        .then(r => r.json()).then(callback).catch(() => callback());
    },
    preload: true,
  });
}
JS;
        }

        $initVal = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        return "<select id=\"{$uid}\" name=\"{$name}" . ($multiple ? '[]' : '') . "\" class=\"form-select\" {$req} {$multiAttr} {$roAttr}>
  " . ($initVal ? "<option value=\"{$initVal}\" selected>{$initVal}</option>" : '') . "
</select>";
    }

    private function buildDependentField(
        string $uid, string $name, array $field,
        string $value, string $req, bool $ro, string $base,
    ): string {
        $relation    = $field['relation'] ?? [];
        $parentField = $relation['parentField'] ?? '';
        $ajaxUrl     = "{$base}?state=ajax_list&action=relation&field={$name}&parent_value=";
        $roAttr      = $ro ? 'disabled' : '';

        if (!$ro && $parentField) {
            $this->initScripts[] = <<<JS
(function(){
  const child  = document.getElementById('{$uid}');
  const parent = document.querySelector('[name="{$parentField}"]');
  if (!parent || !child) return;
  parent.addEventListener('change', async function() {
    const res  = await fetch('{$ajaxUrl}' + encodeURIComponent(this.value), { headers: { Accept: 'application/json' } });
    const opts = await res.json();
    child.innerHTML = '<option value="">-- 선택 --</option>'
      + opts.map(o => '<option value="' + o.value + '">' + o.label + '</option>').join('');
  });
})();
JS;
        }

        $initVal = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        return "<select id=\"{$uid}\" name=\"{$name}\" class=\"form-select\" {$req} {$roAttr}>
  " . ($initVal ? "<option value=\"{$initVal}\" selected>{$initVal}</option>" : '<option value="">-- 선택 --</option>') . "
</select>";
    }

    private function buildUpload(string $uid, string $name, string $currentValue, bool $ro, bool $multiple): string
    {
        $roAttr    = $ro ? 'disabled' : '';
        $multiAttr = $multiple ? 'multiple' : '';
        $preview   = $currentValue
            ? "<div class=\"mt-1\"><small class=\"text-muted\">현재 파일: {$currentValue}</small></div>"
            : '';

        return <<<HTML
<input type="file" id="{$uid}" name="{$name}" class="form-control" {$roAttr} {$multiAttr}>
{$preview}
HTML;
    }

    private function buildWysiwyg(string $uid, string $name, string $value, bool $ro): string
    {
        $roAttr = $ro ? 'disabled' : '';
        $eValue = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

        if (!$ro) {
            $this->initScripts[] = <<<JS
if(typeof ClassicEditor!=='undefined') {
  ClassicEditor.create(document.getElementById('{$uid}')).catch(console.error);
}
JS;
        }

        return "<textarea id=\"{$uid}\" name=\"{$name}\" class=\"form-control\" rows=\"8\" {$roAttr}>{$eValue}</textarea>";
    }
}
