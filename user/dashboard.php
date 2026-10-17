<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';

require_student();
$user = current_user();
$userId = (int)$user['user_id'];
$pdo = get_db();

// 1. Fetch user-specific stats
$requestCountStmt = $pdo->prepare("SELECT COUNT(*) FROM support_requests WHERE user_id = :uid");
$requestCountStmt->execute(['uid' => $userId]);
$totalRequests = (int)$requestCountStmt->fetchColumn();

$openRequestStmt = $pdo->prepare("SELECT COUNT(*) FROM support_requests WHERE user_id = :uid AND status IN ('Submitted', 'In Review', 'Scheduled')");
$openRequestStmt->execute(['uid' => $userId]);
$openRequests = (int)$openRequestStmt->fetchColumn();

$checkinCountStmt = $pdo->prepare("SELECT COUNT(*) FROM wellness_checkins WHERE user_id = :uid");
$checkinCountStmt->execute(['uid' => $userId]);
$totalCheckins = (int)$checkinCountStmt->fetchColumn();

$campaignStmt = $pdo->prepare("SELECT COUNT(*) FROM campaign_participants WHERE user_id = :uid");
$campaignStmt->execute(['uid' => $userId]);
$joinedCampaigns = (int)$campaignStmt->fetchColumn();

// 2. Fetch latest request follow-up update
$latestUpdateStmt = $pdo->prepare("
    SELECT u.status, u.update_note, u.created_at, r.subject, r.request_type, r.request_id
    FROM support_request_updates u
    JOIN support_requests r ON u.request_id = r.request_id
    WHERE r.user_id = :uid
    ORDER BY u.created_at DESC LIMIT 1
");
$latestUpdateStmt->execute(['uid' => $userId]);
$latestUpdate = $latestUpdateStmt->fetch();

// 3. Fetch latest educational resource
$latestResource = $pdo->query("
    SELECT r.resource_id, r.title, r.content, r.published_at, c.category_name
    FROM mental_health_resources r
    JOIN resource_categories c ON r.category_id = c.category_id
    ORDER BY r.published_at DESC LIMIT 1
")->fetch();

// 4. Fetch recent personal check-ins
$recentCheckinsStmt = $pdo->prepare("
    SELECT mood_rating, reflection, checkin_date 
    FROM wellness_checkins 
    WHERE user_id = :uid 
    ORDER BY checkin_date DESC LIMIT 3
");
$recentCheckinsStmt->execute(['uid' => $userId]);
$recentCheckins = $recentCheckinsStmt->fetchAll();

$pageTitle = 'Student Wellness Dashboard · MindBridge';
require __DIR__ . '/../includes/header.php';
?>

<!-- Dashboard Top Header with Live Indicator -->
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-200 dark:border-slate-800">
  <div>
    <h1 class="font-display font-bold text-2xl sm:text-3xl text-slate-900 dark:text-white">
      Welcome, <?= e($user['full_name']) ?>
    </h1>
    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
      Your confidential sanctuary for mental health support, self-reflection, and school resources.
    </p>
  </div>

  <div class="flex items-center gap-3">
    <!-- Live Polling Badge -->
    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-slate-100 dark:bg-slate-800 text-xs font-medium text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
      <span class="live-pulse-dot"></span>
      <span id="live-indicator-text">Live · Connected</span>
    </div>
    <a href="<?= BASE_URL ?>/user/requests.php" class="px-4 py-2 text-xs sm:text-sm font-semibold rounded-xl bg-indigo-800 hover:bg-indigo-900 text-white shadow-sm transition-colors">
      + New Request
    </a>
  </div>
</div>

<!-- Key Stat Cards -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 my-8">
  
  <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
    <div class="flex items-center justify-between text-slate-400 mb-2">
      <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Open Requests</span>
      <span class="text-lg">📋</span>
    </div>
    <span class="font-display font-bold text-3xl text-indigo-900 dark:text-indigo-400" id="dash-open-requests"><?= $openRequests ?></span>
    <span class="block text-xs text-slate-500 mt-1"><?= $totalRequests ?> total lifetime submitted</span>
  </div>

  <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
    <div class="flex items-center justify-between text-slate-400 mb-2">
      <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Private Check-ins</span>
      <span class="text-lg">🌱</span>
    </div>
    <span class="font-display font-bold text-3xl text-emerald-700 dark:text-emerald-400"><?= $totalCheckins ?></span>
    <span class="block text-xs text-slate-500 mt-1">100% confidential to you</span>
  </div>

  <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
    <div class="flex items-center justify-between text-slate-400 mb-2">
      <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Campaigns Joined</span>
      <span class="text-lg">🤝</span>
    </div>
    <span class="font-display font-bold text-3xl text-amber-700 dark:text-amber-400"><?= $joinedCampaigns ?></span>
    <span class="block text-xs text-slate-500 mt-1">Campus awareness participation</span>
  </div>

  <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
    <div class="flex items-center justify-between text-slate-400 mb-2">
      <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Verified Support</span>
      <span class="text-lg">🛡️</span>
    </div>
    <span class="font-display font-bold text-3xl text-slate-800 dark:text-slate-200">24/7</span>
    <span class="block text-xs text-slate-500 mt-1">School & national contacts</span>
  </div>

</div>

<!-- Main Dashboard Grid -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
  
  <!-- Left 2 Cols: Latest Follow-up & Quick Mood Check-in -->
  <div class="lg:col-span-2 space-y-8">
    
    <!-- Latest Follow-up Box -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
      <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100 dark:border-slate-800">
        <div class="flex items-center gap-2">
          <span class="w-3 h-3 rounded-full bg-indigo-600"></span>
          <h2 class="font-display font-bold text-lg text-slate-900 dark:text-white">Latest Referral & Follow-up Status</h2>
        </div>
        <a href="<?= BASE_URL ?>/user/requests.php" class="text-xs font-semibold text-indigo-700 dark:text-indigo-400 hover:underline">View all requests →</a>
      </div>

      <?php if ($latestUpdate): ?>
        <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/80 space-y-3">
          <div class="flex flex-wrap items-center justify-between gap-2">
            <span class="text-sm font-semibold text-slate-900 dark:text-white">
              #<?= $latestUpdate['request_id'] ?> · <?= e($latestUpdate['subject']) ?>
            </span>
            <span class="px-2.5 py-1 rounded-full text-xs font-semibold <?= badge_status_class($latestUpdate['status']) ?>">
              <?= e($latestUpdate['status']) ?>
            </span>
          </div>
          <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 italic bg-white dark:bg-slate-900 p-3 rounded-lg border border-slate-200 dark:border-slate-800 leading-relaxed">
            "<?= e($latestUpdate['update_note']) ?>"
          </p>
          <div class="text-[11px] text-slate-400 flex items-center justify-between">
            <span>Type: <strong class="capitalize font-medium text-slate-600 dark:text-slate-300"><?= e($latestUpdate['request_type']) ?></strong></span>
            <span>Updated: <?= format_date($latestUpdate['created_at']) ?></span>
          </div>
        </div>
      <?php else: ?>
        <div class="text-center py-8 text-slate-400 text-xs sm:text-sm">
          <p>No active requests currently. If you need support or guidance, feel free to submit a request anytime.</p>
          <a href="<?= BASE_URL ?>/user/requests.php" class="inline-block mt-3 text-indigo-700 dark:text-indigo-400 font-semibold underline">Submit appointment or referral</a>
        </div>
      <?php endif; ?>
    </div>

    <!-- Quick Check-in Banner -->
    <div class="bg-gradient-to-r from-indigo-900 to-indigo-950 text-white rounded-2xl p-6 shadow-md relative overflow-hidden">
      <div class="relative z-10 max-w-lg space-y-3">
        <span class="text-xs font-semibold tracking-wider uppercase text-amber-400">Daily Self-Check</span>
        <h3 class="font-display font-bold text-xl sm:text-2xl">How are you feeling in this moment?</h3>
        <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
          Taking 30 seconds to acknowledge your mental state builds emotional awareness. Your entries are 100% private to you.
        </p>
        <div class="pt-2">
          <a href="<?= BASE_URL ?>/user/checkins.php" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs shadow-sm transition-colors">
            <span>Log Private Check-in</span>
            <span>→</span>
          </a>
        </div>
      </div>
    </div>

  </div>

  <!-- Right Col: Educational Highlight & Recent Check-in Snippet -->
  <div class="space-y-8">
    
    <!-- Newest Educational Resource -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
      <span class="text-xs font-semibold uppercase tracking-wider text-indigo-700 dark:text-indigo-400 block mb-1">Newest Resource</span>
      <?php if ($latestResource): ?>
        <h3 class="font-display font-bold text-base text-slate-900 dark:text-white mb-2 leading-snug">
          <?= e($latestResource['title']) ?>
        </h3>
        <span class="inline-block text-[11px] font-semibold text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded mb-3">
          <?= e($latestResource['category_name']) ?>
        </span>
        <p class="text-xs text-slate-600 dark:text-slate-400 line-clamp-3 leading-relaxed mb-4">
          <?= e($latestResource['content']) ?>
        </p>
        <a href="<?= BASE_URL ?>/user/library.php" class="text-xs font-semibold text-indigo-700 dark:text-indigo-400 hover:underline">
          Read article in library →
        </a>
      <?php else: ?>
        <p class="text-xs text-slate-400">Educational articles will appear here.</p>
      <?php endif; ?>
    </div>

    <!-- Confidentiality Reminder Card -->
    <div class="bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 text-xs text-slate-600 dark:text-slate-400 space-y-2">
      <div class="font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
        <span>🔒</span> Privacy Guarantee
      </div>
      <p class="leading-relaxed">
        Your wellness check-ins and reflection logs are stored securely and restricted exclusively to your own student account. 
        School staff and administrators cannot view your personal ratings or notes.
      </p>
    </div>

  </div>

</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
