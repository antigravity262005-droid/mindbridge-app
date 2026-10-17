<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';

require_admin();
$admin = current_user();
$pdo = get_db();

// Handle Status & Note Update via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    verify_csrf();

    $requestId = (int)($_POST['request_id'] ?? 0);
    $newStatus = trim($_POST['status'] ?? '');
    $updateNote = trim($_POST['update_note'] ?? '');

    $validStatuses = ['Submitted', 'In Review', 'Scheduled', 'Completed', 'Closed'];

    if ($requestId <= 0 || !in_array($newStatus, $validStatuses, true)) {
        set_flash('error', 'Invalid request or status selected.');
        redirect('/admin/requests.php');
    }

    if (empty($updateNote)) {
        set_flash('error', 'Please provide an update note explaining the status change.');
        redirect('/admin/requests.php');
    }

    try {
        $pdo->beginTransaction();

        // Update the support request status
        $stmt = $pdo->prepare("UPDATE support_requests SET status = :status WHERE request_id = :id");
        $stmt->execute(['status' => $newStatus, 'id' => $requestId]);

        // Insert audit trail / timeline update
        $stmtUpdate = $pdo->prepare("
            INSERT INTO support_request_updates (request_id, updated_by, status, update_note)
            VALUES (:request_id, :updated_by, :status, :update_note)
        ");
        $stmtUpdate->execute([
            'request_id'  => $requestId,
            'updated_by'  => $admin['user_id'],
            'status'      => $newStatus,
            'update_note' => $updateNote
        ]);

        $pdo->commit();
        set_flash('success', "Request #{$requestId} updated to '{$newStatus}' with student notification recorded.");
    } catch (PDOException $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        set_flash('error', 'Database error updating request: ' . $ex->getMessage());
    }

    redirect('/admin/requests.php');
}

// Filtering & Search
$statusFilter = trim($_GET['status'] ?? 'all');
$typeFilter   = trim($_GET['type'] ?? 'all');
$searchQuery  = trim($_GET['q'] ?? '');

$sql = "
    SELECT 
        r.request_id,
        r.user_id,
        r.service_id,
        r.request_type,
        r.subject,
        r.message,
        r.status,
        r.submitted_at,
        u.full_name AS student_name,
        u.email AS student_email,
        s.service_name
    FROM support_requests r
    JOIN users u ON r.user_id = u.user_id
    LEFT JOIN support_services s ON r.service_id = s.service_id
    WHERE 1=1
";
$params = [];

if ($statusFilter !== 'all' && in_array($statusFilter, ['Submitted', 'In Review', 'Scheduled', 'Completed', 'Closed'], true)) {
    $sql .= " AND r.status = :status";
    $params['status'] = $statusFilter;
}

if ($typeFilter !== 'all' && in_array($typeFilter, ['appointment', 'referral'], true)) {
    $sql .= " AND r.request_type = :type";
    $params['type'] = $typeFilter;
}

if (!empty($searchQuery)) {
    $sql .= " AND (u.full_name LIKE :q OR u.email LIKE :q OR r.subject LIKE :q)";
    $params['q'] = '%' . $searchQuery . '%';
}

$sql .= " ORDER BY r.submitted_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

