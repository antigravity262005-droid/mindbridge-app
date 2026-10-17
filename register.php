<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/helpers.php';

if (is_logged_in()) {
    header('Location: ' . BASE_URL . '/user/dashboard.php');
    exit;
}

$errors = [];
$nameVal = '';
$emailVal = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    $nameVal = $name;
    $emailVal = $email;

    if (empty($name) || mb_strlen($name) < 2) {
        $errors[] = 'Please provide your full name (minimum 2 characters).';
    } elseif (mb_strlen($name) > 120) {
        $errors[] = 'Full name must not exceed 120 characters.';
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please provide a valid educational or personal email address.';
    } elseif (mb_strlen($email) > 150) {
        $errors[] = 'Email address must not exceed 150 characters.';
    }

    if (empty($password) || mb_strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }

    if ($password !== $passwordConfirm) {
        $errors[] = 'Password confirmation does not match.';
    }

    if (empty($errors)) {
        $pdo = get_db();
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()) {
            $errors[] = 'An account with this email address is already registered. Please sign in instead.';
        } else {
            // Insert new student account with role 'user'
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $insert = $pdo->prepare("
                INSERT INTO users (full_name, email, password_hash, role, must_change_password)
                VALUES (:full_name, :email, :hash, 'user', 0)
            ");
            $insert->execute([
                'full_name' => $name,
                'email'     => $email,
                'hash'      => $hash
            ]);

            $newUserId = (int)$pdo->lastInsertId();

            // Auto-sign in the new student
            $newUser = [
                'user_id'              => $newUserId,
                'full_name'            => $name,
                'email'                => $email,
                'role'                 => 'user',
                'must_change_password' => 0,
            ];
            login_user($newUser);

            flash_set('success', 'Your student account has been created! Welcome to MindBridge.');
            header('Location: ' . BASE_URL . '/user/dashboard.php');
            exit;
        }
    }
}

$pageTitle = 'Create Account · MindBridge';
require __DIR__ . '/includes/header.php';
?>

<div class="max-w-md mx-auto py-8">
  <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-8 shadow-xl">
    
    <div class="text-center mb-8">
      <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-950 text-indigo-800 dark:text-indigo-400 font-display font-bold text-2xl flex items-center justify-center mx-auto mb-3 shadow-inner">
        M
      </div>
      <h1 class="font-display font-bold text-2xl text-slate-900 dark:text-white">Create Student Account</h1>
      <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">Join MindBridge for confidential wellness support</p>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="mb-6 p-4 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-300 dark:border-rose-800 text-rose-800 dark:text-rose-200 text-sm">
        <div class="font-semibold mb-1 flex items-center gap-2">
          <span>⚠️</span> Please correct the following:
        </div>
        <ul class="list-disc list-inside space-y-1 text-xs sm:text-sm">
          <?php foreach ($errors as $err): ?>
            <li><?= e($err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/register.php" class="space-y-4" novalidate>
      <?= csrf_field() ?>

      <div>
        <label for="full_name" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Full Name</label>
        <input type="text" id="full_name" name="full_name" value="<?= e($nameVal) ?>" required placeholder="e.g. Maria Santos"
          class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-600">
      </div>

      <div>
        <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Email Address</label>
        <input type="email" id="email" name="email" value="<?= e($emailVal) ?>" required placeholder="student@university.edu"
          class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-600">
      </div>

      <div>
        <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Password (min. 8 characters)</label>
        <input type="password" id="password" name="password" required placeholder="••••••••"
          class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-600">
      </div>

      <div>
        <label for="password_confirm" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Confirm Password</label>
        <input type="password" id="password_confirm" name="password_confirm" required placeholder="••••••••"
          class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-600">
      </div>

      <div class="pt-2">
        <button type="submit" class="w-full py-3.5 px-4 rounded-xl bg-indigo-800 hover:bg-indigo-900 text-white font-semibold text-sm shadow-md hover:shadow-lg transition-all focus:outline-none focus:ring-2 focus:ring-indigo-600">
          Create Account
        </button>
      </div>
    </form>

    <div class="mt-6 text-center text-xs text-slate-500">
      Already have an account? <a href="<?= BASE_URL ?>/login.php" class="font-semibold text-indigo-700 dark:text-indigo-400 hover:underline">Sign in here</a>
    </div>

  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
