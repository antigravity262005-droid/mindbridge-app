<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

require_admin();
$admin = current_user();
$pdo = get_db();

// 1. Core Platform Counts
$totalStudents = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$openRequests = (int)$pdo->query("SELECT COUNT(*) FROM support_requests WHERE status IN ('Submitted', 'In Review', 'Scheduled')")->fetchColumn();
$activeServices = (int)$pdo->query("SELECT COUNT(*) FROM support_services WHERE is_active = 1")->fetchColumn();
$campaignParticipants = (int)$pdo->query("SELECT COUNT(*) FROM campaign_participants")->fetchColumn();

// 2. Requests grouped by status for Chart.js
$statusCountsRaw = $pdo->query("
    SELECT status, COUNT(*) as cnt 
    FROM support_requests 
    GROUP BY status
")->fetchAll(PDO::FETCH_KEY_PAIR);

$allStatuses = ['Submitted', 'In Review', 'Scheduled', 'Completed', 'Closed'];
$chartLabels = $allStatuses;
$chartCounts = [];
foreach ($allStatuses as $st) {
    $chartCounts[] = (int)($statusCountsRaw[$st] ?? 0);
}

// 3. Recent 5 Support Requests
$recentRequests = $pdo->query("
    SELECT r.request_id, r.request_type, r.subject, r.status, r.submitted_at, u.full_name as student_name, s.service_name
    FROM support_requests r
    JOIN users u ON r.user_id = u.user_id
    LEFT JOIN support_services s ON r.service_id = s.service_id
    ORDER BY r.submitted_at DESC LIMIT 5
")->fetchAll();

$pageTitle = 'Administrator Dashboard · MindBridge';
require __DIR__ . '/../includes/header.php';
?>

<!-- Header with Live Indicator -->
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-200 dark:border-slate-800">
  <div>
    <h1 class="font-display font-bold text-2xl sm:text-3xl text-slate-900 dark:text-white">
      Administrator Overview
    </h1>
    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
      Central management for student requests, educational resources, and directory contacts.
    </p>
  </div>

  <div class="flex items-center gap-3">
    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-slate-100 dark:bg-slate-800 text-xs font-medium text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
      <span class="live-pulse-dot"></span>
      <span id="live-indicator-text">Live · Connected</span>
    </div>
    <a href="<?= BASE_URL ?>/admin/requests.php" class="px-4 py-2 text-xs sm:text-sm font-semibold rounded-xl bg-indigo-800 hover:bg-indigo-900 text-white shadow-sm transition-colors">
      Manage Requests (<?= $openRequests ?> Open)
    </a>
  </div>
</div>

<!-- Key Stat Cards -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 my-8">
  
  <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
    <div class="flex items-center justify-between text-slate-400 mb-2">
      <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Registered Students</span>
      <span class="text-lg">🎓</span>
    </div>
    <span class="font-display font-bold text-3xl text-indigo-900 dark:text-indigo-400" id="dash-total-students"><?= $totalStudents ?></span>
    <span class="block text-xs text-slate-500 mt-1">No fixed account limit</span>
  </div>

  <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
    <div class="flex items-center justify-between text-slate-400 mb-2">
      <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Open Requests</span>
      <span class="text-lg">⚠️</span>
    </div>
    <span class="font-display font-bold text-3xl text-amber-700 dark:text-amber-400" id="dash-admin-open-requests"><?= $openRequests ?></span>
    <span class="block text-xs text-slate-500 mt-1">Awaiting review or scheduling</span>
  </div>

  <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
    <div class="flex items-center justify-between text-slate-400 mb-2">
      <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Active Services</span>
      <span class="text-lg">🛡️</span>
    </div>
    <span class="font-display font-bold text-3xl text-emerald-700 dark:text-emerald-400"><?= $activeServices ?></span>
    <span class="block text-xs text-slate-500 mt-1">School & community directory</span>
  </div>

  <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
    <div class="flex items-center justify-between text-slate-400 mb-2">
      <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Campaign Participants</span>
      <span class="text-lg">🤝</span>
    </div>
    <span class="font-display font-bold text-3xl text-slate-800 dark:text-slate-200"><?= $campaignParticipants ?></span>
    <span class="block text-xs text-slate-500 mt-1">Student wellness sign-ups</span>
  </div>

</div>

<!-- Admin Charts & Recent Inquiries Grid -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
  
  <!-- Left: Requests Distribution Chart -->
  <div class="lg:col-span-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm">
    <div class="mb-4">
      <h2 class="font-display font-bold text-lg text-slate-900 dark:text-white">Requests by Current Status</h2>
      <span class="text-xs text-slate-400">Distribution across active follow-up stages</span>
    </div>
    <div class="h-64 w-full flex items-center justify-center">
      <canvas id="adminStatusChart"></canvas>
    </div>
  </div>

  <!-- Right: Recent Requests Table -->
  <div class="lg:col-span-7 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm space-y-4">
    <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
      <div>
        <h2 class="font-display font-bold text-lg text-slate-900 dark:text-white">Recent Student Inquiries</h2>
        <span class="text-xs text-slate-400">Latest 5 submissions requiring follow-up</span>
      </div>
      <a href="<?= BASE_URL ?>/admin/requests.php" class="text-xs font-semibold text-indigo-700 dark:text-indigo-400 hover:underline">
        View All Inquiries →
      </a>
    </div>

    <?php if (empty($recentRequests)): ?>
      <p class="py-8 text-center text-xs text-slate-400">No support requests have been submitted yet.</p>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead>
            <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-400 uppercase tracking-wider text-[10px]">
              <th class="py-2.5 px-2">Student</th>
              <th class="py-2.5 px-2">Type</th>
              <th class="py-2.5 px-2">Subject</th>
              <th class="py-2.5 px-2">Status</th>
              <th class="py-2.5 px-2 text-right">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
            <?php foreach ($recentRequests as $req): ?>
              <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                <td class="py-3 px-2 font-semibold text-slate-900 dark:text-white">
                  <?= e($req['student_name']) ?>
                </td>
                <td class="py-3 px-2 capitalize text-slate-600 dark:text-slate-400">
                  <?= e($req['request_type']) ?>
                </td>
                <td class="py-3 px-2 max-w-[180px] truncate text-slate-700 dark:text-slate-300">
                  <?= e($req['subject']) ?>
                </td>
                <td class="py-3 px-2">
                  <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold <?= badge_status_class($req['status']) ?>">
                    <?= e($req['status']) ?>
                  </span>
                </td>
                <td class="py-3 px-2 text-right">
                  <a href="<?= BASE_URL ?>/admin/requests.php?id=<?= $req['request_id'] ?>" class="font-bold text-indigo-700 dark:text-indigo-400 hover:underline">
                    Follow Up
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

  </div>

</div>

<!-- Chart.js Distribution Script -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  const ctx = document.getElementById('adminStatusChart');
  if (!ctx) return;

  const isDark = document.documentElement.classList.contains('dark');
  const textColor = isDark ? '#f8fafc' : '#0f172a';

  new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: <?= json_encode($chartLabels) ?>,
      datasets: [{
        data: <?= json_encode($chartCounts) ?>,
        backgroundColor: [
          '#f59e0b', // Submitted (Amber)
          '#3b82f6', // In Review (Blue)
          '#1f3a93', // Scheduled (Deep Indigo)
          '#10b981', // Completed (Emerald)
          '#64748b'  // Closed (Slate)
        ],
        borderWidth: 2,
        borderColor: isDark ? '#1e293b' : '#ffffff'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom',
          labels: { color: textColor, font: { family: 'Inter', size: 11 } }
        }
      }
    }
  });
});
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
