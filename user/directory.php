<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

require_login();
$pdo = get_db();

// Fetch only active support services
$services = $pdo->query("
    SELECT service_id, service_name, service_type, description, contact_info, created_at 
    FROM support_services 
    WHERE is_active = 1 
    ORDER BY service_id ASC
")->fetchAll();

$pageTitle = 'Verified Support Services Directory · MindBridge';
require __DIR__ . '/../includes/header.php';
?>

<div class="mb-8 space-y-3">
  <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 text-xs font-semibold uppercase tracking-wider">
    🛡️ Verified Care Directory
  </div>
  <h1 class="font-display font-bold text-3xl sm:text-4xl text-slate-900 dark:text-white">Support Services Directory</h1>
  <p class="text-sm text-slate-600 dark:text-slate-400 max-w-2xl leading-relaxed">
    Connect with school-approved counseling offices, student peer wellness networks, and verified community partners. 
    Select any campus service below to request an appointment.
  </p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
  <?php foreach ($services as $srv): ?>
    <div class="interactive-card bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm flex flex-col justify-between">
      
      <div>
        <div class="flex items-center justify-between gap-2 mb-3">
          <span class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-indigo-50 dark:bg-indigo-950 text-indigo-800 dark:text-indigo-300 border border-indigo-100 dark:border-indigo-900/50">
            <?= e($srv['service_type']) ?>
          </span>
          <span class="inline-flex items-center gap-1.5 text-xs text-emerald-600 dark:text-emerald-400 font-medium">
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Verified Listing
          </span>
        </div>

        <h2 class="font-display font-bold text-xl text-slate-900 dark:text-white mb-2 leading-snug">
          <?= e($srv['service_name']) ?>
        </h2>

        <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 leading-relaxed mb-4">
          <?= e($srv['description']) ?>
        </p>
      </div>

      <div class="space-y-4 pt-4 border-t border-slate-100 dark:border-slate-800">
        
        <!-- Contact Details Box -->
        <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/80 text-xs text-slate-700 dark:text-slate-300 space-y-1">
          <span class="font-semibold text-slate-900 dark:text-white block text-[11px] uppercase tracking-wider">Contact & Location:</span>
          <p class="font-mono text-[11px] leading-relaxed break-words"><?= e($srv['contact_info']) ?></p>
        </div>

        <!-- Action Button -->
        <div class="flex items-center justify-between gap-3">
          <?php if (str_contains(strtolower($srv['service_type']), 'crisis') || str_contains(strtolower($srv['service_name']), 'hotline')): ?>
            <span class="text-[11px] text-amber-700 dark:text-amber-400 font-medium">External hotline — call directly anytime</span>
            <a href="tel:1553" class="px-4 py-2 text-xs font-bold rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 shadow-sm transition-colors">
              Call Hotline
            </a>
          <?php else: ?>
            <span class="text-[11px] text-slate-400">School guidance service</span>
            <a href="<?= BASE_URL ?>/user/requests.php?service_id=<?= (int)$srv['service_id'] ?>&type=appointment"
              class="px-4 py-2 text-xs font-bold rounded-xl bg-indigo-800 hover:bg-indigo-900 text-white shadow-sm transition-colors flex items-center gap-1.5">
              <span>Request Appointment</span>
              <span>→</span>
            </a>
          <?php endif; ?>
        </div>

      </div>

    </div>
  <?php endforeach; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
