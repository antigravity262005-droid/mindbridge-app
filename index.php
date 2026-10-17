<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

$pageTitle = 'MindBridge · A Student Mental Wellness & Support Referral System';

// Fetch quick counts for the dynamic stats counter
$pdo = get_db();
$resourceCount = (int)$pdo->query("SELECT COUNT(*) FROM mental_health_resources")->fetchColumn();
$serviceCount = (int)$pdo->query("SELECT COUNT(*) FROM support_services WHERE is_active = 1")->fetchColumn();
$campaignCount = (int)$pdo->query("SELECT COUNT(*) FROM awareness_campaigns")->fetchColumn();

require __DIR__ . '/includes/header.php';
?>

<!-- ========================================================
     HERO SECTION WITH ANIMATED SUSPENSION-BRIDGE SVG
     ======================================================== -->
<section class="relative pt-6 pb-16 lg:py-24 overflow-hidden">
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
    
    <!-- Hero Copy -->
    <div class="lg:col-span-7 space-y-6">
      
      <!-- Eyebrow Badge -->
      <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200 dark:border-indigo-800 text-indigo-800 dark:text-indigo-300 text-xs font-semibold tracking-wide uppercase">
        <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
        Suicide Prevention & Mental Health Awareness
      </div>

      <!-- Main Headline -->
      <h1 class="font-display font-bold text-4xl sm:text-5xl lg:text-6xl text-slate-900 dark:text-white leading-[1.12] tracking-tight">
        Every student deserves a <span class="text-indigo-800 dark:text-indigo-400 italic">trusted bridge</span> to wellness and care.
      </h1>

      <!-- Warm, Non-Clinical Body Copy -->
      <p class="text-base sm:text-lg text-slate-600 dark:text-slate-300 leading-relaxed max-prose-text">
        Navigating college stress, academic fatigue, and emotional concerns should not feel isolating. 
        MindBridge brings verified school support contacts, guided educational resources, and a safe way 
        to request a guidance appointment into one clear, compassionate space.
      </p>

      <!-- Primary Action CTAs -->
      <div class="flex flex-wrap items-center gap-4 pt-2">
        <a href="<?= BASE_URL ?>/register.php" class="px-6 py-3.5 rounded-xl bg-indigo-800 hover:bg-indigo-900 text-white font-semibold text-sm shadow-md hover:shadow-xl hover:-translate-y-0.5 transition-all flex items-center gap-2 group">
          <span>Create Student Account</span>
          <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
          </svg>
        </a>

        <a href="<?= BASE_URL ?>/login.php" class="px-6 py-3.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 font-semibold text-sm hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
          Sign In
        </a>

        <a href="<?= BASE_URL ?>/user/directory.php" class="px-4 py-3.5 text-indigo-700 dark:text-indigo-400 font-medium text-sm hover:underline flex items-center gap-1.5">
          <span>Browse Support Contacts</span>
          <span>→</span>
        </a>
      </div>

      <!-- Privacy & Trust Footnote -->
      <div class="flex items-center gap-3 pt-2 text-xs text-slate-500 dark:text-slate-400">
        <span class="inline-flex items-center gap-1 text-emerald-600 dark:text-emerald-400 font-medium">
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
          No account limit
        </span>
        <span>•</span>
        <span class="inline-flex items-center gap-1 text-emerald-600 dark:text-emerald-400 font-medium">
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
          100% Private Wellness Check-ins
        </span>
        <span>•</span>
        <span>Role-protected security</span>
      </div>

    </div>

    <!-- Animated Suspension Bridge Illustration -->
    <div class="lg:col-span-5 flex justify-center">
      <div class="relative w-full max-w-lg p-4 rounded-3xl bg-gradient-to-b from-indigo-50/60 to-transparent dark:from-indigo-950/40 border border-slate-200/80 dark:border-slate-800 shadow-xl overflow-hidden">
        
        <!-- Animated SVG Canvas -->
        <svg viewBox="0 0 500 380" class="w-full h-auto drop-shadow-sm" fill="none" xmlns="http://www.w3.org/2000/svg">
          <defs>
            <linearGradient id="skyGrad" x1="250" y1="0" x2="250" y2="300" gradientUnits="userSpaceOnUse">
              <stop stop-color="#1f3a93" stop-opacity="0.12"/>
              <stop offset="1" stop-color="#e8a33d" stop-opacity="0.18"/>
            </linearGradient>
            <linearGradient id="sunGrad" x1="0" y1="0" x2="1" y2="1">
              <stop stop-color="#f59e0b"/>
              <stop offset="1" stop-color="#e8a33d"/>
            </linearGradient>
            <linearGradient id="waterGrad" x1="0" y1="0" x2="0" y2="1">
              <stop stop-color="#1f3a93" stop-opacity="0.2"/>
              <stop offset="1" stop-color="#172c72" stop-opacity="0.45"/>
            </linearGradient>
          </defs>

          <!-- Sky Backdrop -->
          <rect width="500" height="380" rx="16" fill="url(#skyGrad)"/>

          <!-- Rising Golden Sun -->
          <circle cx="250" cy="170" r="52" fill="url(#sunGrad)" class="rising-sun" opacity="0.85"/>
          <circle cx="250" cy="170" r="70" stroke="#f59e0b" stroke-opacity="0.2" stroke-width="2" class="rising-sun"/>

          <!-- Left Suspension Tower -->
          <g class="bridge-tower">
            <rect x="110" y="80" width="16" height="190" rx="3" fill="#1f3a93"/>
            <rect x="106" y="270" width="24" height="25" rx="2" fill="#172c72"/>
            <line x1="118" y1="80" x2="118" y2="270" stroke="#818cf8" stroke-width="1.5" stroke-dasharray="3 3"/>
            <path d="M102 120H134" stroke="#e8a33d" stroke-width="3"/>
            <path d="M102 170H134" stroke="#e8a33d" stroke-width="3"/>
          </g>

          <!-- Right Suspension Tower -->
          <g class="bridge-tower" style="animation-delay: 0.25s;">
            <rect x="374" y="80" width="16" height="190" rx="3" fill="#1f3a93"/>
            <rect x="370" y="270" width="24" height="25" rx="2" fill="#172c72"/>
            <line x1="382" y1="80" x2="382" y2="270" stroke="#818cf8" stroke-width="1.5" stroke-dasharray="3 3"/>
            <path d="M366 120H398" stroke="#e8a33d" stroke-width="3"/>
            <path d="M366 170H398" stroke="#e8a33d" stroke-width="3"/>
          </g>

          <!-- Main Suspension Cable (Catenary Curve) -->
          <path d="M20 180 Q118 80 250 205 Q382 80 480 180" stroke="#1f3a93" stroke-width="5" stroke-linecap="round" fill="none" class="cable-path"/>
          <path d="M20 180 Q118 80 250 205 Q382 80 480 180" stroke="#e8a33d" stroke-width="2" stroke-linecap="round" fill="none" class="cable-path" style="animation-delay:0.5s;"/>

          <!-- Vertical Suspension Droppers -->
          <line x1="150" y1="135" x2="150" y2="240" stroke="#475569" stroke-width="1.5" class="vertical-cable"/>
          <line x1="180" y1="168" x2="180" y2="240" stroke="#475569" stroke-width="1.5" class="vertical-cable"/>
          <line x1="210" y1="190" x2="210" y2="240" stroke="#475569" stroke-width="1.5" class="vertical-cable"/>
          <line x1="250" y1="205" x2="250" y2="240" stroke="#e8a33d" stroke-width="2" class="vertical-cable"/>
          <line x1="290" y1="190" x2="290" y2="240" stroke="#475569" stroke-width="1.5" class="vertical-cable"/>
          <line x1="320" y1="168" x2="320" y2="240" stroke="#475569" stroke-width="1.5" class="vertical-cable"/>
          <line x1="350" y1="135" x2="350" y2="240" stroke="#475569" stroke-width="1.5" class="vertical-cable"/>

          <!-- Bridge Deck / Roadway -->
          <rect x="20" y="240" width="460" height="12" rx="3" fill="#172c72"/>
          <rect x="20" y="243" width="460" height="2" fill="#e8a33d"/>

          <!-- Water Waves / Reflections -->
          <rect x="0" y="280" width="500" height="100" fill="url(#waterGrad)"/>
          <path d="M10 305 Q120 295 240 305 T490 305" stroke="#93c5fd" stroke-width="2" stroke-linecap="round" opacity="0.6" class="water-wave"/>
          <path d="M20 330 Q150 320 280 330 T480 330" stroke="#cbd5e1" stroke-width="1.5" stroke-linecap="round" opacity="0.4" class="water-wave" style="animation-duration: 5.5s;"/>
          <path d="M40 355 Q130 350 260 355 T460 355" stroke="#93c5fd" stroke-width="2" stroke-linecap="round" opacity="0.5" class="water-wave" style="animation-duration: 4.8s;"/>
        </svg>

        <div class="mt-4 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 px-2">
          <span>Symbol of connection & transition</span>
          <span class="text-amber-700 dark:text-amber-400 font-medium">Safe passage forward</span>
        </div>
      </div>
    </div>

  </div>
