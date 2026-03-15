<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>MOVIEDBR | <?= $title ?? 'Admin' ?></title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <style>
    body, .content-wrapper { background-color: #000000 !important; color: #ffffff !important; }
    .main-sidebar { background-color: #0d0d0d !important; border-right: 1px solid #1f1f1f; }
    .brand-text { color: #ff007f !important; font-weight: 800 !important; letter-spacing: 2px; text-transform: uppercase; }
    .nav-pills .nav-link { color: #bbbbbb !important; }
    .nav-pills .nav-link.active { background-color: #ff007f !important; color: #ffffff !important; box-shadow: 0 0 15px rgba(255, 0, 127, 0.4); }
    .main-header { background-color: #0d0d0d !important; border-bottom: 1px solid #1f1f1f !important; }
    .navbar-light .navbar-nav .nav-link { color: #ffffff !important; }
    .main-footer { background-color: #0d0d0d !important; border-top: 1px solid #1f1f1f !important; color: #555 !important; }
  </style>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
  <nav class="main-header navbar navbar-expand">
    <ul class="navbar-nav">
      <li class="nav-item"><a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a></li>
    </ul>
    <ul class="navbar-nav ml-auto">
      <li class="nav-item"><a class="nav-link" href="<?= base_url('admin/logout') ?>">Logout <i class="fas fa-sign-out-alt"></i></a></li>
    </ul>
  </nav>

  <aside class="main-sidebar elevation-4">
    <a href="<?= base_url('admin/dashboard') ?>" class="brand-link text-center"><span class="brand-text">MOVIEDBR</span></a>
    <div class="sidebar mt-3">
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
          <li class="nav-item"><a href="<?= base_url('admin/dashboard') ?>" class="nav-link"><i class="nav-icon fas fa-tachometer-alt"></i> <p>Dashboard</p></a></li>
          <li class="nav-item"><a href="<?= base_url('admin/users') ?>" class="nav-link"><i class="nav-icon fas fa-users"></i> <p>Users List</p></a></li>
          <li class="nav-item"><a href="<?= base_url('admin/roles') ?>" class="nav-link"><i class="nav-icon fas fa-user-shield"></i> <p>Role Management</p></a></li>
          </ul>
      </nav>
    </div>
  </aside>

  <div class="content-wrapper">
    <?= $this->renderSection('content') ?>
  </div>

  <footer class="main-footer"><strong>Copyright &copy; 2025 <span style="color: #ff007f;">MOVIEDBR</span>.</strong></footer>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html>
