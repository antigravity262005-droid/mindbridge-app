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

    // 1. Create Resource
    if ($action === 'create_resource') {
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $title      = trim($_POST['title'] ?? '');
        $content    = trim($_POST['content'] ?? '');
        $sourceUrl  = trim($_POST['source_url'] ?? '');

        if ($categoryId <= 0 || empty($title) || empty($content)) {
            set_flash('error', 'Please fill in the category, title, and educational content.');
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO mental_health_resources (category_id, title, content, source_url, created_by)
                VALUES (:cat, :title, :content, :src, :user)
            ");
            $stmt->execute([
                'cat'     => $categoryId,
                'title'   => $title,
                'content' => $content,
                'src'     => !empty($sourceUrl) ? $sourceUrl : null,
                'user'    => $admin['user_id']
            ]);
            set_flash('success', "Educational resource '{$title}' successfully published.");
        }
        redirect('/admin/resources.php');
    }

    // 2. Update Resource
    if ($action === 'update_resource') {
        $resourceId = (int)($_POST['resource_id'] ?? 0);
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $title      = trim($_POST['title'] ?? '');
        $content    = trim($_POST['content'] ?? '');
        $sourceUrl  = trim($_POST['source_url'] ?? '');

        if ($resourceId <= 0 || $categoryId <= 0 || empty($title) || empty($content)) {
            set_flash('error', 'All fields except source URL are required.');
        } else {
            $stmt = $pdo->prepare("
                UPDATE mental_health_resources
                SET category_id = :cat, title = :title, content = :content, source_url = :src
                WHERE resource_id = :id
            ");
            $stmt->execute([
                'cat'     => $categoryId,
                'title'   => $title,
                'content' => $content,
                'src'     => !empty($sourceUrl) ? $sourceUrl : null,
                'id'      => $resourceId
            ]);
            set_flash('success', "Resource '{$title}' updated successfully.");
        }
        redirect('/admin/resources.php');
    }

    // 3. Delete Resource
    if ($action === 'delete_resource') {
        $resourceId = (int)($_POST['resource_id'] ?? 0);
        if ($resourceId > 0) {
            $stmt = $pdo->prepare("DELETE FROM mental_health_resources WHERE resource_id = :id");
            $stmt->execute(['id' => $resourceId]);
            set_flash('success', 'Resource was successfully removed.');
        }
        redirect('/admin/resources.php');
    }

    // 4. Create Category
    if ($action === 'create_category') {
        $catName = trim($_POST['category_name'] ?? '');
        $catDesc = trim($_POST['description'] ?? '');

        if (empty($catName)) {
            set_flash('error', 'Category name cannot be empty.');
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO resource_categories (category_name, description) VALUES (:name, :desc)");
                $stmt->execute(['name' => $catName, 'desc' => !empty($catDesc) ? $catDesc : null]);
                set_flash('success', "Category '{$catName}' added successfully.");
            } catch (PDOException $ex) {
                set_flash('error', 'Category already exists or invalid data.');
            }
        }
        redirect('/admin/resources.php');
    }
}

