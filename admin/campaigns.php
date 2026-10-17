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

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = trim($_POST['action'] ?? '');

    // 1. Create Campaign
    if ($action === 'create_campaign') {
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $startDate   = trim($_POST['start_date'] ?? '');
        $endDate     = trim($_POST['end_date'] ?? '');

        if (empty($title) || empty($description) || empty($startDate) || empty($endDate)) {
            set_flash('error', 'All fields are required.');
        } elseif ($endDate < $startDate) {
            set_flash('error', 'End date must be on or after the start date.');
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO awareness_campaigns (title, description, start_date, end_date, created_by)
                    VALUES (:title, :desc, :start, :end, :user)
                ");
                $stmt->execute([
                    'title' => $title,
                    'desc'  => $description,
                    'start' => $startDate,
                    'end'   => $endDate,
                    'user'  => $admin['user_id']
                ]);
                set_flash('success', "Campaign '{$title}' created successfully.");
            } catch (PDOException $ex) {
                set_flash('error', 'Error creating campaign: ' . $ex->getMessage());
            }
        }
        redirect('/admin/campaigns.php');
    }

    // 2. Update Campaign
    if ($action === 'update_campaign') {
        $campaignId  = (int)($_POST['campaign_id'] ?? 0);
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $startDate   = trim($_POST['start_date'] ?? '');
        $endDate     = trim($_POST['end_date'] ?? '');

        if ($campaignId <= 0 || empty($title) || empty($description) || empty($startDate) || empty($endDate)) {
            set_flash('error', 'All fields are required.');
        } elseif ($endDate < $startDate) {
            set_flash('error', 'End date must be on or after the start date.');
        } else {
            try {
                $stmt = $pdo->prepare("
                    UPDATE awareness_campaigns
                    SET title = :title, description = :desc, start_date = :start, end_date = :end
                    WHERE campaign_id = :id
                ");
                $stmt->execute([
                    'title' => $title,
                    'desc'  => $description,
                    'start' => $startDate,
                    'end'   => $endDate,
                    'id'    => $campaignId
                ]);
                set_flash('success', "Campaign '{$title}' updated successfully.");
            } catch (PDOException $ex) {
                set_flash('error', 'Error updating campaign: ' . $ex->getMessage());
            }
        }
        redirect('/admin/campaigns.php');
    }

    // 3. Delete Campaign
    if ($action === 'delete_campaign') {
        $campaignId = (int)($_POST['campaign_id'] ?? 0);
        if ($campaignId > 0) {
            $stmt = $pdo->prepare("DELETE FROM awareness_campaigns WHERE campaign_id = :id");
            $stmt->execute(['id' => $campaignId]);
            set_flash('success', 'Campaign was removed.');
        }
        redirect('/admin/campaigns.php');
    }
}

