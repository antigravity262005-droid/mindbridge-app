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

    // 1. Create Service
    if ($action === 'create_service') {
        $name        = trim($_POST['service_name'] ?? '');
        $type        = trim($_POST['service_type'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $contact     = trim($_POST['contact_info'] ?? '');
        $isActive    = isset($_POST['is_active']) ? 1 : 0;

        if (empty($name) || empty($type) || empty($description) || empty($contact)) {
            set_flash('error', 'Please fill in all service details.');
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO support_services (service_name, service_type, description, contact_info, is_active, created_by)
                VALUES (:name, :type, :desc, :contact, :active, :user)
            ");
            $stmt->execute([
                'name'    => $name,
                'type'    => $type,
                'desc'    => $description,
                'contact' => $contact,
                'active'  => $isActive,
                'user'    => $admin['user_id']
            ]);
            set_flash('success', "Support service '{$name}' added to directory.");
        }
        redirect('/admin/services.php');
    }

    // 2. Update Service
    if ($action === 'update_service') {
        $serviceId   = (int)($_POST['service_id'] ?? 0);
        $name        = trim($_POST['service_name'] ?? '');
        $type        = trim($_POST['service_type'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $contact     = trim($_POST['contact_info'] ?? '');
        $isActive    = isset($_POST['is_active']) ? 1 : 0;

        if ($serviceId <= 0 || empty($name) || empty($type) || empty($description) || empty($contact)) {
            set_flash('error', 'All fields are required.');
        } else {
            $stmt = $pdo->prepare("
                UPDATE support_services
                SET service_name = :name, service_type = :type, description = :desc, contact_info = :contact, is_active = :active
                WHERE service_id = :id
            ");
            $stmt->execute([
                'name'    => $name,
                'type'    => $type,
                'desc'    => $description,
                'contact' => $contact,
                'active'  => $isActive,
                'id'      => $serviceId
            ]);
            set_flash('success', "Service '{$name}' updated successfully.");
        }
        redirect('/admin/services.php');
    }

    // 3. Toggle Active Status
    if ($action === 'toggle_active') {
        $serviceId = (int)($_POST['service_id'] ?? 0);
        if ($serviceId > 0) {
            $stmt = $pdo->prepare("UPDATE support_services SET is_active = IF(is_active = 1, 0, 1) WHERE service_id = :id");
            $stmt->execute(['id' => $serviceId]);
            set_flash('success', 'Service visibility updated.');
        }
        redirect('/admin/services.php');
    }

    // 4. Delete Service
    if ($action === 'delete_service') {
        $serviceId = (int)($_POST['service_id'] ?? 0);
        if ($serviceId > 0) {
            $stmt = $pdo->prepare("DELETE FROM support_services WHERE service_id = :id");
            $stmt->execute(['id' => $serviceId]);
            set_flash('success', 'Service was removed from the directory.');
        }
        redirect('/admin/services.php');
    }
}

// Fetch all services
$services = $pdo->query("
    SELECT s.*, u.full_name as author_name,
           (SELECT COUNT(*) FROM support_requests r WHERE r.service_id = s.service_id) as request_count
    FROM support_services s
    JOIN users u ON s.created_by = u.user_id
    ORDER BY s.is_active DESC, s.service_name ASC
")->fetchAll();

$pageTitle = 'Support Directory Management · Administrator · MindBridge';
require __DIR__ . '/../includes/header.php';
?>

<!-- Header -->
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-200 dark:border-slate-800">
  <div>
    <h1 class="font-display font-bold text-2xl sm:text-3xl text-slate-900 dark:text-white">
      Support Services Directory
    </h1>
    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
      Manage approved campus counseling centers, community clinics, peer groups, and external hotlines.
    </p>
  </div>
  
  <button type="button" onclick="openAddServiceModal()" class="px-4 py-2 text-xs sm:text-sm font-semibold rounded-xl bg-indigo-800 hover:bg-indigo-900 text-white shadow-sm transition-colors">
    + Add Support Service
  </button>
</div>

<!-- Services Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 my-8">
  <?php if (empty($services)): ?>
    <div class="col-span-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-12 text-center text-slate-500">
      <span class="text-4xl block mb-2">🛡️</span>
      <p class="font-medium">No services registered in the directory yet.</p>
      <p class="text-xs text-slate-400 mt-1">Click "+ Add Support Service" to list campus or community partners.</p>
    </div>
  <?php else: ?>
    <?php foreach ($services as $srv): ?>
      <div class="bg-white dark:bg-slate-900 border <?= $srv['is_active'] ? 'border-slate-200 dark:border-slate-800' : 'border-dashed border-slate-300 dark:border-slate-700 opacity-75' ?> rounded-2xl p-5 shadow-sm flex flex-col justify-between transition-all hover:shadow-md">
        <div>
          <!-- Top Row: Type and Active Badge -->
          <div class="flex items-center justify-between gap-2 mb-3">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
              <?= e($srv['service_type']) ?>
            </span>
            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-medium <?= $srv['is_active'] ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' ?>">
              <span class="w-1.5 h-1.5 rounded-full <?= $srv['is_active'] ? 'bg-emerald-500' : 'bg-slate-400' ?>"></span>
              <?= $srv['is_active'] ? 'Active' : 'Hidden' ?>
            </span>
          </div>

          <h3 class="font-display font-bold text-lg text-slate-900 dark:text-white mb-2">
            <?= e($srv['service_name']) ?>
          </h3>
          <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed mb-4">
            <?= e($srv['description']) ?>
          </p>

          <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-xs text-slate-700 dark:text-slate-300 mb-4">
            <strong class="block text-[11px] uppercase tracking-wider text-slate-400 mb-1">Contact & Location</strong>
            <p class="break-words"><?= nl2br(e($srv['contact_info'])) ?></p>
          </div>
        </div>

        <div>
          <div class="flex items-center justify-between pt-3 border-t border-slate-100 dark:border-slate-800 text-xs text-slate-400 mb-3">
            <span><?= (int)$srv['request_count'] ?> referral(s) linked</span>
            <span>ID #<?= $srv['service_id'] ?></span>
          </div>

          <div class="flex items-center justify-between gap-2">
            <!-- Toggle Active -->
            <form method="POST" action="<?= BASE_URL ?>/admin/services.php" class="inline">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="toggle_active">
              <input type="hidden" name="service_id" value="<?= $srv['service_id'] ?>">
              <button type="submit" class="px-3 py-1.5 text-xs font-medium rounded-xl border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                <?= $srv['is_active'] ? 'Deactivate' : 'Activate' ?>
              </button>
            </form>

            <div class="flex items-center gap-1">
              <button type="button" 
                      onclick="openEditServiceModal(<?= htmlspecialchars(json_encode([
                          'id'          => $srv['service_id'],
                          'name'        => $srv['service_name'],
                          'type'        => $srv['service_type'],
                          'description' => $srv['description'],
                          'contact'     => $srv['contact_info'],
                          'is_active'   => (int)$srv['is_active']
                      ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>)"
                      class="px-3 py-1.5 text-xs font-semibold rounded-xl bg-indigo-50 dark:bg-slate-800 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 dark:hover:bg-slate-700 transition-colors">
                Edit
              </button>
              
              <form method="POST" action="<?= BASE_URL ?>/admin/services.php" class="inline" onsubmit="return confirm('Delete this service? Any linked requests will retain referral records with service cleared.');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete_service">
                <input type="hidden" name="service_id" value="<?= $srv['service_id'] ?>">
                <button type="submit" class="px-2.5 py-1.5 text-xs font-medium rounded-xl text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition-colors">
                  Delete
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- ========================================================
     MODALS: ADD SERVICE / EDIT SERVICE
     ======================================================== -->

<!-- ADD SERVICE MODAL -->
<div id="add-service-modal" class="fixed inset-0 z-50 hidden bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
  <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-lg w-full p-6 shadow-2xl my-8">
    <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-slate-800">
      <h3 class="font-display font-bold text-xl text-slate-900 dark:text-white">Add Support Service</h3>
      <button type="button" onclick="closeAddServiceModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <form method="POST" action="<?= BASE_URL ?>/admin/services.php" class="mt-4 space-y-4">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="create_service">

      <div>
        <label for="create-srv-name" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
          Service Name <span class="text-rose-500">*</span>
        </label>
        <input type="text" id="create-srv-name" name="service_name" required placeholder="e.g. University Guidance & Counseling Office" class="w-full text-xs sm:text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-600">
      </div>

      <div>
        <label for="create-srv-type" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
          Service Type / Category <span class="text-rose-500">*</span>
        </label>
        <input type="text" id="create-srv-type" name="service_type" required placeholder="e.g. On-Campus, Community Clinic, Telehealth, Crisis Line" class="w-full text-xs sm:text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-600">
      </div>

      <div>
        <label for="create-srv-desc" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
          Description & Offered Support <span class="text-rose-500">*</span>
        </label>
        <textarea id="create-srv-desc" name="description" rows="3" required placeholder="Describe what students can expect when connecting with this service..." class="w-full text-xs sm:text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-600"></textarea>
      </div>

      <div>
        <label for="create-srv-contact" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
          Contact Details & Office Hours <span class="text-rose-500">*</span>
        </label>
        <textarea id="create-srv-contact" name="contact_info" rows="2" required placeholder="Phone, email, room number, operating days..." class="w-full text-xs sm:text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-600"></textarea>
      </div>

      <div class="flex items-center gap-2">
        <input type="checkbox" id="create-srv-active" name="is_active" value="1" checked class="w-4 h-4 rounded text-indigo-800 focus:ring-indigo-600 border-slate-300">
        <label for="create-srv-active" class="text-xs font-medium text-slate-700 dark:text-slate-300">
          Make active and visible immediately in student directory
        </label>
      </div>

      <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200 dark:border-slate-800">
        <button type="button" onclick="closeAddServiceModal()" class="px-4 py-2 text-xs sm:text-sm font-medium rounded-xl text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800">Cancel</button>
        <button type="submit" class="px-4 py-2 text-xs sm:text-sm font-semibold rounded-xl bg-indigo-800 hover:bg-indigo-900 text-white shadow-sm">Save Service</button>
      </div>
    </form>
  </div>
</div>

<!-- EDIT SERVICE MODAL -->
<div id="edit-service-modal" class="fixed inset-0 z-50 hidden bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
  <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-lg w-full p-6 shadow-2xl my-8">
    <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-slate-800">
      <h3 class="font-display font-bold text-xl text-slate-900 dark:text-white">Edit Support Service</h3>
      <button type="button" onclick="closeEditServiceModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <form method="POST" action="<?= BASE_URL ?>/admin/services.php" class="mt-4 space-y-4">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="update_service">
      <input type="hidden" name="service_id" id="edit-srv-id" value="">

      <div>
        <label for="edit-srv-name" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Service Name <span class="text-rose-500">*</span></label>
        <input type="text" id="edit-srv-name" name="service_name" required class="w-full text-xs sm:text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-600">
      </div>

      <div>
        <label for="edit-srv-type" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Service Type <span class="text-rose-500">*</span></label>
        <input type="text" id="edit-srv-type" name="service_type" required class="w-full text-xs sm:text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-600">
      </div>

      <div>
        <label for="edit-srv-desc" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Description <span class="text-rose-500">*</span></label>
        <textarea id="edit-srv-desc" name="description" rows="3" required class="w-full text-xs sm:text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-600"></textarea>
      </div>

      <div>
        <label for="edit-srv-contact" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Contact Details & Location <span class="text-rose-500">*</span></label>
        <textarea id="edit-srv-contact" name="contact_info" rows="2" required class="w-full text-xs sm:text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-600"></textarea>
      </div>

      <div class="flex items-center gap-2">
        <input type="checkbox" id="edit-srv-active" name="is_active" value="1" class="w-4 h-4 rounded text-indigo-800 focus:ring-indigo-600 border-slate-300">
        <label for="edit-srv-active" class="text-xs font-medium text-slate-700 dark:text-slate-300">Active and visible to students</label>
      </div>

      <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200 dark:border-slate-800">
        <button type="button" onclick="closeEditServiceModal()" class="px-4 py-2 text-xs sm:text-sm font-medium rounded-xl text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800">Cancel</button>
        <button type="submit" class="px-4 py-2 text-xs sm:text-sm font-semibold rounded-xl bg-indigo-800 hover:bg-indigo-900 text-white shadow-sm">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
function openAddServiceModal() {
  document.getElementById('add-service-modal').classList.remove('hidden');
}
function closeAddServiceModal() {
  document.getElementById('add-service-modal').classList.add('hidden');
}

function openEditServiceModal(srv) {
  document.getElementById('edit-srv-id').value = srv.id;
  document.getElementById('edit-srv-name').value = srv.name;
  document.getElementById('edit-srv-type').value = srv.type;
  document.getElementById('edit-srv-desc').value = srv.description;
  document.getElementById('edit-srv-contact').value = srv.contact;
  document.getElementById('edit-srv-active').checked = (srv.is_active === 1);
  document.getElementById('edit-service-modal').classList.remove('hidden');
}
function closeEditServiceModal() {
  document.getElementById('edit-service-modal').classList.add('hidden');
}
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
