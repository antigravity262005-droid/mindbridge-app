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

// Handle form submission
$errors = [];
$preselectedServiceId = isset($_GET['service_id']) ? (int)$_GET['service_id'] : 0;
$preselectedType = in_array($_GET['type'] ?? '', ['appointment', 'referral'], true) ? $_GET['type'] : 'appointment';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $requestType = $_POST['request_type'] ?? '';
    $serviceId = !empty($_POST['service_id']) ? (int)$_POST['service_id'] : null;
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!in_array($requestType, ['appointment', 'referral'], true)) {
        $errors[] = 'Please select a valid request type (Appointment or Referral).';
    }

    if (empty($subject) || mb_strlen($subject) < 3) {
        $errors[] = 'Please provide a brief subject for your request (minimum 3 characters).';
    } elseif (mb_strlen($subject) > 200) {
        $errors[] = 'Subject must not exceed 200 characters.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO support_requests (user_id, service_id, request_type, subject, message, status)
            VALUES (:uid, :sid, :rtype, :subj, :msg, 'Submitted')
        ");
        $stmt->execute([
            'uid'   => $userId,
            'sid'   => $serviceId,
            'rtype' => $requestType,
            'subj'  => $subject,
            'msg'   => !empty($message) ? $message : null,
        ]);

        flash_set('success', 'Your support request has been submitted. The guidance administrator will follow up with you.');
        header('Location: ' . BASE_URL . '/user/requests.php');
        exit;
    }
}

// Fetch active services for the dropdown
$services = $pdo->query("SELECT service_id, service_name, service_type FROM support_services WHERE is_active = 1 ORDER BY service_name ASC")->fetchAll();