// Fetch campaigns with participant counts
$campaigns = $pdo->query("
    SELECT c.*, u.full_name as author_name,
           (SELECT COUNT(*) FROM campaign_participants cp WHERE cp.campaign_id = c.campaign_id) as participant_count
    FROM awareness_campaigns c
    JOIN users u ON c.created_by = u.user_id
    ORDER BY c.start_date DESC
")->fetchAll();

// Fetch participant list grouped by campaign
$participantsRaw = $pdo->query("
    SELECT cp.campaign_id, cp.joined_at, u.full_name, u.email
    FROM campaign_participants cp
    JOIN users u ON cp.user_id = u.user_id
    ORDER BY cp.joined_at DESC
")->fetchAll();

$participantsByCampaign = [];
foreach ($participantsRaw as $p) {
    $participantsByCampaign[$p['campaign_id']][] = $p;
}

$today = date('Y-m-d');
$pageTitle = 'Awareness Campaigns Management · Administrator · MindBridge';
require __DIR__ . '/../includes/header.php';
?>

<!-- Header -->
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-200 dark:border-slate-800">
  <div>
    <h1 class="font-display font-bold text-2xl sm:text-3xl text-slate-900 dark:text-white">
      Awareness Campaigns Management
    </h1>
    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
      Organize campus-wide mental health advocacy events, wellness pledge drives, and stigma-reduction workshops.
    </p>
  </div>
  
  <button type="button" onclick="openAddCampaignModal()" class="px-4 py-2 text-xs sm:text-sm font-semibold rounded-xl bg-indigo-800 hover:bg-indigo-900 text-white shadow-sm transition-colors">
    + Launch New Campaign
  </button>
</div>

<!-- Campaigns Table / Cards -->
<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm my-8">
  <div class="overflow-x-auto">
    <table class="w-full text-left text-xs sm:text-sm text-slate-600 dark:text-slate-300">
      <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-700 dark:text-slate-200 font-semibold border-b border-slate-200 dark:border-slate-800">
        <tr>
          <th class="px-4 py-3">Campaign</th>
          <th class="px-4 py-3">Schedule</th>
          <th class="px-4 py-3">Status</th>
          <th class="px-4 py-3 text-center">Participants</th>
          <th class="px-4 py-3 text-right">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
        <?php if (empty($campaigns)): ?>
          <tr>
            <td colspan="5" class="px-6 py-12 text-center text-slate-500">
              <span class="text-3xl block mb-2">📢</span>
              <p class="font-medium">No awareness campaigns scheduled yet.</p>
              <p class="text-xs text-slate-400 mt-1">Click "+ Launch New Campaign" to post an event.</p>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($campaigns as $camp): ?>
            <?php
              $isUpcoming = $camp['start_date'] > $today;
              $isPast = $camp['end_date'] < $today;
              $isActive = !$isUpcoming && !$isPast;
            ?>
            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors">
              <td class="px-4 py-3.5 max-w-sm">
                <span class="block font-semibold text-slate-900 dark:text-white"><?= e($camp['title']) ?></span>
                <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2 mt-0.5"><?= e($camp['description']) ?></p>
              </td>
              <td class="px-4 py-3.5 whitespace-nowrap text-xs text-slate-600 dark:text-slate-300">
                <span><?= date('M d, Y', strtotime($camp['start_date'])) ?></span>
                <span class="text-slate-400"> → </span>
                <span><?= date('M d, Y', strtotime($camp['end_date'])) ?></span>
              </td>
              <td class="px-4 py-3.5 whitespace-nowrap">
                <?php if ($isActive): ?>
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Active Now
                  </span>
                <?php elseif ($isUpcoming): ?>
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-sky-100 text-sky-800 dark:bg-sky-950/60 dark:text-sky-300">
                    Upcoming
                  </span>
                <?php else: ?>
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                    Concluded
                  </span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3.5 text-center">
                <button type="button" 
                        onclick="openParticipantsModal(<?= htmlspecialchars(json_encode([
                            'title' => $camp['title'],
                            'list'  => $participantsByCampaign[$camp['campaign_id']] ?? []
                        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>)"
                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-indigo-50 dark:bg-slate-800 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 font-semibold text-xs transition-colors">
                  <span>👥</span> <?= (int)$camp['participant_count'] ?> Students
                </button>
              </td>
              <td class="px-4 py-3.5 text-right whitespace-nowrap">
                <button type="button" 
                        onclick="openEditCampaignModal(<?= htmlspecialchars(json_encode([
                            'id'    => $camp['campaign_id'],
                            'title' => $camp['title'],
                            'desc'  => $camp['description'],
                            'start' => $camp['start_date'],
                            'end'   => $camp['end_date']
                        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>)"
                        class="px-2.5 py-1 text-xs font-medium rounded-lg text-indigo-700 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-slate-800 transition-colors">
                  Edit
                </button>
                <form method="POST" action="<?= BASE_URL ?>/admin/campaigns.php" class="inline" onsubmit="return confirm('Delete this campaign? Enrolled participants will also be unlinked.');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete_campaign">
                  <input type="hidden" name="campaign_id" value="<?= $camp['campaign_id'] ?>">
                  <button type="submit" class="px-2.5 py-1 text-xs font-medium rounded-lg text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition-colors">
                    Delete
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ========================================================
     MODALS: ADD CAMPAIGN / EDIT CAMPAIGN / PARTICIPANTS LIST
     ======================================================== -->

<!-- ADD CAMPAIGN MODAL -->
<div id="add-campaign-modal" class="fixed inset-0 z-50 hidden bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
  <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-lg w-full p-6 shadow-2xl my-8">
    <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-slate-800">
      <h3 class="font-display font-bold text-xl text-slate-900 dark:text-white">Launch Awareness Campaign</h3>
      <button type="button" onclick="closeAddCampaignModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <form method="POST" action="<?= BASE_URL ?>/admin/campaigns.php" class="mt-4 space-y-4">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="create_campaign">

      <div>
        <label for="create-camp-title" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
          Campaign Title <span class="text-rose-500">*</span>
        </label>
        <input type="text" id="create-camp-title" name="title" required placeholder="e.g. World Mental Health Week: You Are Not Alone" class="w-full text-xs sm:text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-600">
      </div>

      <div>
        <label for="create-camp-desc" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
          Theme & Description <span class="text-rose-500">*</span>
        </label>
        <textarea id="create-camp-desc" name="description" rows="4" required placeholder="Explain the advocacy goal, scheduled activities, and invitation for students to pledge support..." class="w-full text-xs sm:text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-600"></textarea>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label for="create-camp-start" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
            Start Date <span class="text-rose-500">*</span>
          </label>
          <input type="date" id="create-camp-start" name="start_date" required class="w-full text-xs sm:text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-600">
        </div>
        <div>
          <label for="create-camp-end" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
            End Date <span class="text-rose-500">*</span>
          </label>
          <input type="date" id="create-camp-end" name="end_date" required class="w-full text-xs sm:text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-600">
        </div>
      </div>

      <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200 dark:border-slate-800">
        <button type="button" onclick="closeAddCampaignModal()" class="px-4 py-2 text-xs sm:text-sm font-medium rounded-xl text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800">Cancel</button>
        <button type="submit" class="px-4 py-2 text-xs sm:text-sm font-semibold rounded-xl bg-indigo-800 hover:bg-indigo-900 text-white shadow-sm">Schedule Campaign</button>
      </div>
    </form>
  </div>
