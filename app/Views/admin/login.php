<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Moviedbr</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #1a1a2e; height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .login-card { background: #ffffff; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.3); overflow: hidden; width: 100%; max-width: 400px; }
        .login-header { background: #16213e; padding: 30px; text-align: center; color: white; }
        .login-body { padding: 30px; }
        .btn-login { background: #e94560; border: none; width: 100%; padding: 12px; border-radius: 8px; font-weight: bold; color: white; transition: 0.3s; }
        .btn-login:hover { background: #950740; }
        .form-control:focus { border-color: #e94560; box-shadow: 0 0 0 0.25 cold rgba(233, 69, 96, 0.25); }
        .alert { font-size: 0.9rem; }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <h3>Moviedbr Admin</h3>
        <p class="mb-0">Please sign in to continue</p>
    </div>
    <div class="login-body">
        
        <?php if(session()->getFlashdata('error')): ?>
            <div class="alert alert-danger text-center">
                <?= session()->getFlashdata('error') ?>
            </div>
        <?php endif; ?>

        <form action="<?= base_url('admin/login') ?>" method="POST">
            <?= csrf_field() ?>
            
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="admin@example.com" required>
            </div>
            
            <div class="mb-4">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            
            <button type="submit" class="btn btn-login">Login to Dashboard</button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
