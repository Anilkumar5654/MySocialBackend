<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>MySocial | Admin Panel</title>

  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700&display=swap">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">

  <?= view('admin/includes/styles') ?>

  <style>
    body { font-family: 'Poppins', sans-serif; }
    .brand-link { border-bottom: 1px solid rgba(255,255,255,0.1) !important; padding: 15px !important; }
    .brand-text { font-weight: 600 !important; color: #fff !important; }
    .main-header { border-bottom: 1px solid var(--border-soft) !important; background: #fff !important; }
  </style>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">

  <?= view('admin/includes/header') ?>
  <?= view('admin/includes/sidebar') ?>

  <div class="content-wrapper p-4">
    <?= $this->renderSection('content') ?>
  </div>

</div>

<?= view('admin/includes/scripts') ?>
</body>
</html>