// Fetch categories with resource counts
$categories = $pdo->query("
    SELECT c.category_id, c.category_name, c.description, COUNT(r.resource_id) as resource_count
    FROM resource_categories c
    LEFT JOIN mental_health_resources r ON c.category_id = r.category_id
    GROUP BY c.category_id, c.category_name, c.description
    ORDER BY c.category_name ASC
")->fetchAll();

// Filter & Fetch resources
$selectedCat = (int)($_GET['category'] ?? 0);
$catSql = "
    SELECT r.*, c.category_name, u.full_name as author_name
    FROM mental_health_resources r
    JOIN resource_categories c ON r.category_id = c.category_id
    JOIN users u ON r.created_by = u.user_id
";
$params = [];
if ($selectedCat > 0) {
    $catSql .= " WHERE r.category_id = :cat";
    $params['cat'] = $selectedCat;
}
$catSql .= " ORDER BY r.published_at DESC";

$stmt = $pdo->prepare($catSql);
$stmt->execute($params);
$resources = $stmt->fetchAll();

$pageTitle = 'Resource Library Management · Administrator · MindBridge';
require __DIR__ . '/../includes/header.php';
?>

<!-- Header -->
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-200 dark:border-slate-800">
  <div>
    <h1 class="font-display font-bold text-2xl sm:text-3xl text-slate-900 dark:text-white">
      Educational Resource Management
    </h1>
    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
      Curate evidence-based articles, psychoeducational guides, and wellness coping mechanisms for students.
    </p>
  </div>
  
  <div class="flex items-center gap-2">
    <button type="button" onclick="openAddCategoryModal()" class="px-3.5 py-2 text-xs sm:text-sm font-medium rounded-xl border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
      + New Category
    </button>
    <button type="button" onclick="openAddResourceModal()" class="px-4 py-2 text-xs sm:text-sm font-semibold rounded-xl bg-indigo-800 hover:bg-indigo-900 text-white shadow-sm transition-colors">
      + Add Resource
    </button>
  </div>
</div>

<!-- Category Overview Pills -->
<div class="flex flex-wrap items-center gap-2 my-6">
  <a href="<?= BASE_URL ?>/admin/resources.php" class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition-all <?= $selectedCat === 0 ? 'bg-indigo-800 text-white shadow-sm' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200' ?>">
    All Categories (<?= count($resources) ?>)
  </a>
  <?php foreach ($categories as $cat): ?>
    <a href="<?= BASE_URL ?>/admin/resources.php?category=<?= $cat['category_id'] ?>" class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition-all <?= $selectedCat === (int)$cat['category_id'] ? 'bg-indigo-800 text-white shadow-sm' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200' ?>">
      <?= e($cat['category_name']) ?> (<?= $cat['resource_count'] ?>)
    </a>
  <?php endforeach; ?>
</div>

<!-- Resources Table -->
<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm mb-10">
  <div class="overflow-x-auto">
    <table class="w-full text-left text-xs sm:text-sm text-slate-600 dark:text-slate-300">
      <thead class="bg-slate-50 dark:bg-slate-800/60 text-slate-700 dark:text-slate-200 font-semibold border-b border-slate-200 dark:border-slate-800">
        <tr>
          <th class="px-4 py-3">Resource Title</th>
          <th class="px-4 py-3">Category</th>
          <th class="px-4 py-3">Content Excerpt</th>
          <th class="px-4 py-3">Source URL</th>
          <th class="px-4 py-3">Published</th>
          <th class="px-4 py-3 text-right">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
        <?php if (empty($resources)): ?>
          <tr>
            <td colspan="6" class="px-6 py-12 text-center text-slate-500">
              <span class="text-3xl block mb-2">📚</span>
              <p class="font-medium">No resources found in this category.</p>
              <p class="text-xs text-slate-400 mt-1">Click "+ Add Resource" above to write an article.</p>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($resources as $res): ?>
            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors">
              <td class="px-4 py-3.5 font-semibold text-slate-900 dark:text-white max-w-xs">
                <?= e($res['title']) ?>
              </td>
              <td class="px-4 py-3.5 whitespace-nowrap">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                  <?= e($res['category_name']) ?>
                </span>
              </td>
              <td class="px-4 py-3.5 max-w-sm text-slate-500 dark:text-slate-400">
                <p class="truncate text-xs"><?= e(mb_substr($res['content'], 0, 100)) ?>...</p>
              </td>
              <td class="px-4 py-3.5 text-xs">
                <?php if ($res['source_url']): ?>
                  <a href="<?= e($res['source_url']) ?>" target="_blank" rel="noopener noreferrer" class="text-indigo-700 dark:text-indigo-400 hover:underline inline-flex items-center gap-1">
                    <span>Link</span>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                  </a>
                <?php else: ?>
                  <span class="text-slate-400 italic">Original</span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3.5 text-xs text-slate-400 whitespace-nowrap">
                <?= date('M d, Y', strtotime($res['published_at'])) ?>
              </td>
              <td class="px-4 py-3.5 text-right whitespace-nowrap">
                <button type="button" 
                        onclick="openEditResourceModal(<?= htmlspecialchars(json_encode([
                            'id'          => $res['resource_id'],
                            'category_id' => $res['category_id'],
                            'title'       => $res['title'],
                            'content'     => $res['content'],
                            'source_url'  => $res['source_url'] ?? ''
                        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>)"
                        class="px-2.5 py-1 text-xs font-medium rounded-lg text-indigo-700 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-slate-800 transition-colors">
                  Edit
                </button>
                <form method="POST" action="<?= BASE_URL ?>/admin/resources.php" class="inline" onsubmit="return confirm('Are you sure you want to delete this resource?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete_resource">
                  <input type="hidden" name="resource_id" value="<?= $res['resource_id'] ?>">
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
     MODALS: ADD RESOURCE / EDIT RESOURCE / ADD CATEGORY
     ======================================================== -->

<!-- ADD RESOURCE MODAL -->
<div id="add-resource-modal" class="fixed inset-0 z-50 hidden bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
  <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-2xl w-full p-6 shadow-2xl my-8">
    <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-slate-800">
      <h3 class="font-display font-bold text-xl text-slate-900 dark:text-white">Publish New Resource</h3>
      <button type="button" onclick="closeAddResourceModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <form method="POST" action="<?= BASE_URL ?>/admin/resources.php" class="mt-4 space-y-4">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="create_resource">

      <div>
        <label for="create-category-id" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
          Category <span class="text-rose-500">*</span>
        </label>
        <select id="create-category-id" name="category_id" required class="w-full text-xs sm:text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-600">
          <option value="">Select a Category</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['category_id'] ?>"><?= e($cat['category_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label for="create-title" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
          Article Title <span class="text-rose-500">*</span>
        </label>
        <input type="text" id="create-title" name="title" required placeholder="e.g. Grounding Strategies for Academic Stress" class="w-full text-xs sm:text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-600">
      </div>

      <div>
        <label for="create-content" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
          Educational Content <span class="text-rose-500">*</span>
        </label>
        <textarea id="create-content" name="content" rows="6" required placeholder="Write helpful, empathetic, non-diagnostic guidance..." class="w-full text-xs sm:text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-600 leading-relaxed"></textarea>
      </div>

      <div>
        <label for="create-source-url" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
          Reference / Source URL (Optional)
        </label>
        <input type="url" id="create-source-url" name="source_url" placeholder="https://example.org/study" class="w-full text-xs sm:text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-600">
      </div>

      <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200 dark:border-slate-800">
        <button type="button" onclick="closeAddResourceModal()" class="px-4 py-2 text-xs sm:text-sm font-medium rounded-xl text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800">Cancel</button>
        <button type="submit" class="px-4 py-2 text-xs sm:text-sm font-semibold rounded-xl bg-indigo-800 hover:bg-indigo-900 text-white shadow-sm">Publish Resource</button>
      </div>
    </form>
  </div>
