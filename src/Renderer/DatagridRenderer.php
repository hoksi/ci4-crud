<?php

namespace Hoksi\Ci4Crud\Renderer;

use Hoksi\Ci4Crud\Config\CrudConfig;
use Hoksi\Ci4Crud\Core\SchemaReader;
use Hoksi\Ci4Crud\Core\SchemaSerializer;

class DatagridRenderer
{
    private array  $schema;
    private string $uid;

    public function __construct(private readonly CrudConfig $config)
    {
        $this->schema = (new SchemaSerializer($config, new SchemaReader()))->toArray();
        $this->uid    = 'ci4crud_' . substr(md5($config->table . $config->subject), 0, 8);
    }

    public function render(): string
    {
        return $this->renderContainer()
             . $this->renderScript();
    }

    private function baseUrl(): string
    {
        $uri    = $_SERVER['REQUEST_URI'] ?? '';
        $path   = strtok($uri, '?');
        return htmlspecialchars($path, ENT_QUOTES, 'UTF-8');
    }

    private function renderContainer(): string
    {
        $subject     = htmlspecialchars($this->schema['subject'], ENT_QUOTES, 'UTF-8');
        $canAdd      = $this->schema['permissions']['add'] ? '' : 'style="display:none"';
        $canExport   = $this->schema['permissions']['export'] ? '' : 'style="display:none"';
        $canDelMulti = $this->schema['permissions']['deleteMultiple'] ? '' : 'style="display:none"';
        $base        = $this->baseUrl();

        $headers = $this->buildHeaders();

        return <<<HTML
<div id="{$this->uid}_wrapper" class="ci4crud-wrapper">
  <!-- 툴바 -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">{$subject} 목록</h5>
    <div class="d-flex gap-2">
      <button id="{$this->uid}_bulk_delete" class="btn btn-sm btn-danger" {$canDelMulti} style="display:none!important">
        <i class="bi bi-trash"></i> 선택 삭제
      </button>
      <button id="{$this->uid}_export_btn" class="btn btn-sm btn-secondary" {$canExport}
        onclick="location.href='{$base}?state=export&type=csv'">
        <i class="bi bi-download"></i> CSV
      </button>
      <a href="{$base}?state=add" class="btn btn-sm btn-primary" {$canAdd}>
        <i class="bi bi-plus-lg"></i> {$subject} 추가
      </a>
    </div>
  </div>

  <!-- 검색 -->
  <div class="mb-2">
    <input id="{$this->uid}_search" type="text" class="form-control form-control-sm w-auto d-inline-block"
      placeholder="검색..." style="min-width:200px">
    <button class="btn btn-sm btn-outline-secondary" onclick="window['{$this->uid}'].search()">
      <i class="bi bi-search"></i>
    </button>
    <button class="btn btn-sm btn-outline-secondary ms-1" onclick="window['{$this->uid}'].reset()">
      <i class="bi bi-x-circle"></i> 초기화
    </button>
  </div>

  <!-- 테이블 -->
  <div class="table-responsive">
    <table class="table table-bordered table-hover table-sm align-middle">
      <thead class="table-light">
        <tr>
          <th style="width:40px">
            <input type="checkbox" id="{$this->uid}_check_all" class="form-check-input">
          </th>
          {$headers}
          <th style="width:130px">작업</th>
        </tr>
      </thead>
      <tbody id="{$this->uid}_tbody">
        <tr><td colspan="99" class="text-center text-muted py-4">데이터를 불러오는 중...</td></tr>
      </tbody>
    </table>
  </div>

  <!-- 페이지네이션 -->
  <div class="d-flex justify-content-between align-items-center mt-2">
    <small class="text-muted" id="{$this->uid}_info"></small>
    <nav><ul class="pagination pagination-sm mb-0" id="{$this->uid}_pager"></ul></nav>
  </div>
</div>
HTML;
    }

    private function buildHeaders(): string
    {
        $html = '';
        foreach ($this->schema['columns'] as $col) {
            $title = htmlspecialchars($col['title'], ENT_QUOTES, 'UTF-8');
            $field = htmlspecialchars($col['field'], ENT_QUOTES, 'UTF-8');
            if ($col['sortable']) {
                $html .= <<<HTML
<th class="ci4crud-sortable" data-field="{$field}" style="cursor:pointer">
  {$title} <i class="bi bi-arrow-down-up text-muted small"></i>
</th>
HTML;
            } else {
                $html .= "<th>{$title}</th>";
            }
        }
        return $html;
    }