</section>

<!-- ========================================================
     ANIMATED COUNTERS & STATS SECTION
     ======================================================== -->
<section id="overview" class="py-12 border-y border-slate-200 dark:border-slate-800 bg-white/60 dark:bg-slate-900/60 backdrop-blur-sm -mx-4 sm:-mx-6 lg:-mx-8 px-4 sm:px-6 lg:px-8">
  <div class="max-w-7xl mx-auto">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
      
      <!-- Stat 1 -->
      <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/60 dark:border-slate-700/60">
        <span class="block font-display font-bold text-3xl sm:text-4xl text-indigo-900 dark:text-indigo-400" data-counter-target="<?= max($resourceCount, 8) ?>">0</span>
        <span class="block text-xs sm:text-sm font-medium text-slate-600 dark:text-slate-400 mt-1">Reviewed Guides & Articles</span>
      </div>

      <!-- Stat 2 -->
      <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/60 dark:border-slate-700/60">
        <span class="block font-display font-bold text-3xl sm:text-4xl text-amber-800 dark:text-amber-400" data-counter-target="<?= max($serviceCount, 4) ?>">0</span>
        <span class="block text-xs sm:text-sm font-medium text-slate-600 dark:text-slate-400 mt-1">Verified Support Listings</span>
      </div>

      <!-- Stat 3 -->
      <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/60 dark:border-slate-700/60">
        <span class="block font-display font-bold text-3xl sm:text-4xl text-indigo-900 dark:text-indigo-400" data-counter-target="<?= max($campaignCount, 2) ?>">0</span>
        <span class="block text-xs sm:text-sm font-medium text-slate-600 dark:text-slate-400 mt-1">Campus Awareness Campaigns</span>
      </div>

      <!-- Stat 4 -->
      <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/60 dark:border-slate-700/60">
        <span class="block font-display font-bold text-3xl sm:text-4xl text-emerald-700 dark:text-emerald-400">100%</span>
        <span class="block text-xs sm:text-sm font-medium text-slate-600 dark:text-slate-400 mt-1">Confidential Mood Check-ins</span>
      </div>

    </div>
  </div>
