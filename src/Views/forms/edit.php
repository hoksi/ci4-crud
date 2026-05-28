<?php
/**
 * CI4 CRUD — 수정 폼 뷰 템플릿
 *
 * @var \Hoksi\Ci4Crud\Renderer\FormRenderer $renderer
 * @var int|string $id
 */
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>수정</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <?= $renderer->renderAssets('edit') ?>
</head>
<body>
<div class="container py-4">
  <?= $renderer->renderEdit($id) ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
