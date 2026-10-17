<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/helpers.php';

// Redirect if already logged in
if (is_logged_in()) {
    $u = current_user();
    if ($u['role'] === 'administrator') {
        header('Location: ' . BASE_URL . '/admin/dashboard.php');
    } else {
        header('Location: ' . BASE_URL . '/user/dashboard.php');
    }
    exit;
}

$error = null;
$emailValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $emailValue = $email;

    if (empty($email) || empty($password)) {
        $error = 'Please enter both your email address and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please provide a valid email format.';
    } else {
        $pdo = get_db();
        $stmt = $pdo->prepare("SELECT user_id, full_name, email, password_hash, role, must_change_password FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            login_user($user);

            if ((int)$user['must_change_password'] === 1) {
                flash_set('warning', 'Welcome to MindBridge! For your security, you must update your initial password.');
                header('Location: ' . BASE_URL . '/change-password.php');
                exit;
            }

            flash_set('success', 'Welcome back, ' . $user['full_name'] . '!');
            if ($user['role'] === 'administrator') {
                header('Location: ' . BASE_URL . '/admin/dashboard.php');
            } else {
                header('Location: ' . BASE_URL . '/user/dashboard.php');
            }
            exit;
        } else {
            $error = 'Invalid email address or password. Please verify your credentials.';
        }
    }
}

$pageTitle = 'Sign In · MindBridge';
require __DIR__ . '/includes/header.php';
?>

<div class="max-w-md mx-auto py-8">
  <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-8 shadow-xl">
    
    <div class="text-center mb-8">
      <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-950 text-indigo-800 dark:text-indigo-400 font-display font-bold text-2xl flex items-center justify-center mx-auto mb-3 shadow-inner">
        M
      </div>
      <h1 class="font-display font-bold text-2xl text-slate-900 dark:text-white">Welcome Back</h1>
      <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">Sign in to access your wellness dashboard</p>
    </div>

    <?php if ($error): ?>
      <div class="mb-6 p-4 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-300 dark:border-rose-800 text-rose-800 dark:text-rose-200 text-sm font-medium flex items-center gap-3">
        <span>⚠️</span>
        <span><?= e($error) ?></span>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/login.php" class="space-y-5" novalidate>
      <?= csrf_field() ?>

      <div>
        <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">Email Address</label>
        <input type="email" id="email" name="email" value="<?= e($emailValue) ?>" required autocomplete="email" placeholder="student@mindbridge.edu"
          class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-600 dark:focus:ring-indigo-400 transition-colors">
      </div>

      <div>
        <div class="flex items-center justify-between mb-1.5">
          <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300">Password</label>
        </div>
        <input type="password" id="password" name="password" required autocomplete="current-password" placeholder="••••••••"
          class="w-full px-4 py-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-600 dark:focus:ring-indigo-400 transition-colors">
      </div>

      <button type="submit" class="w-full py-3.5 px-4 rounded-xl bg-indigo-800 hover:bg-indigo-900 text-white font-semibold text-sm shadow-md hover:shadow-lg transition-all focus:outline-none focus:ring-2 focus:ring-indigo-600">
        Sign In
      </button>
    </form>

    <!-- Seeded Demo Accounts Quick Helper -->
    <div class="mt-8 pt-6 border-t border-slate-200 dark:border-slate-800 text-xs text-slate-500 dark:text-slate-400 space-y-2">
      <span class="font-semibold text-slate-700 dark:text-slate-300 block">Classroom Demo Accounts:</span>
      <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
        <span class="font-medium text-slate-800 dark:text-slate-200">Admin:</span> <code>admin@mindbridge.edu</code> / <code>Admin@123</code>
      </div>
      <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
        <span class="font-medium text-slate-800 dark:text-slate-200">Student:</span> <code>student@mindbridge.edu</code> / <code>Student@123</code>
      </div>
    </div>

    <div class="mt-6 text-center text-xs text-slate-500">
      Need a new account? <a href="<?= BASE_URL ?>/register.php" class="font-semibold text-indigo-700 dark:text-indigo-400 hover:underline">Register here</a>
    </div>

  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
