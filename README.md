# hoksi/ci4-crud

[![PHP](https://img.shields.io/badge/PHP-8.2+-blue.svg)](https://php.net)
[![CodeIgniter](https://img.shields.io/badge/CodeIgniter-4.7+-orange.svg)](https://codeigniter.com)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![PHPUnit](https://github.com/hoksi/ci4-crud/actions/workflows/php.yml/badge.svg)](https://github.com/hoksi/ci4-crud/actions/workflows/php.yml)

CodeIgniter 4 전용 CRUD 자동 생성 라이브러리입니다.  
PHP 3~5줄로 완전한 CRUD 인터페이스를 생성합니다.

---

## 특징

| 기능 | 설명 |
|------|------|
| **최소 코드** | PHP 3~5줄로 완전한 CRUD 생성 |
| **Mode A** | React 18 + Tabulator.js SPA 모드 (JSON API) |
| **Mode B** | PHP 하이브리드 모드 (Bootstrap 5 + Vanilla JS) |
| **CI4 네이티브** | CI4 QueryBuilder, Validation, Model 직접 활용 |
| **26종 필드 타입** | text, dropdown, date, relation, wysiwyg 등 |
| **관계 처리** | 1:N 드롭다운, N:N 다중선택, 종속 드롭다운 |
| **콜백 시스템** | Before/After Insert/Update/Delete 20종 |
| **파일 업로드** | 단일/다중 업로드, CI4 Files 클래스 연동 |
| **내보내기** | CSV / Excel (PhpSpreadsheet) |
| **MIT 라이선스** | 무료, 상업 사용, 재배포 허용 |

---

## 설치

### PHP 라이브러리 (Composer)

```bash
composer require hoksi/ci4-crud
```

### React 프론트엔드 (npm) — Mode A 전용

```bash
npm install @hoksi/ci4-react-crud
```

---

## 빠른 시작

### Mode A — React SPA

**1. CI4 컨트롤러**

```php
<?php
namespace App\Controllers;

use Hoksi\Ci4Crud\Ci4Crud;

class UserController extends BaseController
{
    public function index()
    {
        return (new Ci4Crud)
            ->setTable('users')
            ->setSubject('사용자')
            ->columns('name', 'email', 'status', 'created_at')
            ->displayAs('created_at', '등록일')
            ->fieldType('status', 'dropdown', ['active' => '활성', 'inactive' => '비활성'])
            ->setRelation('dept_id', 'departments', 'dept_name')
            ->setRule('email', 'required|valid_email|is_unique[users.email]')
            ->setRead()
            ->setClone()
            ->renderJson();
    }
}
```

**2. React 앱**

```tsx
import { CrudApp } from '@hoksi/ci4-react-crud';
import '@hoksi/ci4-react-crud/dist/style.css';

export default function UsersPage() {
    return (
        <CrudApp
            endpoint="/crud/users"
            theme="bootstrap5"
            locale="ko"
        />
    );
}
```

### Mode B — PHP 하이브리드

```php
<?php
namespace App\Controllers;

use Hoksi\Ci4Crud\Ci4Crud;

class UserController extends BaseController
{
    public function index()
    {
        $crud = new Ci4Crud;
        $crud->setTable('users')
             ->setSubject('사용자')
             ->columns('name', 'email', 'status')
             ->fieldType('status', 'dropdown', ['active' => '활성', 'inactive' => '비활성']);

        $output = $crud->render();

        return view('layouts/app', ['crud' => $output]);
    }
}
```

```php
<!-- app/Views/layouts/app.php -->
<!DOCTYPE html>
<html>
<head>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <?= $renderer->renderAssets('add') ?>
</head>
<body>
<div class="container py-4">
  <?= $crud ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
```

---

## CI4 라우트 설정

```php
// app/Config/Routes.php

// Mode A
$routes->match(['get', 'post'], 'crud/users', 'UserController::index');

// Mode B
$routes->match(['get', 'post'], 'admin/users', 'Admin\UserController::index');
```

---

## API 메서드

### 기본 설정

```php
->setTable(string $table)               // 대상 테이블
->setSubject(string $singular, string $plural = '')  // UI 제목
->setPrimaryKey(string $field)          // 기본키 (자동 감지 가능)
->setModel(object $model)              // CI4 Model 직접 주입
->setLanguage(string $lang)            // UI 언어
->where(string|array $field, $value)   // 전역 WHERE 조건
->setPerPage(int $n)                   // 페이지당 항목 수
->setSoftDelete(bool $flag)            // soft delete 연동
->setTimestamps(bool $flag)            // timestamps 자동 처리
```

### 데이터그리드

```php
->columns('name', 'email', 'status')       // 표시 컬럼
->displayAs('created_at', '등록일')         // 컬럼 라벨 변경
->defaultOrdering('created_at', 'desc')    // 기본 정렬
->setSearchable('name', 'email')           // 검색 대상 필드
->callbackColumn('status', fn($v) => ...)  // 커스텀 셀 렌더링
->unsetExport()                            // 내보내기 비활성화
```

### 필드 타입

```php
->fieldType('status', 'dropdown', ['active' => '활성'])
->fieldType('birth_date', 'date')
->fieldType('content', 'wysiwyg')
->fieldType('price', 'float')
->fieldType('password', 'password_toggle')
->setTexteditor('content')
->setFieldUpload('image', 'uploads/images/')
```

**지원 타입:** `string`, `textarea`, `numeric`, `float`, `boolean`, `date`, `datetime`,
`native_date`, `native_time`, `dropdown`, `dropdown_search`, `enum`,
`multiselect_native`, `multiselect_searchable`, `relation`, `relation_nton`,
`dependent`, `password`, `password_toggle`, `email`, `color`,
`upload_file`, `hidden`, `invisible`, `virtual`, `readonly`, `wysiwyg`

### 관계 설정

```php
// 1:N — customers.country_id → countries.name
->setRelation('country_id', 'countries', 'name')

// 동적 로딩 (대용량)
->setRelationDynamic('country_id', 'countries', 'name')

// N:N — films ↔ actors
->setRelationNtoN('actors', 'film_actor', 'actors', 'film_id', 'actor_id', 'full_name')

// 종속 드롭다운 — 국가 선택 → 도시 목록 변경
->setDependentRelation('city_id', 'country_id', 'cities', 'city_name', 'country_id')
```

### 콜백

```php
// 삽입 전 — 패스워드 해싱
->callbackBeforeInsert(function($data) {
    $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
    return $data;
})

// 삽입 후 — 환영 메일
->callbackAfterInsert(function($data, $insertId) {
    // 이메일 발송 등
})

// 삭제 전 — false 반환 시 취소
->callbackBeforeDelete(function($id) {
    return true; // false 반환 시 삭제 취소
})
```

### 권한 제어

```php
->setRead()            // 조회 기능 활성화 (기본 비활성)
->setClone()           // 복제 기능 활성화 (기본 비활성)
->unsetAdd()           // 추가 비활성화
->unsetEdit()          // 수정 비활성화
->unsetDelete()        // 삭제 비활성화
->unsetDeleteMultiple()// 다중삭제 비활성화
->unsetOperations()    // 모든 쓰기 작업 비활성화
```

### 유효성 검사

```php
// CI4 Validation 규칙 직접 사용
->setRule('email', 'required|valid_email|is_unique[users.email]')
->setRules([
    'name'  => 'required|min_length[2]',
    'phone' => 'required|regex_match[/^01[0-9]{8,9}$/]',
])
->setValidationGroup('UserRules')  // CI4 Validation 그룹 재사용
->requiredFields('name', 'email')
->uniqueFields('email')
```

---

## 커스텀 필드 타입 등록

```php
use Hoksi\Ci4Crud\FieldTypes\AbstractFieldType;
use Hoksi\Ci4Crud\FieldTypes\FieldTypeRegistry;

class StarRatingType extends AbstractFieldType
{
    public function getType(): string { return 'star_rating'; }

    public function toSchemaArray(): array
    {
        return ['type' => $this->getType(), 'max' => $this->options['max'] ?? 5];
    }
}

FieldTypeRegistry::register('star_rating', StarRatingType::class);

$crud->fieldType('rating', 'star_rating', ['max' => 5]);
```

---

## 요구사항

| 항목 | 버전 |
|------|------|
| PHP | 8.2+ |
| CodeIgniter | 4.7+ |
| Node.js (Mode A) | 18+ |
| React (Mode A) | 18+ |

---

## 라이선스

MIT License — 자세한 내용은 [LICENSE](LICENSE) 파일을 참조하세요.