// Fetch updates for all requests so we can render timelines in modals
$updatesRaw = $pdo->query("
    SELECT 
        up.update_id,
        up.request_id,
        up.status,
        up.update_note,
        up.created_at,
        u.full_name as updated_by_name
    FROM support_request_updates up
    JOIN users u ON up.updated_by = u.user_id
    ORDER BY up.created_at ASC
")->fetchAll();

$updatesByRequest = [];
foreach ($updatesRaw as $up) {
    $updatesByRequest[$up['request_id']][] = $up;
}

// Summary metrics
$totalCount = (int)$pdo->query("SELECT COUNT(*) FROM support_requests")->fetchColumn();
$openCount  = (int)$pdo->query("SELECT COUNT(*) FROM support_requests WHERE status IN ('Submitted', 'In Review', 'Scheduled')")->fetchColumn();
$schedCount = (int)$pdo->query("SELECT COUNT(*) FROM support_requests WHERE status = 'Scheduled'")->fetchColumn();
$doneCount  = (int)$pdo->query("SELECT COUNT(*) FROM support_requests WHERE status IN ('Completed', 'Closed')")->fetchColumn();

$pageTitle = 'Student Support Requests · Administrator · MindBridge';
require __DIR__ . '/../includes/header.php';
?>

<!-- Header -->
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-200 dark:border-slate-800">
  <div>
    <h1 class="font-display font-bold text-2xl sm:text-3xl text-slate-900 dark:text-white">
      Student Support Requests
    </h1>
    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
      Review and schedule incoming student appointments and referrals. Updates are immediately visible on the student's dashboard.
    </p>
  </div>
  
  <div class="flex items-center gap-2">
    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-slate-100 dark:bg-slate-800 text-xs font-medium text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
      <span class="live-pulse-dot"></span>
      <span>Live Updates Active</span>
    </div>
  </div>
</div>

<!-- Metric Cards -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 my-6">
  <div class="p-4 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Inquiries</span>
    <p class="font-display font-bold text-2xl text-slate-900 dark:text-white mt-1"><?= $totalCount ?></p>
  </div>
  <div class="p-4 rounded-xl bg-amber-50/50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900/40 shadow-sm">
    <span class="text-xs font-semibold text-amber-700 dark:text-amber-400 uppercase tracking-wider">Needs Action</span>
    <p class="font-display font-bold text-2xl text-amber-800 dark:text-amber-300 mt-1" id="dash-admin-open-requests"><?= $openCount ?></p>
  </div>
  <div class="p-4 rounded-xl bg-sky-50/50 dark:bg-sky-950/20 border border-sky-200 dark:border-sky-900/40 shadow-sm">
    <span class="text-xs font-semibold text-sky-700 dark:text-sky-400 uppercase tracking-wider">Scheduled</span>
    <p class="font-display font-bold text-2xl text-sky-800 dark:text-sky-300 mt-1"><?= $schedCount ?></p>
  </div>
  <div class="p-4 rounded-xl bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-900/40 shadow-sm">
    <span class="text-xs font-semibold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider">Resolved</span>
    <p class="font-display font-bold text-2xl text-emerald-800 dark:text-emerald-300 mt-1"><?= $doneCount ?></p>
  </div>
</div>

<!-- Filters Bar -->
<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 mb-6 shadow-sm">
  <form method="GET" action="<?= BASE_URL ?>/admin/requests.php" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 items-end">
    <div>
      <label for="filter-status" class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Status</label>
      <select id="filter-status" name="status" class="w-full text-xs sm:text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-600">
        <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Statuses</option>
        <option value="Submitted" <?= $statusFilter === 'Submitted' ? 'selected' : '' ?>>Submitted</option>
        <option value="In Review" <?= $statusFilter === 'In Review' ? 'selected' : '' ?>>In Review</option>
        <option value="Scheduled" <?= $statusFilter === 'Scheduled' ? 'selected' : '' ?>>Scheduled</option>
        <option value="Completed" <?= $statusFilter === 'Completed' ? 'selected' : '' ?>>Completed</option>
        <option value="Closed" <?= $statusFilter === 'Closed' ? 'selected' : '' ?>>Closed</option>
      </select>
    </div>

    <div>
      <label for="filter-type" class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Request Type</label>
      <select id="filter-type" name="type" class="w-full text-xs sm:text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-600">
        <option value="all" <?= $typeFilter === 'all' ? 'selected' : '' ?>>All Types</option>
        <option value="appointment" <?= $typeFilter === 'appointment' ? 'selected' : '' ?>>Appointment</option>
        <option value="referral" <?= $typeFilter === 'referral' ? 'selected' : '' ?>>Referral</option>
      </select>
    </div>

    <div>
      <label for="filter-search" class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Search Student / Subject</label>
      <input type="text" id="filter-search" name="q" value="<?= e($searchQuery) ?>" placeholder="e.g. Alex, counseling..." class="w-full text-xs sm:text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-600">
    </div>

    <div class="flex items-center gap-2">
      <button type="submit" class="flex-1 px-4 py-2 text-xs sm:text-sm font-semibold rounded-xl bg-indigo-800 hover:bg-indigo-900 text-white transition-colors">
        Filter
      </button>
      <?php if ($statusFilter !== 'all' || $typeFilter !== 'all' || !empty($searchQuery)): ?>
        <a href="<?= BASE_URL ?>/admin/requests.php" class="px-3 py-2 text-xs sm:text-sm font-medium rounded-xl border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
          Reset
        </a>
      <?php endif; ?>
    </div>
  </form>
</div>

<!-- Requests Table Card -->
<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
  <div class="overflow-x-auto">
    <table class="w-full text-left text-xs sm:text-sm text-slate-600 dark:text-slate-300">
      <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-700 dark:text-slate-200 font-semibold border-b border-slate-200 dark:border-slate-800">
        <tr>
          <th class="px-4 py-3">ID</th>
          <th class="px-4 py-3">Student</th>
          <th class="px-4 py-3">Type</th>
          <th class="px-4 py-3">Subject & Service</th>
          <th class="px-4 py-3">Status</th>
          <th class="px-4 py-3">Submitted</th>
          <th class="px-4 py-3 text-right">Action</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
        <?php if (empty($requests)): ?>
          <tr>
            <td colspan="7" class="px-6 py-12 text-center text-slate-500">
              <span class="text-3xl block mb-2">📋</span>
              <p class="font-medium">No support requests match your criteria.</p>
              <p class="text-xs text-slate-400 mt-1">Try resetting the filters or check back later.</p>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($requests as $r): ?>
            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors">
              <td class="px-4 py-3.5 font-mono text-xs text-slate-400">
                #<?= $r['request_id'] ?>
              </td>
              <td class="px-4 py-3.5">
                <span class="block font-semibold text-slate-900 dark:text-white"><?= e($r['student_name']) ?></span>
                <span class="block text-[11px] text-slate-400"><?= e($r['student_email']) ?></span>
              </td>
              <td class="px-4 py-3.5">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold <?= $r['request_type'] === 'appointment' ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/50 dark:text-indigo-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300' ?>">
                  <?= ucfirst($r['request_type']) ?>
                </span>
              </td>
              <td class="px-4 py-3.5 max-w-xs">
                <span class="block font-medium text-slate-800 dark:text-slate-200 truncate" title="<?= e($r['subject']) ?>">
                  <?= e($r['subject']) ?>
                </span>
                <?php if ($r['service_name']): ?>
                  <span class="inline-flex items-center gap-1 text-[11px] text-slate-500 dark:text-slate-400">
                    <span>🏢</span> <?= e($r['service_name']) ?>
                  </span>
                <?php else: ?>
                  <span class="text-[11px] text-slate-400 italic">General referral</span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3.5">
                <?= status_badge($r['status']) ?>
              </td>
              <td class="px-4 py-3.5 text-xs text-slate-500 whitespace-nowrap">
                <?= date('M d, Y', strtotime($r['submitted_at'])) ?><br>
                <span class="text-[11px] text-slate-400"><?= date('h:i A', strtotime($r['submitted_at'])) ?></span>
              </td>
              <td class="px-4 py-3.5 text-right">
                <button type="button" 
                        onclick="openManageModal(<?= htmlspecialchars(json_encode([
                            'id'           => $r['request_id'],
                            'student_name' => $r['student_name'],
                            'student_email'=> $r['student_email'],
                            'type'         => $r['request_type'],
                            'subject'      => $r['subject'],
                            'message'      => $r['message'] ?? 'No additional details provided.',
                            'service'      => $r['service_name'] ?? 'Not specified',
                            'status'       => $r['status'],
                            'submitted_at' => date('M d, Y h:i A', strtotime($r['submitted_at'])),
                            'updates'      => $updatesByRequest[$r['request_id']] ?? []
                        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>)"
                        class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-indigo-800 hover:bg-indigo-900 text-white shadow-sm transition-colors">
                  Review & Respond
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ========================================================
     MANAGE REQUEST MODAL
     ======================================================== -->
