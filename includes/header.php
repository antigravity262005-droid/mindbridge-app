<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/helpers.php';

$currentUser = current_user();
$pageTitle = $pageTitle ?? APP_NAME . ' · Student Mental Wellness & Support';
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?></title>
  
  <!-- Security and Anti-CSRF for dynamic client fetch -->
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
  
  <!-- Google Fonts: Fraunces (Headings) & Inter (Body) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400..700;1,9..144,400..700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          colors: {
            indigo: {
              950: '#0f172a',
              900: '#172c72',
              800: '#1f3a93',
              700: '#2748b0',
              600: '#345ad4',
              100: '#eef2ff',
              50: '#f8fafc',
            },
            amber: {
              500: '#e8a33d',
              600: '#cf8b27',
              50: '#fef7ed',
            }
          },
          fontFamily: {
            sans: ['Inter', 'sans-serif'],
            display: ['Fraunces', 'serif'],
          }
        }
      }
    };
  </script>
  
  <!-- Theme Init Script (prevents flash of white in dark mode) -->
  <script>
    if (localStorage.getItem('mindbridge_theme') === 'dark' || 
       (!('mindbridge_theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
      document.documentElement.classList.add('dark');
    } else {
      document.documentElement.classList.remove('dark');
    }
  </script>
  
  <!-- Custom Design Tokens & Micro-Animations -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
  
  <!-- Chart.js CDN for visual insights -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="min-h-screen flex flex-col bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 antialiased transition-colors duration-200">

  <!-- ========================================================
       PERSISTENT SAFETY & SCOPE NOTICE (Appears on Every Page)
       ======================================================== -->
  <aside aria-label="Emergency and Medical Disclaimer" class="bg-amber-500/10 dark:bg-amber-500/15 border-b border-amber-500/25 px-4 py-2.5 text-xs sm:text-sm text-slate-800 dark:text-amber-200/90 transition-colors">
    <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-2 text-center md:text-left">
      <div class="flex items-center gap-2">
        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-amber-500 text-slate-950 font-bold text-xs shrink-0">!</span>
        <p class="font-normal leading-relaxed">
          <strong class="font-semibold text-slate-950 dark:text-amber-100">MindBridge is an informational referral tool, not an emergency hotline or medical diagnostic service.</strong>
          Requests are not monitored 24/7. In immediate danger, call <strong class="underline decoration-amber-500 font-semibold">911</strong>, go to the nearest emergency room, and notify a trusted adult.
        </p>
      </div>
      <div class="shrink-0 text-xs font-medium bg-amber-500/15 dark:bg-amber-500/20 px-2.5 py-1 rounded-md border border-amber-500/30">
        Philippines NCMH Crisis Hotline: <a href="tel:1553" class="font-bold underline hover:text-amber-700 dark:hover:text-amber-100">1553</a> (Sample directory entry to verify)
      </div>
    </div>
  </aside>

  <!-- ========================================================
       STICKY NAVIGATION BAR
       ======================================================== -->
  <header class="sticky top-0 z-40 bg-white/90 dark:bg-slate-900/90 backdrop-blur-md border-b border-slate-200 dark:border-slate-800 transition-colors shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-18 flex items-center justify-between gap-4">
      
      <!-- Brand Logo -->
      <a href="<?= BASE_URL ?>/" class="flex items-center gap-3 group focus:outline-none focus:ring-2 focus:ring-indigo-600 rounded-lg p-1">
        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-800 to-indigo-950 text-white flex items-center justify-center font-display font-bold text-xl shadow-md group-hover:scale-105 transition-transform duration-200">
          M
        </div>
        <div>
          <span class="font-display font-bold text-xl tracking-tight text-indigo-900 dark:text-white flex items-center gap-1.5">
            MindBridge
            <span class="inline-block w-2 h-2 rounded-full bg-amber-500"></span>
          </span>
          <span class="block text-[11px] font-medium tracking-wide text-slate-500 dark:text-slate-400 uppercase">Student Wellness & Support</span>
        </div>
      </a>

      <!-- Desktop Navigation Links -->
      <nav aria-label="Main Navigation" class="hidden md:flex items-center gap-6 text-sm font-medium">
        <?php if ($currentUser): ?>
          <?php if ($currentUser['role'] === 'administrator'): ?>
            <a href="<?= BASE_URL ?>/admin/dashboard.php" class="text-slate-600 dark:text-slate-300 hover:text-indigo-800 dark:hover:text-white transition-colors">Admin Overview</a>
            <a href="<?= BASE_URL ?>/admin/requests.php" class="text-slate-600 dark:text-slate-300 hover:text-indigo-800 dark:hover:text-white transition-colors">Student Requests</a>
            <a href="<?= BASE_URL ?>/admin/resources.php" class="text-slate-600 dark:text-slate-300 hover:text-indigo-800 dark:hover:text-white transition-colors">Library & Categories</a>
            <a href="<?= BASE_URL ?>/admin/services.php" class="text-slate-600 dark:text-slate-300 hover:text-indigo-800 dark:hover:text-white transition-colors">Support Directory</a>
            <a href="<?= BASE_URL ?>/admin/campaigns.php" class="text-slate-600 dark:text-slate-300 hover:text-indigo-800 dark:hover:text-white transition-colors">Campaigns</a>
          <?php else: ?>
            <a href="<?= BASE_URL ?>/user/dashboard.php" class="text-slate-600 dark:text-slate-300 hover:text-indigo-800 dark:hover:text-white transition-colors">My Dashboard</a>
            <a href="<?= BASE_URL ?>/user/library.php" class="text-slate-600 dark:text-slate-300 hover:text-indigo-800 dark:hover:text-white transition-colors">Educational Library</a>
            <a href="<?= BASE_URL ?>/user/directory.php" class="text-slate-600 dark:text-slate-300 hover:text-indigo-800 dark:hover:text-white transition-colors">Support Services</a>
            <a href="<?= BASE_URL ?>/user/requests.php" class="text-slate-600 dark:text-slate-300 hover:text-indigo-800 dark:hover:text-white transition-colors">My Requests</a>
            <a href="<?= BASE_URL ?>/user/checkins.php" class="text-slate-600 dark:text-slate-300 hover:text-indigo-800 dark:hover:text-white transition-colors">Private Check-in</a>
            <a href="<?= BASE_URL ?>/user/campaigns.php" class="text-slate-600 dark:text-slate-300 hover:text-indigo-800 dark:hover:text-white transition-colors">Campaigns</a>
          <?php endif; ?>
        <?php else: ?>
          <a href="<?= BASE_URL ?>/#overview" class="text-slate-600 dark:text-slate-300 hover:text-indigo-800 dark:hover:text-white transition-colors">Overview</a>
          <a href="<?= BASE_URL ?>/#how-it-works" class="text-slate-600 dark:text-slate-300 hover:text-indigo-800 dark:hover:text-white transition-colors">How It Works</a>
          <a href="<?= BASE_URL ?>/#roles" class="text-slate-600 dark:text-slate-300 hover:text-indigo-800 dark:hover:text-white transition-colors">System Roles</a>
          <a href="<?= BASE_URL ?>/#safety" class="text-slate-600 dark:text-slate-300 hover:text-indigo-800 dark:hover:text-white transition-colors">Safety Scope</a>
        <?php endif; ?>
      </nav>

      <!-- Right Action Utilities (Theme toggle & Auth) -->
      <div class="flex items-center gap-3">
        
        <!-- Dark Mode Toggle Button -->
        <button id="theme-toggle-btn" type="button" aria-label="Toggle visual theme" class="p-2 rounded-xl text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-600 transition-colors">
          <svg id="theme-icon-sun" class="w-5 h-5 hidden dark:block text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
          </svg>
          <svg id="theme-icon-moon" class="w-5 h-5 block dark:hidden text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
          </svg>
        </button>

        <?php if ($currentUser): ?>
          <!-- Logged In User Pill -->
          <div class="flex items-center gap-2 pl-2 border-l border-slate-200 dark:border-slate-800">
            <div class="hidden sm:block text-right">
              <span class="block text-xs font-semibold text-slate-800 dark:text-slate-200"><?= e($currentUser['full_name']) ?></span>
              <span class="block text-[10px] text-slate-500 dark:text-slate-400 capitalize"><?= e($currentUser['role']) ?></span>
            </div>
            <a href="<?= BASE_URL ?>/logout.php" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg transition-colors focus:ring-2 focus:ring-indigo-600">
              Sign out
            </a>
          </div>
        <?php else: ?>
          <!-- Guest Navigation CTAs -->
          <a href="<?= BASE_URL ?>/login.php" class="px-4 py-2 text-sm font-medium text-indigo-900 dark:text-slate-200 hover:text-indigo-700 dark:hover:text-white transition-colors">Sign in</a>
          <a href="<?= BASE_URL ?>/register.php" class="px-4 py-2 text-sm font-medium text-white bg-indigo-800 hover:bg-indigo-900 rounded-xl shadow-sm hover:shadow-md transition-all">Create Account</a>
        <?php endif; ?>

      </div>
    </div>
  </header>

  <!-- Main Content Wrapper -->
  <main class="flex-grow">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
      <?= render_flash() ?>