</section>

<!-- ========================================================
     HOW IT WORKS (4-STEP EMPATHETIC FLOW)
     ======================================================== -->
<section id="how-it-works" class="py-16 lg:py-24">
  <div class="text-center max-w-2xl mx-auto mb-14">
    <h2 class="font-display font-bold text-3xl sm:text-4xl text-slate-900 dark:text-white tracking-tight">
      A gentle, structured approach to support.
    </h2>
    <p class="text-sm sm:text-base text-slate-600 dark:text-slate-400 mt-3 leading-relaxed">
      You are always in control of your journey. MindBridge simplifies the help-seeking process in four comfortable steps.
    </p>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    
    <!-- Step 1 -->
    <div class="interactive-card bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 relative">
      <div class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-950 text-indigo-800 dark:text-indigo-300 font-display font-bold text-xl flex items-center justify-center mb-4">
        01
      </div>
      <h3 class="font-display font-bold text-lg text-slate-900 dark:text-white mb-2">Learn & Reflect</h3>
      <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
        Read educational materials on academic anxiety, burnout prevention, and suicide-prevention awareness at your own pace.
      </p>
    </div>

    <!-- Step 2 -->
    <div class="interactive-card bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 relative">
      <div class="w-12 h-12 rounded-xl bg-amber-50 dark:bg-amber-950 text-amber-700 dark:text-amber-400 font-display font-bold text-xl flex items-center justify-center mb-4">
        02
      </div>
      <h3 class="font-display font-bold text-lg text-slate-900 dark:text-white mb-2">Private Check-in</h3>
      <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
        Track your daily mood (1 to 5) with optional reflections. This history is private to you and never shared with staff or administrators.
      </p>
    </div>

    <!-- Step 3 -->
    <div class="interactive-card bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 relative">
      <div class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-950 text-indigo-800 dark:text-indigo-300 font-display font-bold text-xl flex items-center justify-center mb-4">
        03
      </div>
      <h3 class="font-display font-bold text-lg text-slate-900 dark:text-white mb-2">Request Referral</h3>
      <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
        Select a campus counselor or verified community partner to request a one-on-one appointment without clinical friction.
      </p>
    </div>

    <!-- Step 4 -->
    <div class="interactive-card bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 relative">
      <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-400 font-display font-bold text-xl flex items-center justify-center mb-4">
        04
      </div>
      <h3 class="font-display font-bold text-lg text-slate-900 dark:text-white mb-2">Track & Connect</h3>
      <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
        Monitor your request progress on your dashboard with live status updates and helpful follow-up guidance from the administrator.
      </p>
    </div>

  </div>
