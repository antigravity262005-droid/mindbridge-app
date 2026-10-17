/**
 * MindBridge: Global Application Logic
 * Dark Mode Theme Switcher, Toast Notifications, Confirm Dialogs, Form Guards.
 */

// ========================================================
// 1. THEME SWITCHER (Light / Dark Mode)
// ========================================================
(function initThemeToggle() {
  const toggleBtn = document.getElementById('theme-toggle-btn');
  if (!toggleBtn) return;

  toggleBtn.addEventListener('click', () => {
    const isDark = document.documentElement.classList.toggle('dark');
    localStorage.setItem('mindbridge_theme', isDark ? 'dark' : 'light');

    // Notify user of theme change
    if (window.showToast) {
      window.showToast(isDark ? 'Dark mode enabled' : 'Light mode enabled', 'info', 2000);
    }
  });
})();

// ========================================================
// 2. DYNAMIC TOAST SYSTEM
// ========================================================
window.showToast = function(message, type = 'info', duration = 4000) {
  const container = document.getElementById('toast-container');
  if (!container) return;

  const toast = document.createElement('div');
  toast.className = 'pointer-events-auto flex items-start gap-3 p-4 rounded-xl border shadow-lg text-xs sm:text-sm font-medium transition-all transform translate-y-4 opacity-0';

  // Styling based on toast type
  let icon = 'ℹ️';
  let classes = 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-800 dark:text-slate-200';

  if (type === 'success') {
    icon = '✅';
    classes = 'bg-emerald-50 dark:bg-emerald-950/80 border-emerald-300 dark:border-emerald-800 text-emerald-900 dark:text-emerald-200';
  } else if (type === 'error') {
    icon = '⚠️';
    classes = 'bg-rose-50 dark:bg-rose-950/80 border-rose-300 dark:border-rose-800 text-rose-900 dark:text-rose-200';
  } else if (type === 'warning') {
    icon = '🔔';
    classes = 'bg-amber-50 dark:bg-amber-950/80 border-amber-300 dark:border-amber-800 text-amber-900 dark:text-amber-200';
  }

  toast.className += ' ' + classes;
  toast.innerHTML = `
    <span class="text-base shrink-0">${icon}</span>
    <div class="flex-1 leading-snug">${message}</div>
    <button type="button" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 shrink-0">&times;</button>
  `;

  const closeBtn = toast.querySelector('button');
  closeBtn.addEventListener('click', () => removeToast(toast));

  container.appendChild(toast);

  // Trigger entrance transition
  requestAnimationFrame(() => {
    toast.classList.remove('translate-y-4', 'opacity-0');
  });

  // Auto-dismiss timer
  const autoDismiss = setTimeout(() => {
    removeToast(toast);
  }, duration);

  function removeToast(el) {
    clearTimeout(autoDismiss);
    el.classList.add('opacity-0', 'translate-y-2');
    setTimeout(() => {
      if (el.parentNode) el.parentNode.removeChild(el);
    }, 200);
  }
};

// ========================================================
// 3. CONFIRM MODAL PROMISE WRAPPER
// ========================================================
window.confirmAction = function(title, description) {
  return new Promise((resolve) => {
    const modal = document.getElementById('confirm-modal');
    const titleEl = document.getElementById('modal-title');
    const descEl = document.getElementById('modal-description');
    const cancelBtn = document.getElementById('modal-cancel-btn');
    const confirmBtn = document.getElementById('modal-confirm-btn');

    if (!modal) {
      resolve(confirm(description || 'Are you sure?'));
      return;
    }

    if (titleEl) titleEl.textContent = title || 'Confirm Action';
    if (descEl) descEl.textContent = description || 'Are you sure you wish to proceed?';

    modal.classList.remove('hidden');

    function cleanup(result) {
      modal.classList.add('hidden');
      cancelBtn.removeEventListener('click', onCancel);
      confirmBtn.removeEventListener('click', onConfirm);
      resolve(result);
    }

    function onCancel() { cleanup(false); }
    function onConfirm() { cleanup(true); }

    cancelBtn.addEventListener('click', onCancel);
    confirmBtn.addEventListener('click', onConfirm);
  });
};

// ========================================================
// 4. PREVENT DOUBLE FORM SUBMISSIONS
// ========================================================
document.addEventListener('DOMContentLoaded', () => {
  const forms = document.querySelectorAll('form');
  forms.forEach(form => {
    form.addEventListener('submit', (e) => {
      const submitBtn = form.querySelector('button[type="submit"]');
      if (submitBtn && !submitBtn.disabled) {
        // Allow tiny delay so form serialization completes
        setTimeout(() => {
          submitBtn.disabled = true;
          submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
        }, 50);
      }
    });
  });
});
