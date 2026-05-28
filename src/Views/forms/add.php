<?php
/**
 * CI4 CRUD — 추가 폼 뷰 템플릿
 *
 * 이 파일을 앱의 views/ 디렉토리로 복사하여 커스터마이즈할 수 있습니다.
 *
 * @var \Hoksi\Ci4Crud\Renderer\FormRenderer $renderer
 */
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $renderer->renderSchema()['subject'] ?? 'CRUD' ?> 추가</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <?= $renderer->renderAssets('add') ?>
</head>
<body>
<div class="container py-4">
  <?= $renderer->renderAdd() ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