</section>

<!-- ========================================================
     SYSTEM ROLES & ACCESS BOUNDARIES
     ======================================================== -->
<section id="roles" class="py-16 bg-slate-100/70 dark:bg-slate-900/40 -mx-4 sm:-mx-6 lg:-mx-8 px-4 sm:px-6 lg:px-8 rounded-3xl my-8">
  <div class="max-w-7xl mx-auto">
    <div class="text-center max-w-2xl mx-auto mb-12">
      <h2 class="font-display font-bold text-3xl text-slate-900 dark:text-white tracking-tight">Two distinct roles, zero ambiguity.</h2>
      <p class="text-sm text-slate-600 dark:text-slate-400 mt-2">Built on strict role-based access control (RBAC) to ensure student privacy and administrative clarity.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
      
      <!-- Student Role Card -->
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-8 shadow-sm">
        <div class="flex items-center gap-3 mb-4">
          <span class="w-10 h-10 rounded-xl bg-indigo-100 dark:bg-indigo-900/60 text-indigo-800 dark:text-indigo-300 font-bold flex items-center justify-center text-lg">🎓</span>
          <div>
            <h3 class="font-display font-bold text-xl text-slate-900 dark:text-white">Student User</h3>
            <span class="text-xs text-slate-500 font-medium">Any registered student account</span>
          </div>
        </div>
        <ul class="space-y-3 text-sm text-slate-600 dark:text-slate-300 mb-6">
          <li class="flex items-start gap-2">
            <span class="text-emerald-500 font-bold">✓</span>
            <span>Browse mental wellness and suicide-prevention educational articles.</span>
          </li>
          <li class="flex items-start gap-2">
            <span class="text-emerald-500 font-bold">✓</span>
            <span>Submit appointment or referral requests to verified school services.</span>
          </li>
          <li class="flex items-start gap-2">
            <span class="text-emerald-500 font-bold">✓</span>
            <span>Receive live status updates on personal requests via 3-second dashboard refresh.</span>
          </li>
          <li class="flex items-start gap-2">
            <span class="text-emerald-500 font-bold">✓</span>
            <span class="font-medium text-indigo-900 dark:text-indigo-300">Maintain completely private daily mood check-ins (never accessible to admin).</span>
          </li>
          <li class="flex items-start gap-2">
            <span class="text-emerald-500 font-bold">✓</span>
            <span>Join or leave campus mental wellness awareness campaigns.</span>
          </li>
        </ul>
        <a href="<?= BASE_URL ?>/register.php" class="block text-center py-2.5 rounded-xl bg-indigo-50 dark:bg-indigo-950 text-indigo-800 dark:text-indigo-300 font-semibold text-xs hover:bg-indigo-100 dark:hover:bg-indigo-900 transition-colors">
          Register as Student →
        </a>
      </div>

      <!-- Administrator Role Card -->
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-8 shadow-sm">
        <div class="flex items-center gap-3 mb-4">
          <span class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-900/60 text-amber-800 dark:text-amber-300 font-bold flex items-center justify-center text-lg">🛡️</span>
          <div>
            <h3 class="font-display font-bold text-xl text-slate-900 dark:text-white">Administrator</h3>
            <span class="text-xs text-slate-500 font-medium">Designated guidance staff / faculty</span>
          </div>
        </div>
        <ul class="space-y-3 text-sm text-slate-600 dark:text-slate-300 mb-6">
          <li class="flex items-start gap-2">
            <span class="text-emerald-500 font-bold">✓</span>
            <span>Manage educational resources and resource categories.</span>
          </li>
          <li class="flex items-start gap-2">
            <span class="text-emerald-500 font-bold">✓</span>
            <span>Maintain the verified support-services directory (activate/deactivate).</span>
          </li>
          <li class="flex items-start gap-2">
            <span class="text-emerald-500 font-bold">✓</span>
            <span>Review student appointment requests and record official follow-up notes.</span>
          </li>
          <li class="flex items-start gap-2">
            <span class="text-emerald-500 font-bold">✓</span>
            <span>Create and administer campus awareness campaigns and track engagement.</span>
          </li>
          <li class="flex items-start gap-2">
            <span class="text-rose-500 font-bold">✕</span>
            <span class="text-slate-400">Strictly prohibited from viewing or accessing student wellness check-ins.</span>
          </li>
        </ul>
        <a href="<?= BASE_URL ?>/login.php" class="block text-center py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold text-xs hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
          Administrator Sign In →
        </a>
      </div>

    </div>
  </div>
