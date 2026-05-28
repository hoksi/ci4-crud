# CI4 CRUD 라이브러리 통합 구현 계획서

> GroceryCRUD Enterprise v3.x 분석 기반  
> Backend: CodeIgniter 4.7+ / Frontend: React 18 + Tabulator.js 6.x (Mode A) 또는 Vanilla JS + Bootstrap 5 (Mode B)  
> 작성일: 2026-05-27

---

## 목차

1. [프로젝트 개요](#1-프로젝트-개요)
2. [렌더링 모드 전략](#2-렌더링-모드-전략)
3. [전체 아키텍처](#3-전체-아키텍처)
4. [CI4 백엔드 설계](#4-ci4-백엔드-설계)
5. [React 프론트엔드 설계 (Mode A)](#5-react-프론트엔드-설계-mode-a)
6. [PHP 프론트엔드 설계 (Mode B)](#6-php-프론트엔드-설계-mode-b)
7. [API 통신 규격](#7-api-통신-규격)
8. [PHP API 메서드 목록](#8-php-api-메서드-목록)
9. [디렉토리 구조](#9-디렉토리-구조)
10. [지원 필드 타입](#10-지원-필드-타입)
11. [구현 단계 (Phase 로드맵)](#11-구현-단계-phase-로드맵)
12. [기술 스택](#12-기술-스택)
13. [리스크 및 고려사항](#13-리스크-및-고려사항)

---

## 1. 프로젝트 개요

### 목표

CI4를 백엔드로, React + Tabulator.js(Mode A) 또는 Vanilla JS + Bootstrap 5(Mode B)를 프론트엔드로 사용하는
**무료 오픈소스 CRUD 자동 생성 라이브러리**를 구현합니다.

### 핵심 요구사항

| 요구사항 | 설명 |
|----------|------|
| **최소 코드** | PHP 3~5줄로 완전한 CRUD 생성 |
| **CI4 네이티브** | CI4 QueryBuilder, Validation, Model, Session 직접 활용 |
| **Fluent API** | `$crud->setTable()->setSubject()->renderJson()` |
| **관계 처리** | 1:N 드롭다운, N:N 다중선택 자동 렌더링 |
| **콜백 지원** | Before/After Insert/Update/Delete 콜백 |
| **MIT 라이선스** | 무료, 상업 사용, 재배포 허용 |

### GroceryCRUD 대비 개선점

| 항목 | GroceryCRUD Enterprise | 이 프로젝트 |
|------|----------------------|-------------|
| 라이선스 | 유료 (€29~) | **MIT 무료** |
| 프론트엔드 | Bootstrap + jQuery | **React 18 + Tabulator.js** (Mode A) / **Vanilla JS** (Mode B) |
| Bootstrap | v3/v4 | **Bootstrap 5** |
| jQuery | 필수 | **없음** |
| CI4 연동 | 별도 어댑터 필요 | **CI4 전용 최적화** |
| TypeScript | 미지원 | **지원** (Mode A) |

---

## 2. 렌더링 모드 전략

### Mode A: React SPA (권장)

```
CI4 (JSON API 전용) ←── JSON ──→ React 18 + Tabulator.js
```

- CI4는 JSON만 반환, HTML 렌더링 없음
- React가 전체 UI 담당 (완전한 SPA)
- Tabulator.js 서버사이드 페이지네이션·정렬·필터 내장
- Vite 빌드 파이프라인 필요
- `renderJson()` 메서드로 모든 액션 처리

### Mode B: PHP 하이브리드

```
CI4 (HTML + AJAX JSON) + Vanilla JS Bootstrap 5 UI
```

- CI4가 HTML 초기 렌더링 + AJAX 데이터 처리
- Bootstrap 5 기반 데이터그리드
- 별도 빌드 파이프라인 불필요
- 기존 CI4 레이아웃·네비게이션 유지 가능
- `render()` 메서드로 HTML 반환

> **이 계획서의 기본 구현 방향은 Mode A (React SPA)**이며, Mode B는 하이브리드 옵션으로 병행 지원합니다.

---

## 3. 전체 아키텍처

### Mode A 아키텍처

```
┌─────────────────────────────────────────────────────────────────┐
│                      브라우저 (React SPA)                        │
│                                                                   │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │                   CrudApp (Root Component)                │   │
│  │                                                           │   │
│  │  ┌─────────────────┐      ┌──────────────────────────┐  │   │
│  │  │  DataGrid        │      │  CrudForm                │  │   │
│  │  │  (Tabulator.js) │      │  (Add / Edit / Read /    │  │   │
│  │  │  - 서버사이드    │      │   Clone)                  │  │   │
│  │  │    페이지네이션  │      │  - 동적 필드 렌더링       │  │   │
│  │  │  - 정렬/필터    │      │  - 필드 타입별 컴포넌트   │  │   │
│  │  │  - 인라인 편집  │      │  - 유효성 검사 표시       │  │   │
│  │  │  - 내보내기     │      └──────────────────────────┘  │   │
│  │  └─────────────────┘                                     │   │
│  └──────────────────────────────────────────────────────────┘   │
│                          │  Fetch API (JSON)                     │
└──────────────────────────┼──────────────────────────────────────┘
                           │
          ┌────────────────▼───────────────────────┐
          │          CI4 REST API                   │
          │                                         │
          │  /crud/{resource}?action=schema         │
          │  /crud/{resource}?action=list           │
          │  /crud/{resource}?action=read&id=1      │
          │  /crud/{resource}?action=insert         │
          │  /crud/{resource}?action=update&id=1    │
          │  /crud/{resource}?action=delete&id=1    │
          │  /crud/{resource}?action=relation       │
          │  /crud/{resource}?action=export         │
          │                                         │
          │  ┌─────────────────────────────────┐   │
          │  │  CrudApiController               │   │
          │  │  ↓                               │   │
          │  │  Ci4Crud (PHP 라이브러리)         │   │
          │  │  ↓                               │   │
          │  │  CI4 QueryBuilder + Model        │   │
          │  └─────────────────────────────────┘   │
          └────────────────────────────────────────┘
                           │
          ┌────────────────▼────────────────────────┐
          │            SQLite / MySQL / PostgreSQL   │
          └─────────────────────────────────────────┘
```

### Mode B 아키텍처

```
┌─────────────────────────────────────────────────────────┐
│                    CI4 Controller                        │
│  $crud = new Ci4Crud();                                  │
│  $crud->setTable('users')->setSubject('사용자');          │
│  return $crud->render();                                  │
└──────────────────────┬──────────────────────────────────┘
                       │
         ┌─────────────▼─────────────┐
         │       Ci4Crud (파사드)     │
         │  - Fluent API 진입점       │
         │  - 설정 수집·위임          │
         └──────┬────────────────────┘
                │
    ┌───────────┼──────────────┐
    ▼           ▼              ▼
┌────────┐ ┌────────┐ ┌──────────────┐
│ State  │ │Schema  │ │   Renderer   │
│Manager │ │Reader  │ │  (View 생성) │
└───┬────┘ └───┬────┘ └──────┬───────┘
    ▼          ▼              ▼
┌────────┐ ┌────────┐ ┌──────────────┐
│ CRUD   │ │ Query  │ │  FieldType   │
│Actions │ │Builder │ │  Renderer    │
│Handler │ │Wrapper │ └──────────────┘
└────────┘ └────────┘
```

---

## 4. CI4 백엔드 설계

### 4.1 Ci4Crud PHP 클래스 (Fluent API)

두 모드 공통으로 사용하는 진입점입니다.

```php
class UserCrudController extends BaseController
{
    public function __invoke()
    {
        $crud = new \App\Libraries\Ci4Crud\Ci4Crud();

        $crud->setTable('users')
             ->setSubject('사용자')
             ->columns('name', 'email', 'status', 'created_at')
             ->displayAs('created_at', '등록일')
             ->fieldType('status', 'dropdown', [
                 'active'   => '활성',
                 'inactive' => '비활성',
             ])
             ->setRelation('department_id', 'departments', 'dept_name')
             ->setRule('email', 'required|valid_email|is_unique[users.email]')
             ->callbackBeforeInsert(fn($data) => array_merge($data, [
                 'password' => password_hash($data['password'], PASSWORD_BCRYPT),
             ]))
             ->callbackColumn('status', fn($v, $row) => [
                 'badge'  => $v === 'active' ? 'success' : 'danger',
                 'label'  => $v,
             ])
             ->setRead()
             ->setClone()
             ->unsetExport();

        // Mode A: JSON 반환
        return $crud->renderJson();

        // Mode B: HTML 반환
        // $output = $crud->render();
        // return view('crud/layout', ['crud_output' => $output]);
    }
}
```

### 4.2 renderJson() — Mode A 동작 흐름

```
요청 수신 → action 파라미터 감지 → 핸들러 실행 → JSON 반환

GET  ?action=schema              → 스키마(컬럼 정의·필드타입·권한) JSON
GET  ?action=list                → 데이터 목록 + 페이지네이션 JSON
GET  ?action=read&id=1           → 단건 조회 JSON
POST ?action=insert              → 삽입 결과 JSON
POST ?action=update&id=1         → 수정 결과 JSON
POST ?action=delete&id=1         → 삭제 결과 JSON
POST ?action=delete_multiple     → 다중 삭제 결과 JSON
GET  ?action=relation&field=f&q= → 관계 검색 JSON
GET  ?action=export&type=csv     → CSV 파일 다운로드
```

### 4.3 render() — Mode B 상태 기반 처리

```
URL 파라미터로 상태 자동 감지 → 해당 HTML 반환

GET  ?state=list              → 데이터그리드 HTML
GET  ?state=ajax_list         → JSON 데이터
GET  ?state=add               → 추가 폼 HTML
POST ?state=insert            → DB 삽입 후 JSON
GET  ?state=edit&id=1         → 수정 폼 HTML
POST ?state=update&id=1       → DB 수정 후 JSON
POST ?state=delete&id=1       → DB 삭제 후 JSON
GET  ?state=read&id=1         → 조회 폼 HTML
GET  ?state=clone&id=1        → 복제 폼 HTML
GET  ?state=export&type=csv   → CSV/Excel 다운로드
```

### 4.4 스키마 응답 구조 (Mode A)

React가 초기에 `?action=schema`를 호출하여 전체 UI 구성 정보를 받습니다.

```json
{
  "subject": "사용자",
  "primaryKey": "id",
  "perPage": 20,
  "permissions": {
    "add": true, "edit": true, "delete": true,
    "read": true, "clone": true, "export": true
  },
  "columns": [
    { "field": "name",       "title": "이름",   "sortable": true, "searchable": true },
    { "field": "email",      "title": "이메일", "sortable": true, "searchable": true },
    { "field": "status",     "title": "상태",   "sortable": true, "callback": true },
    { "field": "created_at", "title": "등록일", "sortable": true }
  ],
  "formFields": {
    "add": [
      { "field": "name",    "title": "이름",   "type": "string",   "required": true },
      { "field": "email",   "title": "이메일", "type": "email",    "required": true },
      { "field": "status",  "title": "상태",   "type": "dropdown",
        "options": { "active": "활성", "inactive": "비활성" }
      },
      { "field": "dept_id", "title": "부서",   "type": "relation",
        "relation": { "table": "departments", "labelField": "dept_name", "searchable": true }
      }
    ],
    "edit":  [],
    "read":  [],
    "clone": []
  },
  "defaultOrder": { "field": "created_at", "dir": "desc" }
}
```

### 4.5 목록 JSON 응답 구조

```json
{
  "last_page": 10,
  "data": [
    { "id": 1, "name": "홍길동", "email": "hong@example.com", "status": "active" },
    { "id": 2, "name": "김철수", "email": "kim@example.com",  "status": "inactive" }
  ]
}
```

Tabulator가 전송하는 요청 파라미터:

```
GET /crud/users?action=list
  &page=1
  &size=20
  &sort[0][field]=name&sort[0][dir]=asc
  &filter[0][field]=status&filter[0][type]=eq&filter[0][value]=active
```

### 4.6 표준 JSON 응답 구조

```json
// 성공
{ "success": true,  "data": {}, "message": "처리 완료" }

// 실패
{ "success": false, "message": "유효성 검사 실패",
  "errors": { "email": "이미 사용 중인 이메일입니다." }
}
```

### 4.7 CI4 라우트 등록

```php
// app/Config/Routes.php

// Mode A — REST JSON API
$routes->group('crud', function($routes) {
    $routes->match(['get', 'post'], 'users',    'CrudApi\UserCrudController');
    $routes->match(['get', 'post'], 'products', 'CrudApi\ProductCrudController');
});

// Mode B — 상태 기반 HTML
$routes->get('admin/users',  'Admin\UserController::index');
$routes->post('admin/users', 'Admin\UserController::index');
```

---

## 5. React 프론트엔드 설계 (Mode A)

### 5.1 컴포넌트 트리

```
<CrudApp endpoint="/crud/users">
  │
  ├── <CrudHeader />              ← 제목, 추가 버튼, 검색
  │
  ├── <DataGrid />                ← Tabulator.js 래퍼
  │     ├── useTabulator()        ← Tabulator 인스턴스 관리 Hook
  │     ├── <ActionCell />        ← 행별 수정/삭제/조회 버튼
  │     └── <BulkActionBar />     ← 다중선택 후 일괄 처리
  │
  ├── <CrudModal>                 ← 폼 모달 (추가/수정/조회/복제)
  │     └── <CrudForm />
  │           ├── <FieldRenderer />   ← 필드 타입별 동적 렌더링
  │           │     ├── <StringField />
  │           │     ├── <DropdownField />
  │           │     ├── <RelationField />   ← AJAX 검색 드롭다운
  │           │     ├── <MultiSelectField />
  │           │     ├── <DateField />       ← flatpickr
  │           │     ├── <UploadField />
  │           │     ├── <WysiwygField />    ← CKEditor 5
  │           │     └── ... (24종)
  │           └── <ValidationErrors />
  │
  └── <ExportBar />               ← CSV / Excel / 인쇄 버튼
```

### 5.2 핵심 Custom Hooks

```typescript
const { schema, loading } = useCrudSchema(endpoint);
const { insert, update, remove, loading } = useCrudActions(endpoint);
const { tableRef, refresh } = useTabulator({ endpoint, schema });
const { formData, errors, handleSubmit } = useCrudForm({ mode, schema, onSuccess: refresh });
```

### 5.3 CrudApp 진입점

```tsx
import { CrudApp } from 'ci4-react-crud';

function App() {
    return (
        <CrudApp
            endpoint="/crud/users"
            theme="bootstrap5"
            locale="ko"
            onRowClick={(row) => console.log(row)}
        />
    );
}
```

### 5.4 모달 상태 머신

```
[목록 화면]
    ├── 추가 버튼 ──→ [추가 폼 모달] ──→ POST insert ──→ [목록 새로고침]
    ├── 수정 버튼 ──→ [수정 폼 모달] ──→ POST update ──→ [목록 새로고침]
    ├── 조회 버튼 ──→ [조회 폼 모달] (읽기전용)
    ├── 복제 버튼 ──→ [복제 폼 모달] ──→ POST insert ──→ [목록 새로고침]
    └── 삭제 버튼 ──→ [확인 다이얼로그] ──→ POST delete ──→ [목록 새로고침]
```

### 5.5 Tabulator.js 서버사이드 설정

```typescript
const tabulatorOptions = {
    ajaxURL: `${endpoint}?action=list`,
    ajaxParams: () => ({ csrf_token: getCsrfHash() }),
    pagination: true,
    paginationMode: 'remote',
    sortMode: 'remote',
    filterMode: 'remote',
    paginationSize: schema.perPage,
    layout: 'fitColumns',
    responsiveLayout: 'collapse',
    height: '600px',
    theme: 'bootstrap5',
    selectable: true,
    columns: buildColumns(schema),
};
```

### 5.6 CSRF 처리

```typescript
async function crudFetch(url: string, options: RequestInit = {}) {
    const headers = new Headers(options.headers);
    headers.set('X-CSRF-TOKEN', getCsrfHash());
    headers.set('Accept', 'application/json');
    return fetch(url, { ...options, headers });
}
```

---

## 6. PHP 프론트엔드 설계 (Mode B)

### 6.1 SchemaReader — DB 스키마 자동 감지

```php
class SchemaReader
{
    public function getColumns(string $table): array
    {
        // CI4 DB::getFieldData($table) 활용
    }

    public function inferFieldType(object $field): string
    {
        return match(true) {
            str_contains($field->type, 'int')      => 'numeric',
            str_contains($field->type, 'text')     => 'textarea',
            str_contains($field->type, 'datetime') => 'datetime',
            str_contains($field->type, 'date')     => 'date',
            str_contains($field->type, 'bool')     => 'boolean',
            $field->max_length > 500               => 'textarea',
            default                                 => 'string',
        };
    }
}
```

### 6.2 데이터그리드 화면

Bootstrap 5 기반 서버사이드 AJAX 데이터그리드로 렌더링합니다.

```
┌─────────────────────────────────────────────────────────┐
│  사용자 목록                          [+ 추가] [내보내기]  │
├────────┬────────────┬───────────┬──────────┬───────────┤
│  □  ID │ 이름       │ 이메일    │ 상태     │ 작업      │
├────────┼────────────┼───────────┼──────────┼───────────┤
│  □   1 │ 홍길동     │ hong@...  │ ● 활성   │ 수정 삭제 │
│  □   2 │ 김철수     │ kim@...   │ ○ 비활성 │ 수정 삭제 │
├────────┴────────────┴───────────┴──────────┴───────────┤
│  1-20 / 총 143건         [◀] [1] [2] [3] ... [8] [▶]  │
└─────────────────────────────────────────────────────────┘
```

### 6.3 CrudConfig — 설정 DTO

```php
class CrudConfig
{
    public string  $table          = '';
    public string  $subject        = '';
    public ?string $primaryKey     = null;
    public array   $columns        = [];
    public array   $addFields      = [];
    public array   $editFields     = [];
    public array   $readFields     = [];
    public array   $cloneFields    = [];
    public array   $labels         = [];
    public array   $fieldTypes     = [];
    public array   $relations      = [];
    public array   $relationsNtoN  = [];
    public array   $callbacks      = [];
    public array   $where          = [];
    public array   $rules          = [];
    public array   $requiredFields = [];
    public array   $readOnlyFields = [];
    public array   $uploadFields   = [];
    public bool    $canAdd         = true;
    public bool    $canEdit        = true;
    public bool    $canDelete      = true;
    public bool    $canRead        = false;
    public bool    $canClone       = false;
    public bool    $canExport      = true;
    public int     $perPage        = 20;
    public array   $defaultOrder   = [];
    public string  $language       = 'Korean';
}
```

---

## 7. API 통신 규격

### 전체 엔드포인트 목록

| Method | URL | 파라미터 | 설명 |
|--------|-----|---------|------|
| GET | `?action=schema` | — | 스키마 + 권한 정보 |
| GET | `?action=list` | page, size, sort, filter | 목록 (페이지네이션) |
| GET | `?action=read&id=1` | id | 단건 조회 |
| POST | `?action=insert` | FormData | 새 레코드 삽입 |
| POST | `?action=update&id=1` | FormData | 레코드 수정 |
| POST | `?action=delete&id=1` | — | 레코드 삭제 |
| POST | `?action=delete_multiple` | ids[] | 다중 삭제 |
| GET | `?action=relation&field=f&q=검색어` | field, q | 관계 검색 |
| GET | `?action=export&type=csv` | type | 내보내기 |

---

## 8. PHP API 메서드 목록

### 8.1 기본 설정

```php
->setTable(string $table)
->setSubject(string $singular, string $plural = '')
->setPrimaryKey(string $field)
->setModel(Model $model)            // CI4 Model 직접 주입
->setLanguage(string $lang)
->where(string|array $field, mixed $value = null)
->setPerPage(int $n)
->setSoftDelete(bool $flag)         // CI4 useSoftDeletes 연동
->setTimestamps(bool $flag)         // CI4 useTimestamps 연동
```

### 8.2 데이터그리드 제어

```php
->columns(string ...$fields)
->unsetColumns(string ...$fields)
->displayAs(string $field, string $label)
->defaultOrdering(string $field, string $dir = 'asc')
->defaultColumnWidth(string $field, int $px)
->setSearchable(string ...$fields)
->callbackColumn(string $field, callable $fn)
->setActionButton(string $label, string $url, string $icon = '')
->setActionButtonMultiple(string $label, string $url, string $icon = '')
->setDatagridButton(string $label, string $url, string $icon = '')
->unsetExport()
->unsetExportPdf()
->unsetExportExcel()
->unsetFilters()
->unsetPagination()
->unsetSearchColumns()
->unsetSortingColumns(string ...$fields)
->unsetList()
->inlineEditFields(string ...$fields)
->setCustomQuery(string $query)
```

### 8.3 폼 필드 제어

```php
->fields(string ...$fields)
->addFields(string ...$fields)
->editFields(string ...$fields)
->readFields(string ...$fields)
->cloneFields(string ...$fields)
->unsetFields(string ...$fields)
->unsetAddFields(string ...$fields)
->unsetEditFields(string ...$fields)
->readOnlyFields(string ...$fields)
->readOnlyAddFields(string ...$fields)
->readOnlyEditFields(string ...$fields)
->readOnlyCloneFields(string ...$fields)
->groupFields(string $title, string ...$fields)
->requiredFields(string ...$fields)
->requiredAddFields(string ...$fields)
->requiredEditFields(string ...$fields)
->requiredCloneFields(string ...$fields)
->uniqueFields(string ...$fields)
```

### 8.4 필드 타입

```php
->fieldType(string $field, string $type, array $options = [])
->fieldTypeAddForm(string $field, string $type, array $options = [])
->fieldTypeEditForm(string $field, string $type, array $options = [])
->fieldTypeReadForm(string $field, string $type, array $options = [])
->fieldTypeCloneForm(string $field, string $type, array $options = [])
->fieldTypeFormFields(string $field, string $type, array $options = [])
->fieldTypeColumn(string $field, string $type)
->setTexteditor(string $field)
->unsetTexteditor(string $field)
```

### 8.5 관계 설정

```php
->setRelation(string $fk, string $table, string $label)
->setRelationDynamic(string $fk, string $table, string $label)
->setRelation1to1(string $fk, string $table, array $fields)
->setRelationNtoN(string $field, string $junction, string $related, string $jFk, string $rFk, string $label)
->setDependentRelation(string $child, string $parent, string $table, string $label, string $fk)
```

### 8.6 콜백

```php
->callbackBeforeInsert(callable $fn)
->callbackAfterInsert(callable $fn)
->callbackInsert(callable $fn)
->callbackBeforeUpdate(callable $fn)
->callbackAfterUpdate(callable $fn)
->callbackUpdate(callable $fn)
->callbackBeforeDelete(callable $fn)
->callbackAfterDelete(callable $fn)
->callbackDelete(callable $fn)
->callbackBeforeDeleteMultiple(callable $fn)
->callbackAfterDeleteMultiple(callable $fn)
->callbackDeleteMultiple(callable $fn)
->callbackAddField(string $field, callable $fn)
->callbackEditField(string $field, callable $fn)
->callbackReadField(string $field, callable $fn)
->callbackCloneField(string $field, callable $fn)
->callbackColumn(string $field, callable $fn)
->callbackQuery(callable $fn)
->callbackBeforeUpload(callable $fn)
->callbackAfterUpload(callable $fn)
->callbackUpload(callable $fn)
```

### 8.7 유효성 검사

```php
->setRule(string $field, string $rule)
->setRules(array $rules)
->setValidationGroup(string $group)   // CI4 Validation 그룹 재사용
```

### 8.8 파일 업로드

```php
->setFieldUpload(string $field, string $path)
->setFieldUploadMultiple(string $field, string $path)
->setFieldBlob(string $field)
```

### 8.9 권한 제어

```php
->setAdd()    / ->unsetAdd()
->setEdit()   / ->unsetEdit()
->setDelete() / ->unsetDelete()
->setRead()   / ->unsetRead()
->setClone()  / ->unsetClone()
->setDeleteMultiple() / ->unsetDeleteMultiple()
->unsetOperations()
```

### 8.10 렌더링

```php
->renderJson()     // Mode A — JSON 반환
->render()         // Mode B — HTML 반환
->renderSchema()   // 스키마 JSON만 반환
```

---

## 9. 디렉토리 구조

### CI4 백엔드 (공통)

```
app/Libraries/Ci4Crud/
├── Ci4Crud.php                    ← 진입점 (Fluent API)
├── Config/
│   └── CrudConfig.php             ← 설정 DTO
├── Core/
│   ├── ActionManager.php          ← ?action=* 파라미터 라우팅 (Mode A)
│   ├── StateManager.php           ← ?state=* URL 감지 (Mode B)
│   ├── SchemaReader.php           ← DB 스키마 자동 파악
│   ├── QueryHandler.php           ← 목록 쿼리 (정렬/필터/페이지)
│   ├── InsertHandler.php          ← 삽입 처리
│   ├── UpdateHandler.php          ← 수정 처리
│   ├── DeleteHandler.php          ← 삭제 처리
│   ├── ReadHandler.php            ← 단건 조회
│   └── SchemaSerializer.php       ← PHP 설정 → JSON 스키마 변환
├── FieldTypes/
│   ├── FieldTypeInterface.php
│   ├── StringType.php
│   ├── TextareaType.php
│   ├── DropdownType.php
│   ├── MultiSelectType.php
│   ├── RelationType.php
│   ├── RelationNtoNType.php
│   ├── DateType.php
│   ├── DateTimeType.php
│   ├── BooleanType.php
│   ├── PasswordType.php
│   ├── UploadType.php
│   ├── HiddenType.php
│   ├── InvisibleType.php
│   ├── VirtualType.php
│   └── CustomType.php
├── Relations/
│   ├── OneToManyRelation.php
│   ├── ManyToManyRelation.php
│   └── DependentRelation.php
└── Export/
    ├── CsvExporter.php
    └── ExcelExporter.php          ← PhpSpreadsheet
```

### Mode B 전용 — PHP 뷰

```
app/Libraries/Ci4Crud/
├── Renderer/
│   ├── DatagridRenderer.php
│   ├── FormRenderer.php
│   └── ExportRenderer.php
└── Views/
    ├── layouts/
    │   └── crud_layout.php
    ├── datagrid/
    │   └── index.php
    └── forms/
        ├── add.php
        ├── edit.php
        ├── read.php
        └── clone.php
```

### Mode A 전용 — React 프론트엔드

```
resources/
├── src/
│   ├── index.tsx
│   ├── components/
│   │   ├── CrudApp.tsx
│   │   ├── DataGrid/
│   │   │   ├── DataGrid.tsx
│   │   │   ├── useTabulator.ts
│   │   │   ├── ActionCell.tsx
│   │   │   └── BulkActionBar.tsx
│   │   ├── Form/
│   │   │   ├── CrudModal.tsx
│   │   │   ├── CrudForm.tsx
│   │   │   ├── FieldRenderer.tsx
│   │   │   └── fields/
│   │   │       ├── StringField.tsx
│   │   │       ├── TextareaField.tsx
│   │   │       ├── DropdownField.tsx
│   │   │       ├── RelationField.tsx
│   │   │       ├── MultiSelectField.tsx
│   │   │       ├── DateField.tsx
│   │   │       ├── UploadField.tsx
│   │   │       ├── WysiwygField.tsx
│   │   │       └── ... (24종)
│   │   └── shared/
│   │       ├── ConfirmDialog.tsx
│   │       ├── LoadingSpinner.tsx
│   │       └── ValidationErrors.tsx
│   ├── hooks/
│   │   ├── useCrudSchema.ts
│   │   ├── useCrudActions.ts
│   │   └── useCrudForm.ts
│   ├── api/
│   │   ├── crudApi.ts
│   │   └── types.ts
│   └── styles/
│       └── crud.scss
├── package.json
└── vite.config.ts
```

---

## 10. 지원 필드 타입

| 타입 | 렌더링 | PHP 정의 | React 컴포넌트 (Mode A) |
|------|--------|----------|------------------------|
| `string` | `<input type="text">` | 자동 | `StringField` |
| `textarea` | `<textarea>` | 자동 | `TextareaField` |
| `numeric` | `<input type="number">` | — | `NumericField` |
| `float` | `<input step=".01">` | — | `FloatField` |
| `boolean` | 토글 스위치 | — | `BooleanField` |
| `date` | flatpickr | — | `DateField` |
| `datetime` | flatpickr | — | `DateTimeField` |
| `native_date` | `<input type="date">` | — | `NativeDateField` |
| `native_time` | `<input type="time">` | — | `NativeTimeField` |
| `dropdown` | `<select>` | options 배열 | `DropdownField` |
| `dropdown_search` | Tom Select | options 배열 | `SearchDropdownField` |
| `multiselect_native` | `<select multiple>` | — | `MultiSelectField` |
| `multiselect_searchable` | Tom Select multi | — | `SearchMultiField` |
| `relation` | AJAX 드롭다운 | `setRelation()` | `RelationField` |
| `relation_nton` | AJAX 다중선택 | `setRelationNtoN()` | `RelationNtoNField` |
| `dependent` | 종속 드롭다운 | `setDependentRelation()` | `DependentField` |
| `enum` | `<select>` | — | `EnumField` |
| `password` | `<input type="password">` | — | `PasswordField` |
| `password_toggle` | 표시/숨김 토글 | — | `PasswordToggleField` |
| `email` | `<input type="email">` | — | `EmailField` |
| `color` | 색상 선택기 | — | `ColorField` |
| `upload_file` | 파일 업로드 | `setFieldUpload()` | `UploadField` |
| `hidden` | `<input type="hidden">` | — | `HiddenField` |
| `invisible` | 렌더링 안 함 | — | `null` |
| `virtual` | DB 저장 안 됨 | — | `VirtualField` |
| `readonly` | 텍스트 표시만 | — | `ReadOnlyField` |
| `wysiwyg` | CKEditor 5 | `setTexteditor()` | `WysiwygField` |

---

## 11. 구현 단계 (Phase 로드맵)

> Phase 1~3은 두 모드 공통 CI4 백엔드. Phase 4~5에서 모드 분기.

### Phase 1 — CI4 코어 API (4주)

**목표**: 기본 CRUD JSON/HTML API 동작

| 작업 | 산출물 |
|------|--------|
| `Ci4Crud.php` Fluent API 뼈대 | 메서드 체이닝 구조 |
| `CrudConfig.php` | 설정 DTO |
| `SchemaReader.php` | DB 컬럼 자동 파악 |
| `ActionManager.php` + `StateManager.php` | Mode A/B 라우팅 |
| `QueryHandler.php` | 목록 + 서버사이드 페이지네이션/정렬/필터 |
| `InsertHandler.php` / `UpdateHandler.php` / `DeleteHandler.php` / `ReadHandler.php` | CRUD 처리 |
| `SchemaSerializer.php` | PHP 설정 → JSON 스키마 변환 |

**검증 기준**: `curl /crud/users?action=list` JSON 반환 확인

---

### Phase 2 — 필드 타입 & 콜백 (3주)

**목표**: 다양한 입력 위젯 + 데이터 가공 콜백

| 작업 | 산출물 |
|------|--------|
| 필드 타입 인터페이스 + 20종 구현 | `FieldTypes/` 디렉토리 |
| 콜백 시스템 20종 | `callbackBeforeInsert` 등 |
| CI4 Validation 통합 | `setRule/setRules/setValidationGroup` |
| `displayAs` + `groupFields` | UI 레이블·그룹화 |
| `readOnlyFields` | 읽기전용 렌더링 |
| `callbackColumn` | 커스텀 셀 렌더링 |

---

### Phase 3 — 관계형 데이터 (3주)

**목표**: 1:N, N:N 관계 완전 동작

| 작업 | 산출물 |
|------|--------|
| `OneToManyRelation.php` | 관계 데이터 조회 API |
| `ManyToManyRelation.php` | Junction 테이블 동기화 |
| `DependentRelation.php` | 종속 드롭다운 |
| `?action=relation` 엔드포인트 | 검색어 기반 관계 데이터 반환 |
| 동적 로딩 (`setRelationDynamic`) | AJAX 옵션 로딩 |

---

### Phase 4A — React + Tabulator 데이터그리드 (Mode A, 3주)

**목표**: React 데이터그리드 완전 동작

| 작업 | 산출물 |
|------|--------|
| Vite + React + TypeScript 프로젝트 구성 | `vite.config.ts`, `package.json` |
| `useCrudSchema` Hook | 스키마 로딩·캐싱 |
| `DataGrid.tsx` + `useTabulator.ts` | Tabulator.js 래퍼 |
| 스키마 → Tabulator columns 변환 | `buildColumns()` |
| 서버사이드 AJAX 연결 | Tabulator ↔ CI4 API |
| `ActionCell.tsx` + `BulkActionBar.tsx` | 행 액션·일괄처리 |

### Phase 4B — PHP 데이터그리드 (Mode B, 2주)

**목표**: Bootstrap 5 AJAX 데이터그리드 완전 동작

| 작업 | 산출물 |
|------|--------|
| `DatagridRenderer.php` | 목록 HTML 생성 |
| `datagrid/index.php` 뷰 | Bootstrap 5 테이블 |
| Vanilla JS AJAX | 정렬·필터·페이지네이션 |

---

### Phase 5A — React 폼 시스템 (Mode A, 3주)

**목표**: 추가/수정/조회/복제 폼 완전 동작

| 작업 | 산출물 |
|------|--------|
| `CrudModal.tsx` | 모달 컨테이너 + 상태 머신 |
| `CrudForm.tsx` + `FieldRenderer.tsx` | 동적 폼 렌더러 |
| 필드 컴포넌트 24종 | `fields/` 디렉토리 |
| `useCrudActions.ts` + `useCrudForm.ts` | Fetch API + 폼 상태 |
| `ValidationErrors.tsx` | CI4 오류 → UI 표시 |

### Phase 5B — PHP 폼 시스템 (Mode B, 2주)

**목표**: 추가/수정/조회/복제 폼 완전 동작

| 작업 | 산출물 |
|------|--------|
| `FormRenderer.php` | 동적 폼 생성 |
| `forms/add.php` 등 뷰 4종 | Bootstrap 5 폼 |
| Vanilla JS 폼 제출 | Fetch API AJAX |

---

### Phase 6 — 파일 업로드 & 내보내기 (2주)

| 작업 | 산출물 |
|------|--------|
| CI4 파일 업로드 통합 | `UploadHandler.php` (CI4 Files 클래스) |
| `UploadField.tsx` / 업로드 뷰 | 파일 업로드 UI |
| CSV 내보내기 | `CsvExporter.php` |
| Excel 내보내기 | `ExcelExporter.php` (PhpSpreadsheet) |
| `WysiwygField.tsx` / CKEditor 5 통합 | WYSIWYG 에디터 |
| flatpickr 날짜 필드 | DateField / DateTimeField |

---

### Phase 7 — 패키지화 & 배포 (2주)

| 작업 | 산출물 |
|------|--------|
| CI4 라이브러리 `composer.json` | `your-org/ci4-crud` |
| React 라이브러리 `package.json` | `@your-org/ci4-react-crud` |
| PHPUnit 테스트 | 핵심 API 커버리지 80%+ |
| Vitest + Testing Library | React 컴포넌트 테스트 |
| README + API 문서 | 한국어/영어 |
| GitHub Actions CI/CD | 자동 테스트 + 릴리즈 |

---

## 12. 기술 스택

### 백엔드 (공통)

| 구분 | 선택 | 이유 |
|------|------|------|
| Framework | CodeIgniter 4.7+ | 타겟 프레임워크 |
| PHP | 8.2+ | named args, enum, readonly |
| Excel | PhpSpreadsheet | PHP 순수 구현, 의존성 낮음 |
| 테스트 | PHPUnit 10+ | CI4 기본 |

### Mode A 프론트엔드

| 구분 | 선택 | 이유 |
|------|------|------|
| UI 프레임워크 | React 18 | 컴포넌트 재사용, 생태계 |
| 언어 | TypeScript 5 | 타입 안전성 |
| 데이터그리드 | Tabulator.js 6.x | MIT, jQuery 불필요, 내보내기 내장 |
| 빌드 도구 | Vite 5 | 빠른 HMR, ES Modules |
| CSS | Bootstrap 5.3 | Tabulator Bootstrap 테마 일치 |
| 날짜 피커 | flatpickr | 경량, MIT |
| 검색 셀렉트 | Tom Select | Select2 대체, jQuery 없음 |
| WYSIWYG | CKEditor 5 | 풍부한 기능 (GPL 주의) |
| 상태관리 | React 내장 Hooks | 외부 라이브러리 최소화 |
| HTTP | Fetch API (native) | 외부 라이브러리 없음 |
| 테스트 | Vitest + Testing Library | Vite 연동 |

### Mode B 프론트엔드

| 구분 | 선택 | 이유 |
|------|------|------|
| CSS | Bootstrap 5.3 | 최신, CI4 Playground와 동일 |
| Icons | Bootstrap Icons | Bootstrap과 통일 |
| JS | Vanilla JS (ES2022+) | jQuery 의존성 제거 |
| 날짜 피커 | flatpickr | 경량, MIT |
| 검색 셀렉트 | Tom Select | Select2 jQuery 대체 |
| WYSIWYG | CKEditor 5 (CDN) | 풍부한 기능 |

---

## 13. 리스크 및 고려사항

### 기술 리스크

| 리스크 | 영향도 | 대응 방안 |
|--------|--------|-----------|
| React 빌드 파이프라인 CI4 통합 | 중 | Vite 빌드 결과물을 CI4 `public/` 에 배포 |
| CSRF 토큰 SPA 처리 | 고 | 초기 로딩 시 토큰 발급 엔드포인트 별도 구성 |
| N:N 관계 원자성 | 중 | CI4 트랜잭션 래핑 |
| 파일 업로드 + React FormData | 중 | multipart/form-data + Fetch API |
| CKEditor 5 GPL 라이선스 | 중 | 상업 프로젝트는 Quill(BSD) 또는 TipTap(MIT) 대체 |
| 테이블 스키마 DB별 차이 | 중 | CI4 `DB::getFieldData()` — MySQL/SQLite3/PostgreSQL 공통 |
| AJAX State URL 충돌 (Mode B) | 중 | `setUniqueId()`로 인스턴스별 네임스페이스 분리 |
| Tabulator.js 버전 업그레이드 | 중 | 버전 고정 (`^6.3`) |

### 우선순위 기준

| Phase | 완료 시 커버리지 |
|-------|----------------|
| Phase 1~2 | 기본 CRUD 목록 조회·삭제 동작 |
| Phase 1~3 | 추가·수정 폼까지 완전 동작 (실무 60%) |
| Phase 1~4 | 데이터그리드 완전 동작 (실무 70%) |
| Phase 1~5 | 관계형 데이터 + 폼 포함 (실무 85%) |
| Phase 1~6 | 파일 업로드 + 내보내기 포함 (실무 95%) |
| Phase 1~7 | GroceryCRUD Enterprise 동급 + 패키지 배포 |

### 라이선스 정책

- 라이브러리 자체: **MIT License**
- flatpickr: MIT / Tom Select: Apache-2.0 / CKEditor 5: GPL (상업 프로젝트 주의)
- Tabulator.js: MIT / React: MIT / Vite: MIT / PhpSpreadsheet: MIT

---

## 부록: 최소 구현 예시

### CI4 컨트롤러 (공통)

```php
class UserCrudController extends BaseController
{
    public function __invoke()
    {
        return (new Ci4Crud)
            ->setTable('users')
            ->setSubject('사용자')
            ->columns('name', 'email', 'status')
            ->setRelation('dept_id', 'departments', 'dept_name')
            ->setRead()
            ->setClone()
            ->renderJson();  // Mode A
    }
}
```

### React 앱 (Mode A — 10줄)

```tsx
import { CrudApp } from '@your-org/ci4-react-crud';
import '@your-org/ci4-react-crud/dist/style.css';

export default function UsersPage() {
    return (
        <CrudApp
            endpoint="/crud/users"
            theme="bootstrap5"
            locale="ko"
            height="70vh"
        />
    );
}
```

### CI4 뷰 (Mode B — 하이브리드)

```php
// 컨트롤러
$output = (new Ci4Crud)
    ->setTable('users')->setSubject('사용자')
    ->columns('name', 'email', 'status')
    ->render();

return view('crud/layout', ['crud_output' => $output]);
```
