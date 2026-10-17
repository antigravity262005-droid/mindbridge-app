<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/helpers.php';

require_login();
$user = current_user();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($newPassword) || mb_strlen($newPassword) < 8) {
        $errors[] = 'New password must be at least 8 characters long.';
    }

    if ($newPassword !== $confirmPassword) {
        $errors[] = 'Password confirmation does not match.';
    }

    if (empty($errors)) {
        $pdo = get_db();
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);

        $stmt = $pdo->prepare("UPDATE users SET password_hash = :hash, must_change_password = 0 WHERE user_id = :id");
        $stmt->execute([
            'hash' => $hash,
            'id'   => $user['user_id']
        ]);

        // Update session state
        $_SESSION['user']['must_change_password'] = 0;

        flash_set('success', 'Your password has been successfully updated.');
        if ($user['role'] === 'administrator') {
            header('Location: ' . BASE_URL . '/admin/dashboard.php');
        } else {
            header('Location: ' . BASE_URL . '/user/dashboard.php');
        }
        exit;
    }
}

$pageTitle = 'Update Password · MindBridge';
require __DIR__ . '/includes/header.php';
?>

<div class="max-w-md mx-auto py-8">
  <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-8 shadow-xl">
    
    <div class="text-center mb-6">
      <span class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-amber-50 dark:bg-amber-950 text-amber-700 dark:text-amber-400 text-xl font-bold mb-3 shadow-inner">
        🔒
      </span>
      <h1 class="font-display font-bold text-2xl text-slate-900 dark:text-white">Update Your Password</h1>
      <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
        <?= $user['must_change_password'] ? 'Please create a secure personal password to continue' : 'Choose a new strong password for your account' ?>
      </p>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="mb-6 p-4 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-300 dark:border-rose-800 text-rose-800 dark:text-rose-200 text-sm">
        <ul class="list-disc list-inside space-y-1 text-xs sm:text-sm">
          <?php foreach ($errors as $err): ?>
            <li><?= e($err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/change-password.php" class="space-y-4">
      <?= csrf_field() ?>

      <div>
        <label for="new_password" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">New Password (min. 8 characters)</label>
        <input type="password" id="new_password" name="new_password" required placeholder="••••••••"
          class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-600">
      </div>

      <div>
        <label for="confirm_password" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Confirm New Password</label>
        <input type="password" id="confirm_password" name="confirm_password" required placeholder="••••••••"
          class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-600">
      </div>

      <div class="pt-2">
        <button type="submit" class="w-full py-3.5 px-4 rounded-xl bg-indigo-800 hover:bg-indigo-900 text-white font-semibold text-sm shadow-md hover:shadow-lg transition-all focus:outline-none focus:ring-2 focus:ring-indigo-600">
          Save New Password
        </button>
      </div>
    </form>

    <?php if (!$user['must_change_password']): ?>
      <div class="mt-6 text-center text-xs text-slate-500">
        <a href="<?= $user['role'] === 'administrator' ? BASE_URL . '/admin/dashboard.php' : BASE_URL . '/user/dashboard.php' ?>" class="hover:underline">Cancel & Return to Dashboard</a>
      </div>
    <?php endif; ?>

  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