// Fetch student's requests with updates
$myRequestsStmt = $pdo->prepare("
    SELECT r.request_id, r.request_type, r.subject, r.message, r.status, r.submitted_at, s.service_name
    FROM support_requests r
    LEFT JOIN support_services s ON r.service_id = s.service_id
    WHERE r.user_id = :uid
    ORDER BY r.submitted_at DESC
");
$myRequestsStmt->execute(['uid' => $userId]);
$myRequests = $myRequestsStmt->fetchAll();

// Fetch all updates for student's requests to display timelines
$updatesStmt = $pdo->prepare("
    SELECT u.update_id, u.request_id, u.status, u.update_note, u.created_at, admin.full_name as updated_by_name
    FROM support_request_updates u
    JOIN support_requests r ON u.request_id = r.request_id
    JOIN users admin ON u.updated_by = admin.user_id
    WHERE r.user_id = :uid
    ORDER BY u.created_at ASC
");
$updatesStmt->execute(['uid' => $userId]);
$allUpdates = $updatesStmt->fetchAll();

// Group updates by request_id
$updatesByRequest = [];
foreach ($allUpdates as $up) {
    $updatesByRequest[$up['request_id']][] = $up;
}

$pageTitle = 'My Support Requests & Appointments · MindBridge';
require __DIR__ . '/../includes/header.php';
?>

<div class="mb-8 space-y-3">
  <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-50 dark:bg-indigo-950 text-indigo-800 dark:text-indigo-300 text-xs font-semibold uppercase tracking-wider">
    📬 Support & Referral Inquiries
  </div>
  <h1 class="font-display font-bold text-3xl sm:text-4xl text-slate-900 dark:text-white">My Support Requests</h1>
  <p class="text-sm text-slate-600 dark:text-slate-400 max-w-2xl leading-relaxed">
    Submit a private consultation request or referral inquiry to school counselors. 
    You can track administrative updates and scheduled times right here.
  </p>
</div>

<!-- Grid Layout: Request Form + Requests List -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
  
  <!-- Left Column: Submit New Request Form -->
  <div class="lg:col-span-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-sm">
    
    <div class="mb-6">
      <h2 class="font-display font-bold text-xl text-slate-900 dark:text-white">Submit New Request</h2>
      <p class="text-xs text-slate-500 mt-1">Our guidance staff will follow up on your request shortly.</p>
    </div>

    <!-- Explicit Medical Privacy Warning -->
    <div class="mb-6 p-4 rounded-xl bg-amber-500/10 border border-amber-500/25 text-xs text-slate-800 dark:text-amber-200/90 space-y-1.5 leading-relaxed">
      <span class="font-bold flex items-center gap-1.5 text-amber-800 dark:text-amber-400">
        <span>⚠️</span> Non-Emergency & Privacy Reminder
      </span>
      <p>
        Requests are reviewed during regular campus office hours. <strong>Do not submit unnecessary sensitive medical details.</strong> 
        In immediate danger or emergency, contact <strong>911</strong> or the <strong>NCMH Hotline at 1553</strong>.
      </p>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="mb-6 p-4 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-300 dark:border-rose-800 text-rose-800 dark:text-rose-200 text-xs">
        <ul class="list-disc list-inside space-y-1">
          <?php foreach ($errors as $err): ?>
            <li><?= e($err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/user/requests.php" class="space-y-4">
      <?= csrf_field() ?>

      <div>
        <label for="request_type" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Request Type</label>
        <select id="request_type" name="request_type" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-600">
          <option value="appointment" <?= $preselectedType === 'appointment' ? 'selected' : '' ?>>Appointment (One-on-one session)</option>
          <option value="referral" <?= $preselectedType === 'referral' ? 'selected' : '' ?>>Referral (Connection to partner service)</option>
        </select>
      </div>

      <div>
        <label for="service_id" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Target Service (Optional)</label>
        <select id="service_id" name="service_id" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-600">
          <option value="">General Guidance / Any Available Service</option>
          <?php foreach ($services as $srv): ?>
            <option value="<?= $srv['service_id'] ?>" <?= $preselectedServiceId === (int)$srv['service_id'] ? 'selected' : '' ?>>
              <?= e($srv['service_name']) ?> (<?= e($srv['service_type']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label for="subject" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Subject / Reason</label>
        <input type="text" id="subject" name="subject" required placeholder="e.g. Managing midterms anxiety & study burnout"
          class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-600">
      </div>

      <div>
        <label for="message" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Optional Message / Context</label>
        <textarea id="message" name="message" rows="3" placeholder="Share brief context or preferred meeting days/times..."
          class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-600"></textarea>
      </div>

      <button type="submit" class="w-full py-3.5 px-4 rounded-xl bg-indigo-800 hover:bg-indigo-900 text-white font-semibold text-sm shadow-md hover:shadow-lg transition-all focus:outline-none focus:ring-2 focus:ring-indigo-600">
        Submit Request
      </button>
    </form>

  </div>

  <!-- Right Column: List of Submitted Requests with Live Updates -->
  <div class="lg:col-span-7 space-y-6">
    <div class="flex items-center justify-between pb-2 border-b border-slate-200 dark:border-slate-800">
      <h2 class="font-display font-bold text-xl text-slate-900 dark:text-white">Request History & Timeline</h2>
      <span class="text-xs text-slate-400 font-medium"><?= count($myRequests) ?> total records</span>
    </div>

    <?php if (empty($myRequests)): ?>
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-10 text-center text-slate-400 space-y-2">
        <span class="text-3xl block">📋</span>
        <p class="font-semibold text-slate-700 dark:text-slate-300 text-sm">No requests submitted yet</p>
        <p class="text-xs">When you submit an appointment or referral request, its status and follow-up updates will appear here.</p>
      </div>
    <?php else: ?>
      <div class="space-y-6" id="user-requests-container">
        <?php foreach ($myRequests as $req): ?>
          <article class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm space-y-4" data-request-id="<?= $req['request_id'] ?>">
            
            <!-- Request Header -->
            <div class="flex flex-wrap items-start justify-between gap-3">
              <div>
                <span class="text-xs font-mono text-slate-400 block mb-0.5">Request #<?= $req['request_id'] ?> · <?= format_date($req['submitted_at']) ?></span>
                <h3 class="font-display font-bold text-lg text-slate-900 dark:text-white leading-snug">
                  <?= e($req['subject']) ?>
                </h3>
              </div>
              <span class="px-3 py-1 rounded-full text-xs font-semibold <?= badge_status_class($req['status']) ?>">
                <?= e($req['status']) ?>
              </span>
            </div>

            <!-- Details -->
            <div class="grid grid-cols-2 gap-2 text-xs text-slate-500 pt-2 border-t border-slate-100 dark:border-slate-800">
              <div>
                <span class="block text-slate-400">Type:</span>
                <span class="font-semibold capitalize text-slate-700 dark:text-slate-300"><?= e($req['request_type']) ?></span>
              </div>
              <div>
                <span class="block text-slate-400">Selected Service:</span>
                <span class="font-semibold text-slate-700 dark:text-slate-300"><?= e($req['service_name'] ?? 'General School Guidance') ?></span>
              </div>
            </div>

            <?php if (!empty($req['message'])): ?>
              <p class="text-xs text-slate-600 dark:text-slate-400 bg-slate-50 dark:bg-slate-800/60 p-3 rounded-xl border border-slate-100 dark:border-slate-800 leading-relaxed">
                <?= e($req['message']) ?>
              </p>
            <?php endif; ?>

            <!-- Follow-up Updates Timeline -->
            <?php $updates = $updatesByRequest[$req['request_id']] ?? []; ?>
            <div class="pt-3 border-t border-slate-100 dark:border-slate-800">
              <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 block mb-3">Administrator Notes & Progress:</span>
              
              <?php if (empty($updates)): ?>
                <p class="text-xs text-slate-400 italic">No notes recorded yet. Your request is queued for review by the guidance administrator.</p>
              <?php else: ?>
                <div class="space-y-3 relative pl-4 border-l-2 border-indigo-200 dark:border-indigo-900">
                  <?php foreach ($updates as $up): ?>
                    <div class="relative">
                      <span class="absolute -left-[21px] top-1.5 w-2.5 h-2.5 rounded-full bg-indigo-600"></span>
                      <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-xs space-y-1">
                        <div class="flex items-center justify-between gap-2">
                          <span class="font-bold text-slate-900 dark:text-white">Status updated to: <span class="<?= badge_status_class($up['status']) ?> px-2 py-0.5 rounded text-[10px]"><?= e($up['status']) ?></span></span>
                          <span class="text-[10px] text-slate-400"><?= format_date($up['created_at']) ?></span>
                        </div>
                        <p class="text-slate-700 dark:text-slate-300 leading-relaxed italic">"<?= e($up['update_note']) ?>"</p>
                        <span class="block text-[10px] text-slate-400 text-right">By <?= e($up['updated_by_name']) ?></span>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>

          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