</div>

<!-- EDIT RESOURCE MODAL -->
<div id="edit-resource-modal" class="fixed inset-0 z-50 hidden bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
  <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-2xl w-full p-6 shadow-2xl my-8">
    <div class="flex items-center justify-between pb-4 border-b border-slate-200 dark:border-slate-800">
      <h3 class="font-display font-bold text-xl text-slate-900 dark:text-white">Edit Educational Resource</h3>
      <button type="button" onclick="closeEditResourceModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <form method="POST" action="<?= BASE_URL ?>/admin/resources.php" class="mt-4 space-y-4">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="update_resource">
      <input type="hidden" name="resource_id" id="edit-resource-id" value="">

      <div>
        <label for="edit-category-id" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Category <span class="text-rose-500">*</span></label>
        <select id="edit-category-id" name="category_id" required class="w-full text-xs sm:text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-600">
          <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['category_id'] ?>"><?= e($cat['category_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label for="edit-title" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Article Title <span class="text-rose-500">*</span></label>
        <input type="text" id="edit-title" name="title" required class="w-full text-xs sm:text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-600">
      </div>

      <div>
        <label for="edit-content" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Educational Content <span class="text-rose-500">*</span></label>
        <textarea id="edit-content" name="content" rows="6" required class="w-full text-xs sm:text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-600 leading-relaxed"></textarea>
      </div>

      <div>
        <label for="edit-source-url" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Reference / Source URL</label>
        <input type="url" id="edit-source-url" name="source_url" class="w-full text-xs sm:text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-600">
      </div>

      <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200 dark:border-slate-800">
        <button type="button" onclick="closeEditResourceModal()" class="px-4 py-2 text-xs sm:text-sm font-medium rounded-xl text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800">Cancel</button>
        <button type="submit" class="px-4 py-2 text-xs sm:text-sm font-semibold rounded-xl bg-indigo-800 hover:bg-indigo-900 text-white shadow-sm">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- ADD CATEGORY MODAL -->
<div id="add-category-modal" class="fixed inset-0 z-50 hidden bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4">
  <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl">
    <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
      <h3 class="font-display font-bold text-lg text-slate-900 dark:text-white">Create Resource Category</h3>
      <button type="button" onclick="closeAddCategoryModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <form method="POST" action="<?= BASE_URL ?>/admin/resources.php" class="mt-4 space-y-4">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="create_category">

      <div>
        <label for="cat-name-input" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Category Name <span class="text-rose-500">*</span></label>
        <input type="text" id="cat-name-input" name="category_name" required placeholder="e.g. Stress Resilience" class="w-full text-xs sm:text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-600">
      </div>

      <div>
        <label for="cat-desc-input" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Description</label>
        <textarea id="cat-desc-input" name="description" rows="3" placeholder="Brief summary of topics covered in this category..." class="w-full text-xs sm:text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-600"></textarea>
      </div>

      <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200 dark:border-slate-800">
        <button type="button" onclick="closeAddCategoryModal()" class="px-4 py-2 text-xs sm:text-sm font-medium rounded-xl text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800">Cancel</button>
        <button type="submit" class="px-4 py-2 text-xs sm:text-sm font-semibold rounded-xl bg-indigo-800 hover:bg-indigo-900 text-white shadow-sm">Save Category</button>
      </div>
    </form>
  </div>
</div>

<script>
function openAddResourceModal() {
  document.getElementById('add-resource-modal').classList.remove('hidden');
}
function closeAddResourceModal() {
  document.getElementById('add-resource-modal').classList.add('hidden');
}

function openEditResourceModal(res) {
  document.getElementById('edit-resource-id').value = res.id;
  document.getElementById('edit-category-id').value = res.category_id;
  document.getElementById('edit-title').value = res.title;
  document.getElementById('edit-content').value = res.content;
  document.getElementById('edit-source-url').value = res.source_url;
  document.getElementById('edit-resource-modal').classList.remove('hidden');
}
function closeEditResourceModal() {
  document.getElementById('edit-resource-modal').classList.add('hidden');
}

function openAddCategoryModal() {
  document.getElementById('add-category-modal').classList.remove('hidden');
}
function closeAddCategoryModal() {
  document.getElementById('add-category-modal').classList.add('hidden');
}
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