</section>

<!-- ========================================================
     SAFETY, SCOPE, AND ETHICAL PROTOCOL
     ======================================================== -->
<section id="safety" class="py-16">
  <div class="rounded-3xl bg-gradient-to-br from-amber-500/10 via-indigo-900/5 to-transparent border border-amber-500/30 p-8 sm:p-12">
    <div class="max-w-3xl mx-auto space-y-6">
      
      <div class="flex items-center gap-3 text-amber-800 dark:text-amber-400">
        <span class="p-2 rounded-xl bg-amber-500/20 text-xl font-bold">⚠️</span>
        <h2 class="font-display font-bold text-2xl sm:text-3xl text-slate-900 dark:text-white">
          Our Commitment to Safety and Transparency
        </h2>
      </div>

      <div class="space-y-4 text-sm sm:text-base text-slate-700 dark:text-slate-300 leading-relaxed">
        <p>
          <strong>What MindBridge is:</strong> An educational prototype and supportive referral navigator designed to 
          organize approved school services, de-stigmatize help-seeking behavior, and facilitate non-emergency appointments.
        </p>
        <p>
          <strong>What MindBridge is NOT:</strong> This application is not a clinical counseling service, suicide-crisis hotline, 
          diagnostic instrument, or emergency-dispatch platform. It does not evaluate suicide risk, calculate mental health diagnoses, 
          or guarantee immediate responses.
        </p>
        <p class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/20 text-xs sm:text-sm text-slate-800 dark:text-amber-200">
          <strong>Immediate Danger Notice:</strong> If you or another student are facing an acute crisis or thoughts of self-harm, 
          do not wait for an online form update. Immediately dial <strong>911</strong>, proceed to the nearest emergency department, 
          or call the <strong>National Center for Mental Health (NCMH) Crisis Hotline at 1553</strong> (0917-899-8727).
        </p>
      </div>

    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
