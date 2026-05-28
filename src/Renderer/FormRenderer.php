<?php

namespace Hoksi\Ci4Crud\Renderer;

use Hoksi\Ci4Crud\Config\CrudConfig;
use Hoksi\Ci4Crud\Core\ReadHandler;
use Hoksi\Ci4Crud\Core\SchemaReader;
use Hoksi\Ci4Crud\Core\SchemaSerializer;

class FormRenderer
{
    private array  $schema;

    public function __construct(private readonly CrudConfig $config)
    {
        $this->schema = (new SchemaSerializer($config, new SchemaReader()))->toArray();
    }

    public function renderAdd(): string
    {
        return $this->renderForm('add', [], '추가');
    }

    public function renderEdit(int|string $id): string
    {
        $result = (new ReadHandler($this->config))->handle($id);
        $data   = $result['data'] ?? [];
        return $this->renderForm('edit', $data, '수정', $id);
    }

    public function renderRead(int|string $id): string
    {
        $result = (new ReadHandler($this->config))->handle($id);
        $data   = $result['data'] ?? [];
        return $this->renderForm('read', $data, '조회', $id, readonly: true);
    }

    public function renderClone(int|string $id): string
    {
        $result = (new ReadHandler($this->config))->handle($id);
        $data   = $result['data'] ?? [];
        return $this->renderForm('clone', $data, '복제');
    }

    private function renderForm(
        string     $mode,
        array      $data,
        string     $label,
        int|string $id = 0,
        bool       $readonly = false,
    ): string {
        $subject = htmlspecialchars($this->schema['subject'], ENT_QUOTES, 'UTF-8');
        $base    = strtok($_SERVER['REQUEST_URI'] ?? '', '?');
        $action  = $mode === 'edit' ? "{$base}?state=update&id={$id}"
                 : ($mode === 'read' ? '#'
                 : "{$base}?state=insert");
        $fields  = $this->schema['formFields'][$mode] ?? [];
        $inputs  = $this->buildInputs($fields, $data, $readonly);

        $btnHtml = $readonly
            ? '<a href="' . htmlspecialchars($base, ENT_QUOTES) . '" class="btn btn-secondary">목록으로</a>'
            : '<button type="submit" class="btn btn-primary">' . $label . '</button>
               <a href="' . htmlspecialchars($base, ENT_QUOTES) . '" class="btn btn-secondary ms-2">취소</a>';

        return <<<HTML
<div class="ci4crud-form-wrapper">
  <h5 class="mb-3">{$subject} {$label}</h5>
  <div id="ci4crud_validation_errors" class="alert alert-danger d-none"></div>
  <form id="ci4crud_form" method="post" action="{$action}">
    {$inputs}
    <div class="mt-3">
      {$btnHtml}
    </div>
  </form>
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
    if (json.success) {
      location.href = '{$base}';
    } else {
      const errEl = document.getElementById('ci4crud_validation_errors');
      errEl.classList.remove('d-none');
      errEl.innerHTML = Object.values(json.errors ?? { msg: json.message }).join('<br>');
    }
  });
  </script>
</div>
HTML;
    }

    private function buildInputs(array $fields, array $data, bool $readonly): string
    {
        $html = '';
        foreach ($fields as $field) {
            $name    = htmlspecialchars($field['field'], ENT_QUOTES, 'UTF-8');
            $title   = htmlspecialchars($field['title'], ENT_QUOTES, 'UTF-8');
            $value   = htmlspecialchars((string)($data[$field['field']] ?? ''), ENT_QUOTES, 'UTF-8');
            $type    = $field['type'];
            $req     = ($field['required'] ?? false) ? 'required' : '';
            $ro      = ($readonly || ($field['readonly'] ?? false)) ? 'readonly disabled' : '';

            $input = match($type) {
                'textarea'   => "<textarea name=\"{$name}\" class=\"form-control\" rows=\"4\" {$req} {$ro}>{$value}</textarea>",
                'boolean'    => "<input type=\"checkbox\" name=\"{$name}\" class=\"form-check-input\" value=\"1\""
                                . ($value ? ' checked' : '') . " {$ro}>",
                'date'       => "<input type=\"date\" name=\"{$name}\" class=\"form-control\" value=\"{$value}\" {$req} {$ro}>",
                'datetime'   => "<input type=\"datetime-local\" name=\"{$name}\" class=\"form-control\" value=\"{$value}\" {$req} {$ro}>",
                'numeric'    => "<input type=\"number\" name=\"{$name}\" class=\"form-control\" value=\"{$value}\" {$req} {$ro}>",
                'float'      => "<input type=\"number\" step=\"0.01\" name=\"{$name}\" class=\"form-control\" value=\"{$value}\" {$req} {$ro}>",
                'password',
                'password_toggle' => "<input type=\"password\" name=\"{$name}\" class=\"form-control\" {$req} {$ro}>",
                'email'      => "<input type=\"email\" name=\"{$name}\" class=\"form-control\" value=\"{$value}\" {$req} {$ro}>",
                'color'      => "<input type=\"color\" name=\"{$name}\" class=\"form-control form-control-color\" value=\"{$value}\" {$ro}>",
                'hidden'     => "<input type=\"hidden\" name=\"{$name}\" value=\"{$value}\">",
                'invisible'  => '',
                'dropdown',
                'enum'       => $this->buildSelect($name, $field['options'] ?? [], $value, $req, $ro),
                default      => "<input type=\"text\" name=\"{$name}\" class=\"form-control\" value=\"{$value}\" {$req} {$ro}>",
            };

            if ($type === 'hidden' || $type === 'invisible') {
                $html .= $input;
                continue;
            }

            $html .= <<<HTML
<div class="mb-3">
  <label class="form-label">{$title}{$this->reqBadge($field['required'] ?? false)}</label>
  {$input}
</div>
HTML;
        }
        return $html;
    }

    private function buildSelect(string $name, array $options, string $current, string $req, string $ro): string
    {
        $opts = '';
        foreach ($options as $key => $label) {
            $sel   = $current === (string)$key ? 'selected' : '';
            $opts .= "<option value=\"{$key}\" {$sel}>{$label}</option>";
        }
        return "<select name=\"{$name}\" class=\"form-select\" {$req} {$ro}><option value=\"\">-- 선택 --</option>{$opts}</select>";
    }

    private function reqBadge(bool $required): string
    {
        return $required ? ' <span class="text-danger">*</span>' : '';
    }
}
