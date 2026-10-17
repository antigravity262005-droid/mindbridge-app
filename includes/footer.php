    </div><!-- /.max-w-7xl -->
  </main>

  <!-- ========================================================
       GLOBAL FOOTER
       ======================================================== -->
  <footer class="mt-auto bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 text-sm transition-colors">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
        
        <!-- Column 1: Mission -->
        <div class="md:col-span-2">
          <div class="flex items-center gap-2 mb-3">
            <span class="w-8 h-8 rounded-lg bg-indigo-800 text-white font-display font-bold flex items-center justify-center text-sm">M</span>
            <span class="font-display font-bold text-lg text-slate-900 dark:text-white">MindBridge</span>
          </div>
          <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 max-w-prose leading-relaxed mb-4">
            A student mental wellness, awareness, and support referral system developed for the theme 
            <strong class="font-semibold text-slate-700 dark:text-slate-300">Suicide Prevention and Mental Health Awareness</strong>. 
            MindBridge bridges the gap between students seeking help and approved campus or community resources.
          </p>
          <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
            <span>Educational Prototype · Midterm Project (Database Systems)</span>
          </div>
        </div>

        <!-- Column 2: Navigation Links -->
        <div>
          <h4 class="font-semibold text-xs tracking-wider uppercase text-slate-900 dark:text-slate-200 mb-3">Platform Navigation</h4>
          <ul class="space-y-2 text-xs sm:text-sm">
            <li><a href="<?= BASE_URL ?>/" class="hover:text-indigo-700 dark:hover:text-white transition-colors">Home & Overview</a></li>
            <li><a href="<?= BASE_URL ?>/user/library.php" class="hover:text-indigo-700 dark:hover:text-white transition-colors">Educational Library</a></li>
            <li><a href="<?= BASE_URL ?>/user/directory.php" class="hover:text-indigo-700 dark:hover:text-white transition-colors">Support Directory</a></li>
            <li><a href="<?= BASE_URL ?>/user/campaigns.php" class="hover:text-indigo-700 dark:hover:text-white transition-colors">Awareness Campaigns</a></li>
            <li><a href="<?= BASE_URL ?>/user/checkins.php" class="hover:text-indigo-700 dark:hover:text-white transition-colors">Private Mood Check-in</a></li>
          </ul>
        </div>

        <!-- Column 3: Emergency Quick Contacts -->
        <div>
          <h4 class="font-semibold text-xs tracking-wider uppercase text-slate-900 dark:text-slate-200 mb-3">Crisis & Emergency</h4>
          <p class="text-xs text-slate-500 dark:text-slate-400 mb-2">Not monitored in real-time. For urgent safety:</p>
          <div class="space-y-2 text-xs">
            <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700">
              <span class="block font-semibold text-slate-800 dark:text-slate-200">Emergency Services: 911</span>
              <span class="text-[11px] text-slate-500">Police, Ambulance, Emergency Room</span>
            </div>
            <div class="p-2.5 rounded-lg bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/50">
              <span class="block font-semibold text-amber-900 dark:text-amber-200">NCMH Crisis Hotline: 1553</span>
              <span class="text-[11px] text-amber-700/80 dark:text-amber-300/80">0917-899-8727 | 0966-351-4518</span>
            </div>
          </div>
        </div>

      </div>

      <!-- Bottom Bar -->
      <div class="pt-6 border-t border-slate-100 dark:border-slate-800/80 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-400">
        <p>© <?= date('Y') ?> MindBridge. All rights reserved. Database Systems Concept & Prototype.</p>
        <p class="text-center sm:text-right">Designed with empathy, transparency, and accessible web standards.</p>
      </div>
    </div>
  </footer>

  <!-- ========================================================
       TOAST CONTAINER & MODAL DIALOG CONTAINER
       ======================================================== -->
  <div id="toast-container" class="fixed bottom-5 right-5 z-50 flex flex-col gap-2.5 pointer-events-none max-w-sm w-full"></div>

  <div id="confirm-modal" class="fixed inset-0 z-50 hidden bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4 transition-opacity duration-200" role="dialog" aria-modal="true" aria-labelledby="modal-title">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl transform scale-95 transition-transform duration-200">
      <h3 id="modal-title" class="font-display font-bold text-lg text-slate-900 dark:text-white mb-2">Confirm Action</h3>
      <p id="modal-description" class="text-sm text-slate-600 dark:text-slate-400 mb-6 leading-relaxed">Are you sure you wish to proceed?</p>
      <div class="flex items-center justify-end gap-3">
        <button id="modal-cancel-btn" type="button" class="px-4 py-2 text-sm font-medium rounded-xl text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">Cancel</button>
        <button id="modal-confirm-btn" type="button" class="px-4 py-2 text-sm font-medium rounded-xl text-white bg-indigo-800 hover:bg-indigo-900 transition-colors">Confirm</button>
      </div>
    </div>
  </div>

  <!-- Global Application Scripts -->
  <script src="<?= BASE_URL ?>/assets/js/animations.js"></script>
  <script src="<?= BASE_URL ?>/assets/js/app.js"></script>
  <?php if ($currentUser): ?>
    <script src="<?= BASE_URL ?>/assets/js/poll.js"></script>
  <?php endif; ?>

</body>
</html>