<div id="manage-modal" class="fixed inset-0 z-50 hidden bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
  <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-2xl w-full p-6 shadow-2xl my-8">
    <div class="flex items-start justify-between pb-4 border-b border-slate-200 dark:border-slate-800">
      <div>
        <div class="flex items-center gap-2">
          <span class="font-display font-bold text-xl text-slate-900 dark:text-white" id="modal-req-title">Request Details</span>
          <span id="modal-req-status-badge"></span>
        </div>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1" id="modal-req-meta"></p>
      </div>
      <button type="button" onclick="closeManageModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <!-- Student details & message -->
    <div class="my-4 p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700">
      <div class="grid grid-cols-2 gap-2 text-xs mb-3">
        <div>
          <span class="text-slate-400">Student:</span>
          <strong class="block text-slate-800 dark:text-slate-200 font-semibold" id="modal-req-student"></strong>
        </div>
        <div>
          <span class="text-slate-400">Requested Support Service:</span>
          <strong class="block text-slate-800 dark:text-slate-200 font-semibold" id="modal-req-service"></strong>
        </div>
      </div>
      <div class="text-xs">
        <span class="text-slate-400">Student Message / Non-Clinical Summary:</span>
        <div class="mt-1 p-3 bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 whitespace-pre-wrap leading-relaxed" id="modal-req-message"></div>
      </div>
    </div>

    <!-- Timeline of previous updates -->
    <div class="mb-5">
      <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">History & Follow-up Timeline</h4>
      <div id="modal-req-timeline" class="space-y-2 max-h-48 overflow-y-auto pr-1"></div>
    </div>

    <!-- Update Form -->
    <form method="POST" action="<?= BASE_URL ?>/admin/requests.php" class="pt-4 border-t border-slate-200 dark:border-slate-800">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="update_status">
      <input type="hidden" name="request_id" id="modal-form-request-id" value="">

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
        <div>
          <label for="form-new-status" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
            New Status <span class="text-rose-500">*</span>
          </label>
          <select id="form-new-status" name="status" required class="w-full text-xs sm:text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-600">
            <option value="Submitted">Submitted (Pending)</option>
            <option value="In Review">In Review</option>
            <option value="Scheduled">Scheduled</option>
            <option value="Completed">Completed</option>
            <option value="Closed">Closed</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
            Updating Counselor / Admin
          </label>
          <input type="text" disabled value="<?= e($admin['full_name']) ?>" class="w-full text-xs sm:text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 px-3 py-2 text-slate-500">
        </div>
      </div>

      <div class="mb-4">
        <label for="form-update-note" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
          Follow-up Note & Student Message <span class="text-rose-500">*</span>
        </label>
        <textarea id="form-update-note" name="update_note" rows="3" required placeholder="e.g. Appointment scheduled with Counselor Ramos for Tuesday 10:00 AM at Student Services Bldg Rm 204." class="w-full text-xs sm:text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-600"></textarea>
        <p class="text-[11px] text-slate-400 mt-1">This note will appear immediately on the student's dashboard timeline.</p>
      </div>

      <div class="flex items-center justify-end gap-3">
        <button type="button" onclick="closeManageModal()" class="px-4 py-2 text-xs sm:text-sm font-medium rounded-xl text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
          Cancel
        </button>
        <button type="submit" class="px-4 py-2 text-xs sm:text-sm font-semibold rounded-xl bg-indigo-800 hover:bg-indigo-900 text-white shadow-sm transition-colors">
          Save Status & Send Note
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function openManageModal(data) {
  document.getElementById('modal-form-request-id').value = data.id;
  document.getElementById('modal-req-title').textContent = 'Request #' + data.id + ': ' + data.subject;
  document.getElementById('modal-req-meta').textContent = 'Submitted on ' + data.submitted_at + ' · Type: ' + data.type;
  document.getElementById('modal-req-student').textContent = data.student_name + ' (' + data.student_email + ')';
  document.getElementById('modal-req-service').textContent = data.service;
  document.getElementById('modal-req-message').textContent = data.message;
  document.getElementById('form-new-status').value = data.status;

  // Timeline list
  const tl = document.getElementById('modal-req-timeline');
  tl.innerHTML = '';
  if (!data.updates || data.updates.length === 0) {
    tl.innerHTML = '<p class="text-xs text-slate-400 italic">No previous updates recorded yet.</p>';
  } else {
    data.updates.forEach(u => {
      const item = document.createElement('div');
      item.className = 'p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs';
      item.innerHTML = `
        <div class="flex items-center justify-between font-medium text-slate-800 dark:text-slate-200 mb-1">
          <span>Status changed to <strong>${u.status}</strong> by ${u.updated_by_name}</span>
          <span class="text-[11px] text-slate-400">${u.created_at}</span>
        </div>
        <p class="text-slate-600 dark:text-slate-300 text-[11px]">${u.update_note}</p>
      `;
      tl.appendChild(item);
    });
  }

  document.getElementById('manage-modal').classList.remove('hidden');
}

function closeManageModal() {
  document.getElementById('manage-modal').classList.add('hidden');
}
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