    private function renderScript(): string
    {
        $schemaJson = json_encode($this->schema, JSON_UNESCAPED_UNICODE);
        $base       = $this->baseUrl();
        $uid        = $this->uid;

        return <<<HTML
<script>
(function() {
  const uid    = '{$uid}';
  const base   = '{$base}';
  const schema = {$schemaJson};
  let state    = { page: 1, size: schema.perPage, sort: [], search: '', order: {} };

  const el = id => document.getElementById(uid + '_' + id);

  async function load() {
    const params = new URLSearchParams({
      state: 'ajax_list',
      page:  state.page,
      size:  state.size,
    });
    if (state.search) params.set('filter[0][field]', '__search__');
    if (state.search) params.set('filter[0][value]', state.search);
    if (state.sort.length) {
      params.set('sort[0][field]', state.sort[0]);
      params.set('sort[0][dir]',   state.sort[1] ?? 'asc');
    }

    const res  = await fetch(base + '?' + params.toString(), {
      headers: { 'Accept': 'application/json' }
    });
    const data = await res.json();
    renderRows(data.data ?? []);
    renderPager(data.last_page ?? 1);
    el('info').textContent = '총 ' + (data.data?.length ?? 0) + '건 표시 중';
  }

  function renderRows(rows) {
    const pk      = schema.primaryKey;
    const cols    = schema.columns;
    const perms   = schema.permissions;
    const tbody   = el('tbody');

    if (!rows.length) {
      tbody.innerHTML = '<tr><td colspan="99" class="text-center text-muted py-4">데이터가 없습니다.</td></tr>';
      return;
    }

    tbody.innerHTML = rows.map(row => {
      const id      = row[pk];
      const cells   = cols.map(col => '<td>' + (row[col.field] ?? '') + '</td>').join('');
      const editBtn = perms.edit
        ? '<a href="' + base + '?state=edit&id=' + id + '" class="btn btn-xs btn-warning btn-sm py-0">수정</a> '
        : '';
      const readBtn = perms.read
        ? '<a href="' + base + '?state=read&id=' + id + '" class="btn btn-xs btn-info btn-sm py-0">조회</a> '
        : '';
      const delBtn  = perms.delete
        ? '<button class="btn btn-xs btn-danger btn-sm py-0" onclick="window[\'' + uid + '\'].del(' + id + ')">삭제</button>'
        : '';

      return '<tr>'
        + '<td><input type="checkbox" class="form-check-input ci4crud-row-check" value="' + id + '"></td>'
        + cells
        + '<td>' + editBtn + readBtn + delBtn + '</td>'
        + '</tr>';
    }).join('');
  }

  function renderPager(lastPage) {
    const pager = el('pager');
    const pages = [];
    const cur   = state.page;
    const start = Math.max(1, cur - 2);
    const end   = Math.min(lastPage, cur + 2);

    pages.push(makePage('&laquo;', 1,        cur === 1));
    pages.push(makePage('&lsaquo;', cur - 1, cur === 1));
    for (let p = start; p <= end; p++) pages.push(makePage(p, p, false, p === cur));
    pages.push(makePage('&rsaquo;', cur + 1, cur === lastPage));
    pages.push(makePage('&raquo;', lastPage,  cur === lastPage));

    pager.innerHTML = pages.join('');
  }

  function makePage(label, page, disabled, active = false) {
    const cls = 'page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '');
    return '<li class="' + cls + '">'
      + '<a class="page-link" href="#" onclick="event.preventDefault();window[\'' + uid + '\'].go(' + page + ')">' + label + '</a>'
      + '</li>';
  }

  // 정렬
  document.querySelectorAll('#' + uid + '_wrapper .ci4crud-sortable').forEach(th => {
    th.addEventListener('click', () => {
      const field = th.dataset.field;
      const dir   = (state.sort[0] === field && state.sort[1] === 'asc') ? 'desc' : 'asc';
      state.sort  = [field, dir];
      state.page  = 1;
      load();
    });
  });

  // 전체 선택
  el('check_all').addEventListener('change', e => {
    document.querySelectorAll('.' + uid + '_wrapper .ci4crud-row-check').forEach(cb => {
      cb.checked = e.target.checked;
    });
  });

  // 공개 API
  window[uid] = {
    go:     p  => { state.page = p; load(); },
    search: () => { state.search = el('search').value; state.page = 1; load(); },
    reset:  () => { state.search = ''; el('search').value = ''; state.page = 1; load(); },
    del: async id => {
      if (!confirm('정말 삭제하시겠습니까?')) return;
      await fetch(base + '?state=delete&id=' + id, { method: 'POST' });
      load();
    },
  };

  // 초기 로드
  load();
})();
</script>
HTML;
    }
}
