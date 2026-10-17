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

$error = null;

// Handle new check-in submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $rating = (int)($_POST['mood_rating'] ?? 0);
    $reflection = trim($_POST['reflection'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $error = 'Please select a mood rating between 1 and 5.';
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO wellness_checkins (user_id, mood_rating, reflection)
            VALUES (:uid, :rating, :refl)
        ");
        $stmt->execute([
            'uid'    => $userId,
            'rating' => $rating,
            'refl'   => !empty($reflection) ? $reflection : null,
        ]);

        flash_set('success', 'Your daily wellness check-in has been saved. Thank you for taking time for yourself!');
        header('Location: ' . BASE_URL . '/user/checkins.php');
        exit;
    }
}

// Fetch check-ins ONLY for this user (never accessible to admin)
$checkinsStmt = $pdo->prepare("
    SELECT checkin_id, mood_rating, reflection, checkin_date 
    FROM wellness_checkins 
    WHERE user_id = :uid 
    ORDER BY checkin_date DESC
");
$checkinsStmt->execute(['uid' => $userId]);
$checkins = $checkinsStmt->fetchAll();

// Prepare chronological data for Chart.js (reverse to show oldest to newest)
$chartData = array_reverse(array_slice($checkins, 0, 14));
$chartLabels = [];
$chartRatings = [];
foreach ($chartData as $c) {
    $chartLabels[] = (new DateTime($c['checkin_date']))->format('M j');
    $chartRatings[] = (int)$c['mood_rating'];
}

$pageTitle = 'Private Wellness Check-in · MindBridge';
require __DIR__ . '/../includes/header.php';
?>

<div class="mb-8 space-y-3">
  <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 text-xs font-semibold uppercase tracking-wider">
    🔒 Private Personal Journal
  </div>
  <h1 class="font-display font-bold text-3xl sm:text-4xl text-slate-900 dark:text-white">Daily Wellness Check-in</h1>
  <p class="text-sm text-slate-600 dark:text-slate-400 max-w-2xl leading-relaxed">
    Take a quiet moment to reflect on your energy and emotional state. 
    <strong>This check-in is strictly private to you</strong>—school staff and administrators cannot view your ratings or reflections.
  </p>
</div>

<!-- Main Check-in Interface -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
  
  <!-- Left Column: Check-in Form -->
  <div class="lg:col-span-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-sm space-y-6">
    
    <div>
      <h2 class="font-display font-bold text-xl text-slate-900 dark:text-white">Log Today's Mood</h2>
      <p class="text-xs text-slate-500 mt-1">Select the rating that best resonates with your feelings today.</p>
    </div>

    <?php if ($error): ?>
      <div class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-300 dark:border-rose-800 text-rose-800 dark:text-rose-200 text-xs">
        <?= e($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/user/checkins.php" class="space-y-6">
      <?= csrf_field() ?>

      <!-- 1 to 5 Mood Selector with Bouncing Radio Tiles -->
      <div>
        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-3">
          Select Your Rating (1 = Overwhelmed, 5 = Energized)
        </label>
        
        <div class="grid grid-cols-5 gap-2.5">
          <?php
          $moods = [
            1 => ['emoji' => '😔', 'title' => 'Very Low'],
            2 => ['emoji' => '😕', 'title' => 'Down'],
            3 => ['emoji' => '😐', 'title' => 'Balanced'],
            4 => ['emoji' => '🙂', 'title' => 'Good'],
            5 => ['emoji' => '✨', 'title' => 'Uplifted'],
          ];
          foreach ($moods as $val => $m):
          ?>
            <div class="mood-option">
              <input type="radio" id="mood_<?= $val ?>" name="mood_rating" value="<?= $val ?>" class="sr-only" <?= $val === 3 ? 'checked' : '' ?>>
              <label for="mood_<?= $val ?>" class="cursor-pointer flex flex-col items-center justify-center p-3 rounded-2xl border-2 border-slate-200 dark:border-slate-700 hover:border-indigo-400 text-center transition-all">
                <span class="text-2xl sm:text-3xl mb-1 select-none"><?= $m['emoji'] ?></span>
                <span class="text-[11px] font-bold text-slate-700 dark:text-slate-300"><?= $val ?></span>
                <span class="text-[9px] text-slate-400 hidden sm:block"><?= $m['title'] ?></span>
              </label>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Optional Personal Reflection Note -->
      <div>
        <label for="reflection" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
          Personal Reflection Note (Optional)
        </label>
        <textarea id="reflection" name="reflection" rows="4" placeholder="What contributed to how you felt today? Any self-care steps you took or goals for tomorrow?"
          class="w-full px-4 py-3 rounded-2xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-600 transition-colors"></textarea>
        <span class="block text-[11px] text-slate-400 mt-1">Saved exclusively to your private account journal.</span>
      </div>

      <button type="submit" class="w-full py-3.5 px-4 rounded-xl bg-indigo-800 hover:bg-indigo-900 text-white font-semibold text-sm shadow-md hover:shadow-lg transition-all focus:outline-none focus:ring-2 focus:ring-indigo-600">
        Save Daily Check-in
      </button>
    </form>

    <!-- Privacy Shield Banner -->
    <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-xs text-slate-700 dark:text-emerald-200/90 space-y-1">
      <span class="font-bold flex items-center gap-1.5 text-emerald-800 dark:text-emerald-300">
        <span>🛡️</span> Zero Staff Access Policy
      </span>
      <p class="leading-relaxed">
        Our database relationships mathematically restrict wellness check-ins to your user ID. 
        There is no administrative query, UI view, or API endpoint that exposes your check-in notes to anyone else.
      </p>
    </div>

  </div>

  <!-- Right Column: Mood Trend Chart & History Logs -->
  <div class="lg:col-span-7 space-y-6">
    
    <!-- Mood Trend Chart.js -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h2 class="font-display font-bold text-lg text-slate-900 dark:text-white">Recent Mood Journey</h2>
          <span class="text-xs text-slate-400">Up to 14 most recent logged check-ins</span>
        </div>
        <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300">
          Scale 1 to 5
        </span>
      </div>

      <?php if (empty($chartRatings)): ?>
        <div class="py-12 text-center text-xs text-slate-400">
          Log your first check-in to start generating your visual mood trend chart.
        </div>
      <?php else: ?>
        <div class="h-64 w-full">
          <canvas id="moodChart"></canvas>
        </div>
      <?php endif; ?>
    </div>

    <!-- History Logs List -->
    <div class="space-y-4">
      <div class="flex items-center justify-between pb-2 border-b border-slate-200 dark:border-slate-800">
        <h3 class="font-display font-bold text-lg text-slate-900 dark:text-white">Past Reflections</h3>
        <span class="text-xs text-slate-400"><?= count($checkins) ?> recorded entries</span>
      </div>

      <?php if (empty($checkins)): ?>
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-8 text-center text-slate-400 text-xs">
          No check-ins recorded yet. Log how you feel today using the form on the left!
        </div>
      <?php else: ?>
        <div class="space-y-3">
          <?php foreach ($checkins as $chk): ?>
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 sm:p-5 shadow-sm flex items-start gap-4">
              <span class="text-3xl p-2 rounded-2xl bg-slate-100 dark:bg-slate-800 select-none shrink-0">
                <?= mood_emoji((int)$chk['mood_rating']) ?>
              </span>
              <div class="flex-grow space-y-1">
                <div class="flex flex-wrap items-center justify-between gap-2">
                  <span class="font-display font-bold text-sm text-slate-900 dark:text-white">
                    <?= mood_label((int)$chk['mood_rating']) ?> (Rating <?= (int)$chk['mood_rating'] ?>/5)
                  </span>
                  <span class="text-[11px] text-slate-400"><?= format_date($chk['checkin_date']) ?></span>
                </div>
                <?php if (!empty($chk['reflection'])): ?>
                  <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 leading-relaxed italic">
                    "<?= e($chk['reflection']) ?>"
                  </p>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

  </div>

</div>

<!-- Chart.js Render Script -->
<?php if (!empty($chartRatings)): ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const ctx = document.getElementById('moodChart');
  if (!ctx) return;

  const isDark = document.documentElement.classList.contains('dark');
  const textColor = isDark ? '#94a3b8' : '#64748b';
  const gridColor = isDark ? 'rgba(51, 65, 85, 0.4)' : 'rgba(226, 232, 240, 0.8)';

  new Chart(ctx, {
    type: 'line',
    data: {
      labels: <?= json_encode($chartLabels) ?>,
      datasets: [{
        label: 'Mood Level (1 to 5)',
        data: <?= json_encode($chartRatings) ?>,
        borderColor: '#1f3a93',
        backgroundColor: 'rgba(31, 58, 147, 0.12)',
        pointBackgroundColor: '#e8a33d',
        pointBorderColor: '#ffffff',
        pointRadius: 5,
        pointHoverRadius: 7,
        borderWidth: 3,
        tension: 0.35,
        fill: true,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          min: 1,
          max: 5,
          ticks: {
            stepSize: 1,
            color: textColor,
            font: { family: 'Inter', size: 11 },
            callback: (value) => {
              const labels = { 1: '1 😔', 2: '2 😕', 3: '3 😐', 4: '4 🙂', 5: '5 ✨' };
              return labels[value] || value;
            }
          },
          grid: { color: gridColor }
        },
        x: {
          ticks: { color: textColor, font: { family: 'Inter', size: 11 } },
          grid: { display: false }
        }
      },
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: (item) => 'Mood Rating: ' + item.raw + '/5'
          }
        }
      }
    }
  });
});
</script>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
