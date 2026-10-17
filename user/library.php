<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

require_login();
$pdo = get_db();

// Fetch categories
$categories = $pdo->query("SELECT category_id, category_name, description FROM resource_categories ORDER BY category_id ASC")->fetchAll();

// Filter parameters
$catFilter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search = trim($_GET['search'] ?? '');

$query = "
    SELECT r.resource_id, r.title, r.content, r.source_url, r.published_at, c.category_name, c.category_id, u.full_name as author
    FROM mental_health_resources r
    JOIN resource_categories c ON r.category_id = c.category_id
    JOIN users u ON r.created_by = u.user_id
    WHERE 1=1
";
$params = [];

if ($catFilter > 0) {
    $query .= " AND r.category_id = :cat_id";
    $params['cat_id'] = $catFilter;
}

if (!empty($search)) {
    $query .= " AND (r.title LIKE :search OR r.content LIKE :search)";
    $params['search'] = '%' . $search . '%';
}

$query .= " ORDER BY r.published_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$resources = $stmt->fetchAll();

$pageTitle = 'Educational Resource Library · MindBridge';
require __DIR__ . '/../includes/header.php';
?>

<div class="mb-8 space-y-3">
  <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-50 dark:bg-indigo-950 text-indigo-800 dark:text-indigo-300 text-xs font-semibold uppercase tracking-wider">
    📚 Knowledge & Mental Health Guides
  </div>
  <h1 class="font-display font-bold text-3xl sm:text-4xl text-slate-900 dark:text-white">Educational Library</h1>
  <p class="text-sm text-slate-600 dark:text-slate-400 max-w-2xl leading-relaxed">
    Carefully curated, non-judgmental materials on understanding emotional stress, practicing practical grounding, 
    and learning safe ways to support yourself and peers.
  </p>
</div>

<!-- Category Filters and Search Bar -->
<div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 sm:p-6 mb-8 shadow-sm space-y-4">
  
  <form method="GET" action="<?= BASE_URL ?>/user/library.php" class="flex flex-col sm:flex-row items-center gap-3">
    <?php if ($catFilter > 0): ?>
      <input type="hidden" name="category" value="<?= $catFilter ?>">
    <?php endif; ?>

    <div class="relative flex-grow w-full">
      <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">🔍</span>
      <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search resources by title or topic..."
        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-600">
    </div>

    <button type="submit" class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-indigo-800 hover:bg-indigo-900 text-white font-semibold text-sm transition-colors shrink-0">
      Search Library
    </button>
    <?php if (!empty($search) || $catFilter > 0): ?>
      <a href="<?= BASE_URL ?>/user/library.php" class="w-full sm:w-auto text-center px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-400 text-sm hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors shrink-0">
        Clear
      </a>
    <?php endif; ?>
  </form>

  <!-- Category Pills -->
  <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider mr-1">Categories:</span>
    <a href="<?= BASE_URL ?>/user/library.php<?= !empty($search) ? '?search=' . urlencode($search) : '' ?>"
      class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors <?= $catFilter === 0 ? 'bg-indigo-800 text-white shadow-sm' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200' ?>">
      All Topics
    </a>
    <?php foreach ($categories as $cat): ?>
      <a href="<?= BASE_URL ?>/user/library.php?category=<?= $cat['category_id'] ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>"
        class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors <?= $catFilter === (int)$cat['category_id'] ? 'bg-indigo-800 text-white shadow-sm' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200' ?>">
        <?= e($cat['category_name']) ?>
      </a>
    <?php endforeach; ?>
  </div>

</div>

<!-- Resources Grid -->
<?php if (empty($resources)): ?>
  <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-12 text-center text-slate-400">
    <span class="text-4xl block mb-3">📖</span>
    <p class="text-base font-semibold text-slate-700 dark:text-slate-300">No resources found</p>
    <p class="text-xs mt-1">Try adjusting your search terms or selecting another category.</p>
  </div>