</div>

<!-- EDIT CAMPAIGN MODAL -->
<div id="edit-campaign-modal" class="fixed inset-0 z-50 hidden bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
  <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-lg w-full p-6 shadow-2xl my-8">
    <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-slate-800">
      <h3 class="font-display font-bold text-xl text-slate-900 dark:text-white">Edit Campaign</h3>
      <button type="button" onclick="closeEditCampaignModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <form method="POST" action="<?= BASE_URL ?>/admin/campaigns.php" class="mt-4 space-y-4">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="update_campaign">
      <input type="hidden" name="campaign_id" id="edit-camp-id" value="">

      <div>
        <label for="edit-camp-title" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Campaign Title <span class="text-rose-500">*</span></label>
        <input type="text" id="edit-camp-title" name="title" required class="w-full text-xs sm:text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-600">
      </div>

      <div>
        <label for="edit-camp-desc" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Theme & Description <span class="text-rose-500">*</span></label>
        <textarea id="edit-camp-desc" name="description" rows="4" required class="w-full text-xs sm:text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-600"></textarea>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label for="edit-camp-start" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Start Date <span class="text-rose-500">*</span></label>
          <input type="date" id="edit-camp-start" name="start_date" required class="w-full text-xs sm:text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-600">
        </div>
        <div>
          <label for="edit-camp-end" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">End Date <span class="text-rose-500">*</span></label>
          <input type="date" id="edit-camp-end" name="end_date" required class="w-full text-xs sm:text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-600">
        </div>
      </div>

      <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200 dark:border-slate-800">
        <button type="button" onclick="closeEditCampaignModal()" class="px-4 py-2 text-xs sm:text-sm font-medium rounded-xl text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800">Cancel</button>
        <button type="submit" class="px-4 py-2 text-xs sm:text-sm font-semibold rounded-xl bg-indigo-800 hover:bg-indigo-900 text-white shadow-sm">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- PARTICIPANTS LIST MODAL -->
<div id="participants-modal" class="fixed inset-0 z-50 hidden bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4">
  <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl">
    <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
      <div>
        <h3 class="font-display font-bold text-lg text-slate-900 dark:text-white" id="modal-part-title">Enrolled Students</h3>
        <p class="text-xs text-slate-500">Participants who pledged support</p>
      </div>
      <button type="button" onclick="closeParticipantsModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <div id="modal-part-list" class="my-4 max-h-60 overflow-y-auto space-y-2 pr-1"></div>

    <div class="flex justify-end pt-3 border-t border-slate-200 dark:border-slate-800">
      <button type="button" onclick="closeParticipantsModal()" class="px-4 py-2 text-xs font-semibold rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 transition-colors">Close</button>
    </div>
  </div>
</div>

<script>
function openAddCampaignModal() {
  document.getElementById('add-campaign-modal').classList.remove('hidden');
}
function closeAddCampaignModal() {
  document.getElementById('add-campaign-modal').classList.add('hidden');
}

function openEditCampaignModal(camp) {
  document.getElementById('edit-camp-id').value = camp.id;
  document.getElementById('edit-camp-title').value = camp.title;
  document.getElementById('edit-camp-desc').value = camp.desc;
  document.getElementById('edit-camp-start').value = camp.start;
  document.getElementById('edit-camp-end').value = camp.end;
  document.getElementById('edit-campaign-modal').classList.remove('hidden');
}
function closeEditCampaignModal() {
  document.getElementById('edit-campaign-modal').classList.add('hidden');
}

function openParticipantsModal(data) {
  document.getElementById('modal-part-title').textContent = data.title;
  const listEl = document.getElementById('modal-part-list');
  listEl.innerHTML = '';

  if (!data.list || data.list.length === 0) {
    listEl.innerHTML = '<p class="text-xs text-slate-400 italic text-center py-6">No students have joined this campaign yet.</p>';
  } else {
    data.list.forEach(p => {
      const item = document.createElement('div');
      item.className = 'flex items-center justify-between p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs';
      item.innerHTML = `
        <div>
          <span class="block font-semibold text-slate-800 dark:text-slate-200">${p.full_name}</span>
          <span class="text-[11px] text-slate-400">${p.email}</span>
        </div>
        <span class="text-[10px] text-slate-400">${p.joined_at}</span>
      `;
      listEl.appendChild(item);
    });
  }

  document.getElementById('participants-modal').classList.remove('hidden');
}
function closeParticipantsModal() {
  document.getElementById('participants-modal').classList.add('hidden');
}
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
