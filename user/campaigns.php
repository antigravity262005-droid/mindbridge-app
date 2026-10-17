<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';

require_login();
$user = current_user();
$userId = (int)$user['user_id'];
$pdo = get_db();

// Handle Join / Leave Campaign actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $action = $_POST['action'] ?? '';
    $campaignId = (int)($_POST['campaign_id'] ?? 0);

    if ($campaignId > 0) {
        if ($action === 'join') {
            $stmt = $pdo->prepare("INSERT IGNORE INTO campaign_participants (campaign_id, user_id) VALUES (:cid, :uid)");
            $stmt->execute(['cid' => $campaignId, 'uid' => $userId]);
            flash_set('success', 'You have joined this awareness campaign! Thank you for supporting campus mental wellness.');
        } elseif ($action === 'leave') {
            $stmt = $pdo->prepare("DELETE FROM campaign_participants WHERE campaign_id = :cid AND user_id = :uid");
            $stmt->execute(['cid' => $campaignId, 'uid' => $userId]);
            flash_set('info', 'You have left the campaign.');
        }
    }

    header('Location: ' . BASE_URL . '/user/campaigns.php');
    exit;
}

// Fetch campaigns with participant counts and user membership status
$campaignsStmt = $pdo->prepare("
    SELECT c.campaign_id, c.title, c.description, c.start_date, c.end_date, c.created_at,
           COUNT(p.user_id) AS total_participants,
           MAX(CASE WHEN p.user_id = :uid THEN 1 ELSE 0 END) AS is_joined
    FROM awareness_campaigns c
    LEFT JOIN campaign_participants p ON c.campaign_id = p.campaign_id
    GROUP BY c.campaign_id
    ORDER BY c.start_date ASC
");
$campaignsStmt->execute(['uid' => $userId]);
$campaigns = $campaignsStmt->fetchAll();

$pageTitle = 'Campus Awareness Campaigns · MindBridge';
require __DIR__ . '/../includes/header.php';
?>

<div class="mb-8 space-y-3">
  <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-50 dark:bg-amber-950 text-amber-800 dark:text-amber-300 text-xs font-semibold uppercase tracking-wider">
    🤝 Community & Peer Engagement
  </div>
  <h1 class="font-display font-bold text-3xl sm:text-4xl text-slate-900 dark:text-white">Awareness Campaigns</h1>
  <p class="text-sm text-slate-600 dark:text-slate-400 max-w-2xl leading-relaxed">
    Participate in campus-wide initiatives aimed at breaking mental health stigma, encouraging healthy communication, 
    and building supportive student networks.
  </p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-8">
  <?php foreach ($campaigns as $camp): ?>
    <?php
    $isJoined = (bool)$camp['is_joined'];
    $startDate = new DateTime($camp['start_date']);
    $endDate = new DateTime($camp['end_date']);
    $today = new DateTime();
    $isActive = ($today >= $startDate && $today <= $endDate);
    ?>
    <article class="interactive-card bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-sm flex flex-col justify-between">
      
      <div>
        <div class="flex items-center justify-between gap-2 mb-3">
          <span class="px-3 py-1 rounded-full text-xs font-semibold <?= $isActive ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300/40' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' ?>">
            <?= $isActive ? 'Active Now' : 'Scheduled' ?>
          </span>
          <span class="text-xs text-slate-500 flex items-center gap-1 font-medium">
            <span>👥</span> <strong class="text-slate-900 dark:text-white"><?= (int)$camp['total_participants'] ?></strong> participants
          </span>
        </div>

        <h2 class="font-display font-bold text-2xl text-slate-900 dark:text-white mb-3 leading-snug">
          <?= e($camp['title']) ?>
        </h2>

        <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 leading-relaxed mb-6">
          <?= e($camp['description']) ?>
        </p>
      </div>

      <div class="pt-6 border-t border-slate-100 dark:border-slate-800 space-y-4">
        
        <!-- Date Timeline -->
        <div class="flex items-center justify-between text-xs text-slate-500">
          <span>Timeline:</span>
          <span class="font-semibold text-slate-700 dark:text-slate-300">
            <?= $startDate->format('M j, Y') ?> – <?= $endDate->format('M j, Y') ?>
          </span>
        </div>

        <!-- Join / Leave Form Action -->
        <form method="POST" action="<?= BASE_URL ?>/user/campaigns.php">
          <?= csrf_field() ?>
          <input type="hidden" name="campaign_id" value="<?= $camp['campaign_id'] ?>">

          <?php if ($isJoined): ?>
            <div class="flex items-center justify-between gap-3">
              <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                <span>✓</span> You are participating
              </span>
              <button type="submit" name="action" value="leave"
                class="px-4 py-2 rounded-xl border border-rose-300 dark:border-rose-800 text-rose-700 dark:text-rose-300 text-xs font-semibold hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors">
                Leave Campaign
              </button>
            </div>
          <?php else: ?>
            <button type="submit" name="action" value="join"
              class="w-full py-3 rounded-xl bg-indigo-800 hover:bg-indigo-900 text-white text-xs font-bold shadow-sm transition-colors flex items-center justify-center gap-2">
              <span>Join Campaign</span>
              <span>🤝</span>
            </button>
          <?php endif; ?>
        </form>

      </div>

    </article>
  <?php endforeach; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