<?php else: ?>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($resources as $res): ?>
      <article class="interactive-card bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm flex flex-col justify-between">
        
        <div>
          <div class="flex items-center justify-between gap-2 mb-3">
            <span class="px-2.5 py-0.5 rounded-md text-[11px] font-semibold bg-indigo-50 dark:bg-indigo-950 text-indigo-800 dark:text-indigo-300 border border-indigo-100 dark:border-indigo-900/60">
              <?= e($res['category_name']) ?>
            </span>
            <span class="text-[11px] text-slate-400">
              <?= format_date($res['published_at'], 'M j, Y') ?>
            </span>
          </div>

          <h2 class="font-display font-bold text-lg text-slate-900 dark:text-white mb-2.5 leading-snug">
            <?= e($res['title']) ?>
          </h2>

          <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 leading-relaxed line-clamp-4 mb-4">
            <?= e($res['content']) ?>
          </p>
        </div>

        <div class="pt-4 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-xs">
          <button type="button" 
            onclick="openResourceModal(<?= htmlspecialchars(json_encode([
              'title' => $res['title'],
              'category' => $res['category_name'],
              'content' => $res['content'],
              'source' => $res['source_url'],
              'date' => format_date($res['published_at']),
              'author' => $res['author']
            ]), ENT_QUOTES, 'UTF-8') ?>)"
            class="font-semibold text-indigo-700 dark:text-indigo-400 hover:underline">
            Read Full Guide →
          </button>

          <?php if (!empty($res['source_url'])): ?>
            <a href="<?= e($res['source_url']) ?>" target="_blank" rel="noopener noreferrer" class="text-slate-400 hover:text-slate-600 flex items-center gap-1 text-[11px]">
              <span>Source</span>
              <span>↗</span>
            </a>
          <?php endif; ?>
        </div>

      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<!-- Resource Reader Modal -->
<div id="resource-modal" class="fixed inset-0 z-50 hidden bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4">
  <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-2xl w-full max-h-[85vh] flex flex-col shadow-2xl overflow-hidden">
    
    <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-start justify-between gap-4">
      <div>
        <span id="modal-res-cat" class="text-xs font-semibold uppercase tracking-wider text-indigo-700 dark:text-indigo-400 block mb-1"></span>
        <h3 id="modal-res-title" class="font-display font-bold text-xl sm:text-2xl text-slate-900 dark:text-white leading-snug"></h3>
        <span id="modal-res-meta" class="text-[11px] text-slate-400 block mt-1"></span>
      </div>
      <button onclick="closeResourceModal()" type="button" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-2xl font-bold p-1">✕</button>
    </div>

    <div class="p-6 overflow-y-auto space-y-4">
      <div id="modal-res-content" class="text-sm sm:text-base text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-line"></div>
      
      <div id="modal-res-source-wrap" class="pt-4 border-t border-slate-100 dark:border-slate-800 hidden text-xs text-slate-500">
        Trusted Reference / Source: <a id="modal-res-source" href="#" target="_blank" rel="noopener noreferrer" class="text-indigo-700 dark:text-indigo-400 underline font-medium break-all"></a>
      </div>
    </div>

    <div class="p-4 bg-slate-50 dark:bg-slate-800/60 border-t border-slate-200 dark:border-slate-800 flex justify-end">
      <button onclick="closeResourceModal()" type="button" class="px-5 py-2 rounded-xl bg-slate-200 dark:bg-slate-700 text-slate-800 dark:text-white text-xs font-semibold">Close</button>
    </div>

  </div>
</div>

<script>
function openResourceModal(data) {
  document.getElementById('modal-res-cat').textContent = data.category;
  document.getElementById('modal-res-title').textContent = data.title;
  document.getElementById('modal-res-meta').textContent = 'Published ' + data.date + ' · Reviewed by ' + data.author;
  document.getElementById('modal-res-content').textContent = data.content;

  const srcWrap = document.getElementById('modal-res-source-wrap');
  const srcLink = document.getElementById('modal-res-source');
  if (data.source) {
    srcLink.href = data.source;
    srcLink.textContent = data.source;
    srcWrap.classList.remove('hidden');
  } else {
    srcWrap.classList.add('hidden');
  }

  document.getElementById('resource-modal').classList.remove('hidden');
}

function closeResourceModal() {
  document.getElementById('resource-modal').classList.add('hidden');
}
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
