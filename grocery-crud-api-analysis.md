# GroceryCRUD Enterprise v3.x API 함수 분석 문서

> 출처: https://www.grocerycrud.com/docs/api-and-functions-list  
> 작성일: 2026-05-27  
> 대상 버전: GroceryCRUD Enterprise v3.x (CodeIgniter 4 / Laravel 호환)

---

## 목차

1. [개요](#1-개요)
2. [카테고리 구조](#2-카테고리-구조)
3. [기본 함수 (Basic)](#3-기본-함수-basic)
4. [데이터그리드 함수 (Datagrid)](#4-데이터그리드-함수-datagrid)
5. [CRUD 작업별 함수](#5-crud-작업별-함수)
   - 5.1 삽입 (Insert)
   - 5.2 수정 (Update)
   - 5.3 삭제 (Delete)
   - 5.4 조회 (Read)
   - 5.5 복제 (Clone)
6. [파일 업로드 함수 (Upload)](#6-파일-업로드-함수-upload)
7. [데이터베이스 함수 (Database)](#7-데이터베이스-함수-database)
8. [전체 작업 공통 함수](#8-전체-작업-공통-함수)
9. [필드 타입 함수 (Field Type)](#9-필드-타입-함수-field-type)
10. [고급 함수 (Advanced)](#10-고급-함수-advanced)
11. [함수 수 요약](#11-함수-수-요약)
12. [CI4 통합 시 주요 활용 패턴](#12-ci4-통합-시-주요-활용-패턴)

---

## 1. 개요

GroceryCRUD Enterprise는 PHP 프레임워크(CodeIgniter 4, Laravel 등)에서 **CRUD 인터페이스를 자동 생성**해주는 라이브러리입니다. 약 **150개 이상의 API 함수**가 11개 카테고리로 분류되어 있으며, 데이터그리드 표시부터 복잡한 관계형 데이터 처리까지 광범위한 기능을 제공합니다.

**핵심 특징:**
- 단 3줄 코드로 완성형 CRUD 생성 가능
- 30개 이상의 콜백 함수로 세밀한 동작 제어
- 1:1, 1:N, N:N 관계형 데이터 자동 처리
- Bootstrap 기반 반응형 UI 자동 생성
- Excel/PDF 내보내기, 인쇄, 필터, 정렬 내장
- CKEditor 텍스트 에디터 통합

---

## 2. 카테고리 구조

| 카테고리 | 함수 수 | 주요 역할 |
|----------|---------|-----------|
| Basic | 7 | 기본 설정 (테이블, 제목, 언어, 스킨) |
| Datagrid | ~25 | 목록 화면 컬럼·버튼·내보내기·필터 제어 |
| Insert | ~10 | 추가 폼 필드·콜백·필수값 설정 |
| Update | ~10 | 수정 폼 필드·콜백·필수값 설정 |
| Delete | ~10 | 삭제 전후 콜백·다중삭제 제어 |
| Read | ~10 | 조회 폼 필드·읽기전용 설정 |
| Clone | ~6 | 복제 기능·콜백 설정 |
| Upload | ~6 | 파일 업로드 필드·콜백 설정 |
| Database | ~10 | 관계 설정, 모델 교체, 기본키 설정 |
| All Operations | ~6 | 모든 폼 공통 필드·그룹화 |
| Field Type | ~10 | 필드 입력 타입 변경 (select, date, file 등) |
| Advanced | ~30 | CSRF, 테마, 언어, 검증 규칙, 상태 조회 |

---

## 3. 기본 함수 (Basic)

가장 먼저 호출하는 필수 설정 함수들입니다.

| 함수명 | 설명 | 사용 예 |
|--------|------|---------|
| `setTable(string)` | CRUD 대상 DB 테이블 지정 | `->setTable('customers')` |
| `setSubject(string)` | UI에 표시되는 작업 제목 | `->setSubject('고객')` |
| `displayAs(field, label)` | 필드명을 사람 친화적 라벨로 변경 | `->displayAs('first_name', '이름')` |
| `setLanguage(string)` | UI 언어 설정 | `->setLanguage('Korean')` |
| `where(field, value)` | 전체 쿼리에 WHERE 조건 추가 | `->where('is_deleted', 0)` |
| `setSkin(string)` | Bootstrap v3 / v4 스킨 선택 | `->setSkin('bootstrap-v4')` |
| `render()` | 최종 렌더링 실행, 반드시 마지막 호출 | `$output = $crud->render()` |

**최소 구현 예시 (CI4):**
```php
$crud = new GroceryCrud();
$crud->setTable('customers');
$crud->setSubject('고객');
$output = $crud->render();
```

---

## 4. 데이터그리드 함수 (Datagrid)

목록 화면(데이터그리드)의 컬럼, 버튼, 내보내기 등을 제어합니다.

### 4.1 컬럼 제어

| 함수명 | 설명 |
|--------|------|
| `columns(...fields)` | 표시할 컬럼 지정 (미지정 시 전체 표시) |
| `unsetColumns(...fields)` | 특정 컬럼 숨김 |
| `callbackColumn(field, fn)` | 컬럼 값을 콜백 함수로 변환 (HTML 출력 가능) |
| `defaultOrdering(field, dir)` | 기본 정렬 기준 (예: `'name', 'asc'`) |
| `defaultColumnWidth(field, px)` | 컬럼 기본 너비 설정 |
| `inlineEditFields(...fields)` | 목록에서 직접 인라인 편집 가능 필드 |

### 4.2 버튼 제어

| 함수명 | 설명 |
|--------|------|
| `setActionButton(label, url, icon, newTab)` | 행별 커스텀 액션 버튼 추가 |
| `setActionButtonMultiple(label, url, icon)` | 다중 선택 후 일괄 처리 버튼 |
| `setDatagridButton(label, url, icon)` | 데이터그리드 상단 버튼 추가 |

### 4.3 기능 활성화/비활성화

| 함수명 | 설명 |
|--------|------|
| `setExport()` | 내보내기 기능 활성화 |
| `unsetExport()` | 내보내기 전체 제거 |
| `unsetExportPdf()` | PDF 내보내기만 제거 |
| `unsetExportExcel()` | Excel 내보내기만 제거 |
| `setPrint()` | 인쇄 기능 활성화 |
| `unsetPrint()` | 인쇄 기능 제거 |
| `unsetPagination()` | 페이지네이션 제거 |
| `unsetFilters()` | 검색 필터 버튼 제거 |
| `unsetSearchColumns()` | 컬럼별 검색 제거 |
| `unsetSortingColumns(...fields)` | 특정 컬럼 정렬 기능 제거 |
| `unsetSettings()` | 설정 버튼 제거 |
| `unsetList()` | 목록 화면 제거 |
| `unsetBackToList()` | "목록으로" 버튼 제거 |
| `setDefaultState(state)` | 기본 진입 상태 설정 (list/add/edit 등) |
| `setCustomQuery(query)` | 완전한 커스텀 SQL 쿼리로 교체 |

---

## 5. CRUD 작업별 함수

### 5.1 삽입 (Insert)

| 함수명 | 설명 |
|--------|------|
| `addFields(...fields)` | 추가 폼에 표시할 필드 지정 |
| `unsetAddFields(...fields)` | 추가 폼에서 특정 필드 숨김 |
| `requiredAddFields(...fields)` | 추가 폼 필수 입력 필드 |
| `callbackAddField(field, fn)` | 추가 폼 특정 필드를 커스텀 HTML로 교체 |
| `callbackAddForm(fn)` | 추가 폼 데이터 전처리 (submit 전) |
| `callbackBeforeInsert(fn)` | DB 삽입 직전 콜백 (데이터 가공) |
| `callbackAfterInsert(fn)` | DB 삽입 완료 후 콜백 (이메일 발송 등) |
| `callbackInsert(fn)` | 기본 삽입 로직 완전 대체 |
| `setAdd()` | 추가 기능 강제 활성화 |
| `unsetAdd()` | 추가 버튼/기능 비활성화 |

### 5.2 수정 (Update)

| 함수명 | 설명 |
|--------|------|
| `editFields(...fields)` | 수정 폼에 표시할 필드 지정 |
| `unsetEditFields(...fields)` | 수정 폼에서 특정 필드 숨김 |
| `requiredEditFields(...fields)` | 수정 폼 필수 입력 필드 |
| `callbackEditField(field, fn)` | 수정 폼 특정 필드를 커스텀 HTML로 교체 |
| `callbackEditForm(fn)` | 수정 폼 데이터 전처리 |
| `callbackBeforeUpdate(fn)` | DB 수정 직전 콜백 |
| `callbackAfterUpdate(fn)` | DB 수정 완료 후 콜백 |
| `callbackUpdate(fn)` | 기본 수정 로직 완전 대체 |
| `setEdit()` | 수정 기능 강제 활성화 |
| `unsetEdit()` | 수정 버튼/기능 비활성화 |

### 5.3 삭제 (Delete)

| 함수명 | 설명 |
|--------|------|
| `callbackBeforeDelete(fn)` | 단건 삭제 직전 콜백 |
| `callbackAfterDelete(fn)` | 단건 삭제 완료 후 콜백 |
| `callbackDelete(fn)` | 기본 단건 삭제 로직 완전 대체 |
| `callbackBeforeDeleteMultiple(fn)` | 다중 삭제 직전 콜백 |
| `callbackAfterDeleteMultiple(fn)` | 다중 삭제 완료 후 콜백 |
| `callbackDeleteMultiple(fn)` | 기본 다중 삭제 로직 완전 대체 |
| `setDelete()` | 삭제 기능 강제 활성화 |
| `setDeleteMultiple()` | 다중 삭제 기능 활성화 |
| `unsetDelete()` | 삭제 기능 비활성화 |
| `unsetDeleteMultiple()` | 다중 삭제 기능 비활성화 |

### 5.4 조회 (Read)

기본적으로 비활성화 상태이므로 `setRead()`로 명시적 활성화 필요합니다.

| 함수명 | 설명 |
|--------|------|
| `setRead()` | 상세 조회 기능 활성화 |
| `unsetRead()` | 조회 기능 비활성화 |
| `readFields(...fields)` | 조회 폼에 표시할 필드 지정 |
| `unsetReadFields(...fields)` | 조회 폼에서 특정 필드 숨김 |
| `callbackReadField(field, fn)` | 조회 폼 특정 필드 커스텀 렌더링 |
| `callbackReadForm(fn)` | 조회 폼 데이터 가공 |
| `readOnlyFields(...fields)` | 수정/복제 폼에서 편집 불가 필드 |
| `readOnlyAddFields(...fields)` | 추가 폼에서 편집 불가 필드 |
| `readOnlyEditFields(...fields)` | 수정 폼에서 편집 불가 필드 |
| `readOnlyCloneFields(...fields)` | 복제 폼에서 편집 불가 필드 |

### 5.5 복제 (Clone)

기본 비활성화, `setClone()`으로 활성화합니다.

| 함수명 | 설명 |
|--------|------|
| `setClone()` | 복제 기능 활성화 |
| `unsetClone()` | 복제 기능 비활성화 |
| `cloneFields(...fields)` | 복제 폼에 표시할 필드 |
| `unsetCloneFields(...fields)` | 복제 폼에서 특정 필드 숨김 |
| `requiredCloneFields(...fields)` | 복제 폼 필수 입력 필드 |
| `callbackCloneField(field, fn)` | 복제 폼 특정 필드 커스텀 렌더링 |

---

## 6. 파일 업로드 함수 (Upload)

| 함수명 | 설명 |
|--------|------|
| `setFieldUpload(field, path)` | 단일 파일 업로드 필드 + 저장 경로 설정 |
| `setFieldUploadMultiple(field, path)` | 다중 파일 업로드 필드 설정 |
| `setFieldBlob(field)` | DB BLOB 타입으로 파일 저장 |
| `callbackBeforeUpload(fn)` | 업로드 파일 유효성 검사·필터링 |
| `callbackAfterUpload(fn)` | 업로드 완료 후 파일 처리 (리사이즈 등) |
| `callbackUpload(fn)` | 기본 업로드 로직 완전 대체 |

**사용 예:**
```php
$crud->setFieldUpload('profile_image', 'uploads/profiles/');
$crud->callbackAfterUpload(function($callbackObject) {
    // 업로드 후 썸네일 생성 등
    return $callbackObject;
});
```

---

## 7. 데이터베이스 함수 (Database)

### 7.1 관계 설정

| 함수명 | 설명 |
|--------|------|
| `setRelation(field, table, displayField)` | 1:N 관계 — 드롭다운 자동 생성 |
| `setRelationDynamic(field, table, displayField)` | 동적 로딩 관계 (대용량 데이터) |
| `setRelation1to1(field, table, fields)` | 1:1 관계 — 조인 테이블 인라인 편집 |
| `setRelationNtoN(field, junctionTable, relatedTable, ...)` | N:N 관계 — 체크박스/멀티셀렉트 자동 생성 |
| `setDependentRelation(childField, parentField, ...)` | 종속 드롭다운 (부모 선택 시 자식 옵션 변경) |

**1:N 관계 예시:**
```php
// customers.country_id → countries.name 드롭다운
$crud->setRelation('country_id', 'countries', 'name');
```

**N:N 관계 예시:**
```php
// products ↔ categories (junction: product_categories)
$crud->setRelationNtoN('categories', 'product_categories', 'categories', 'product_id', 'category_id', 'name');
```

### 7.2 모델 및 기타 설정

| 함수명 | 설명 |
|--------|------|
| `setModel(model)` | 기본 모델 대신 커스텀 모델 사용 |
| `getModel()` | 현재 사용 중인 모델 인스턴스 반환 |
| `setPrimaryKey(field)` | 기본키 수동 지정 (auto-detect 불가 시) |
| `setDatabaseSchema(schema)` | PostgreSQL 스키마 지정 |
| `setSequenceName(sequence)` | PostgreSQL 시퀀스명 지정 |

---

## 8. 전체 작업 공통 함수

모든 폼(추가/수정/복제/조회)에 동시 적용되는 함수입니다.

| 함수명 | 설명 |
|--------|------|
| `fields(...fields)` | 모든 폼에 동일한 필드 표시 |
| `unsetFields(...fields)` | 모든 폼에서 특정 필드 제거 |
| `requiredFields(...fields)` | 모든 폼의 필수 입력 필드 |
| `fieldOptions(field, options)` | 특정 필드의 추가 옵션 설정 |
| `groupFields(title, ...fields)` | 폼 내 필드를 그룹(섹션)으로 묶기 |
| `unsetOperations()` | 모든 CRUD 작업(추가/수정/삭제) 비활성화 |

---

## 9. 필드 타입 함수 (Field Type)

기본 text input 대신 다양한 입력 위젯을 지정합니다.

| 함수명 | 적용 범위 |
|--------|-----------|
| `fieldType(field, type, options)` | 기본값 — 추가·수정·복제 폼 모두 적용 |
| `fieldTypeAddForm(field, type)` | 추가 폼만 적용 |
| `fieldTypeEditForm(field, type)` | 수정 폼만 적용 |
| `fieldTypeCloneForm(field, type)` | 복제 폼만 적용 |
| `fieldTypeReadForm(field, type)` | 조회 폼만 적용 |
| `fieldTypeColumn(field, type)` | 데이터그리드 컬럼 타입 변경 |
| `fieldTypeSearchColumn(field, type)` | 검색 필터 컬럼 타입 변경 |
| `fieldTypeFormFields(field, type)` | 모든 폼(add/edit/clone/read) 동시 적용 |
| `setTexteditor(field)` | CKEditor WYSIWYG 에디터 활성화 |
| `unsetTexteditor(field)` | 텍스트 에디터 비활성화 |

**지원 필드 타입 (주요):**

| 타입 | 설명 |
|------|------|
| `text` | 기본 텍스트 input |
| `textarea` | 멀티라인 텍스트 |
| `dropdown` | select 드롭다운 |
| `multiselect` | 다중 선택 드롭다운 |
| `checkbox` | 체크박스 |
| `radio` | 라디오 버튼 |
| `date` | 날짜 피커 |
| `datetime` | 날짜+시간 피커 |
| `numeric` | 숫자 입력 |
| `password` | 비밀번호 마스킹 |
| `color` | 색상 선택기 |
| `hidden` | 숨겨진 필드 |
| `invisible` | 폼에 표시 안 됨 |
| `readonly` | 읽기 전용 표시 |
| `upload_file` | 파일 업로드 위젯 |

---

## 10. 고급 함수 (Advanced)

### 10.1 상태(State) 조회

| 함수명 | 설명 |
|--------|------|
| `getState()` | 현재 상태명 반환 (`'list'`, `'add'`, `'edit'` 등) |
| `getStateInfo()` | 현재 상태의 전체 정보 객체 반환 (ID, 필드 등 포함) |
| `setDefaultState(state)` | CRUD 초기 진입 상태 설정 |

**상태 목록:**

| 상태명 | 설명 |
|--------|------|
| `list` | 데이터그리드 목록 |
| `add` | 추가 폼 |
| `edit` | 수정 폼 |
| `delete` | 삭제 처리 |
| `read` | 조회 폼 |
| `clone` | 복제 폼 |
| `ajax_list` | AJAX 목록 요청 |
| `export` | 내보내기 |
| `print` | 인쇄 |

### 10.2 보안

| 함수명 | 설명 |
|--------|------|
| `setCsrfTokenName(name)` | CSRF 토큰 필드명 지정 |
| `setCsrfTokenValue(value)` | CSRF 토큰 값 지정 |
| `setRule(field, rule)` | 단일 필드 유효성 검사 규칙 |
| `setRules(rulesArray)` | 다중 필드 유효성 검사 규칙 |
| `uniqueFields(...fields)` | 중복 불가 필드 지정 |

### 10.3 테마 & 언어

| 함수명 | 설명 |
|--------|------|
| `setTheme(name)` | 테마 변경 |
| `setThemePath(path)` | 커스텀 테마 경로 지정 |
| `setLangString(key, value)` | 특정 번역 문자열 덮어쓰기 |
| `setLanguagePath(path)` | 커스텀 언어 파일 경로 지정 |

### 10.4 기타 고급

| 함수명 | 설명 |
|--------|------|
| `setMasterDetail(crud)` | 마스터-디테일 그리드 연결 |
| `setApiUrlPath(path)` | API 엔드포인트 URL 변경 |
| `setConfig(key, value)` | 내부 설정 값 직접 지정 |
| `setUniqueId(id)` | 한 페이지에 여러 CRUD 인스턴스 사용 시 고유 ID |
| `mapColumn(field, mapField)` | 컬럼 필드명 매핑 |
| `replaceState(state, fn)` | 특정 상태를 완전히 커스텀 로직으로 대체 |
| `unsetBootstrap()` | Bootstrap CSS 자동 로드 비활성화 |
| `unsetJquery()` | jQuery 자동 로드 비활성화 |
| `unsetJqueryUi()` | jQuery UI 자동 로드 비활성화 |
| `unsetCssTheme()` | 테마 CSS 자동 로드 비활성화 |
| `unsetCssIcons()` | 아이콘 CSS 자동 로드 비활성화 |
| `unsetCssThirdParty()` | 서드파티 CSS 자동 로드 비활성화 |
| `unsetAutoloadJavaScript()` | JS 자동 로드 비활성화 |

---

## 11. 함수 수 요약

| 카테고리 | 함수 수 |
|----------|---------|
| Basic | 7 |
| Datagrid | 24 |
| Insert | 10 |
| Update | 10 |
| Delete | 10 |
| Read | 10 |
| Clone | 6 |
| Upload | 6 |
| Database | 10 |
| All Operations | 6 |
| Field Type | 10 |
| Advanced | ~30 |
| **합계** | **약 139+** |

---

## 12. CI4 통합 시 주요 활용 패턴

### 12.1 기본 CI4 컨트롤러 구조

```php
<?php
namespace App\Controllers;

use GroceryCrud\Core\GroceryCrud;

class CustomersController extends BaseController
{
    public function index()
    {
        $crud = new GroceryCrud();
        $crud->setTable('customers');
        $crud->setSubject('고객');
        $crud->setLanguage('Korean');

        // 표시할 컬럼 지정
        $crud->columns('first_name', 'last_name', 'email', 'phone');

        // 라벨 변경
        $crud->displayAs('first_name', '이름');
        $crud->displayAs('last_name', '성');

        // 관계 설정
        $crud->setRelation('country_id', 'countries', 'name');

        $output = $crud->render();
        return view('crud_view', (array) $output);
    }
}
```

### 12.2 콜백 활용 패턴

```php
// 삽입 전 비밀번호 해싱
$crud->callbackBeforeInsert(function($callbackObject) {
    $callbackObject->data['password'] = password_hash(
        $callbackObject->data['password'], PASSWORD_BCRYPT
    );
    return $callbackObject;
});

// 컬럼 커스텀 렌더링 (상태 배지)
$crud->callbackColumn('status', function($value, $row) {
    $color = $value === 'active' ? 'success' : 'danger';
    return "<span class='badge bg-{$color}'>{$value}</span>";
});

// 삭제 전 관련 데이터 정리
$crud->callbackBeforeDelete(function($callbackObject) {
    // 연관 파일 삭제 등 처리
    return $callbackObject;
});
```

### 12.3 권한별 기능 제어 패턴

```php
// 읽기 전용 사용자
if ($userRole === 'viewer') {
    $crud->unsetAdd();
    $crud->unsetEdit();
    $crud->unsetDelete();
    $crud->setRead();
}

// 관리자
if ($userRole === 'admin') {
    $crud->setClone();
    $crud->setDeleteMultiple();
    $crud->setExport();
}
```

### 12.4 필드 타입 활용 패턴

```php
// 날짜 피커
$crud->fieldType('birth_date', 'date');

// WYSIWYG 에디터
$crud->setTexteditor('description');

// 상태 드롭다운
$crud->fieldType('status', 'dropdown', [
    'active'   => '활성',
    'inactive' => '비활성',
    'pending'  => '대기',
]);

// 비밀번호 필드 (수정 폼만)
$crud->fieldTypeEditForm('password', 'password');
```

---

> **참고:** 위 문서는 공식 문서 기준으로 작성되었으며, Community Edition은 일부 고급 기능(N:N 관계, 콜백 일부, 복제 기능 등)이 제한될 수 있습니다. Enterprise Edition에서 전체 기능을 사용할 수 있습니다.
