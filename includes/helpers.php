<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * Safely escape string content for HTML output.
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Output standardized JSON response and exit.
 */
function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Set a flash notification message into session.
 */
function flash_set(string $type, string $message): void
{
    $_SESSION['flash_' . $type] = $message;
}

/**
 * Retrieve and clear a flash message from session.
 */
function flash_get(string $type): ?string
{
    $key = 'flash_' . $type;
    if (isset($_SESSION[$key])) {
        $msg = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $msg;
    }
    return null;
}

/**
 * Render all available flash alerts as styled notification banners.
 */
function render_flash(): string
{
    $html = '';
    $types = [
        'success' => ['bg' => 'bg-emerald-50 dark:bg-emerald-950/40', 'border' => 'border-emerald-500/30', 'text' => 'text-emerald-800 dark:text-emerald-200', 'icon' => '✓'],
        'error'   => ['bg' => 'bg-rose-50 dark:bg-rose-950/40',    'border' => 'border-rose-500/30',    'text' => 'text-rose-800 dark:text-rose-200',    'icon' => '✕'],
        'warning' => ['bg' => 'bg-amber-50 dark:bg-amber-950/40',  'border' => 'border-amber-500/30',  'text' => 'text-amber-800 dark:text-amber-200',  'icon' => '⚠'],
        'info'    => ['bg' => 'bg-blue-50 dark:bg-blue-950/40',    'border' => 'border-blue-500/30',    'text' => 'text-blue-800 dark:text-blue-200',    'icon' => 'ℹ'],
    ];

    foreach ($types as $type => $style) {
        $msg = flash_get($type);
        if ($msg) {
            $html .= sprintf(
                '<div class="mb-6 flex items-start gap-3 rounded-xl border p-4 shadow-sm transition-all duration-300 %s %s %s" role="alert">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-current/10 font-bold text-sm">%s</span>
                    <div class="text-sm font-medium leading-relaxed pt-0.5">%s</div>
                </div>',
                $style['bg'], $style['border'], $style['text'], $style['icon'], e($msg)
            );
        }
    }

    return $html;
}

/**
 * Returns Tailwind CSS badge classes for support request statuses.
 */
function badge_status_class(string $status): string
{
    return match ($status) {
        'Submitted'   => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 border border-amber-300/40',
        'In Review'   => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300 border border-blue-300/40',
        'Scheduled'   => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300 border border-indigo-300/40',
        'Completed'   => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 border border-emerald-300/40',
        'Closed'      => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-300/40',
        default       => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
    };
}

/**
 * Format a human-readable date.
 */
function format_date(?string $datetime, string $format = 'M j, Y · g:i A'): string
{
    if (!$datetime) return '—';
    try {
        $dt = new DateTime($datetime);
        return $dt->format($format);
    } catch (Exception) {
        return $datetime;
    }
}

/**
 * Returns an expressive mood emoji corresponding to ratings 1 to 5.
 */
function mood_emoji(int $rating): string
{
    return match ($rating) {
        1 => '😔',
        2 => '😕',
        3 => '😐',
        4 => '🙂',
        5 => '✨',
        default => '🌿',
    };
}

/**
 * Returns a compassionate, warm label for mood ratings.
 */
function mood_label(int $rating): string
{
    return match ($rating) {
        1 => 'Very Low / Overwhelmed',
        2 => 'Somewhat Down',
        3 => 'Balanced / Neutral',
        4 => 'Good / Centered',
        5 => 'Energized & Uplifted',
        default => 'Check-in Recorded',
    };
}

/**
 * Redirects to a local URL and terminates execution.
 */
function redirect(string $path): void
{
    if (!str_starts_with($path, 'http://') && !str_starts_with($path, 'https://')) {
        if (!str_starts_with($path, BASE_URL)) {
            $path = BASE_URL . '/' . ltrim($path, '/');
        }
    }
    header('Location: ' . $path);
    exit;
}

/**
 * Compatibility alias for flash_set.
 */
function set_flash(string $type, string $message): void
{
    flash_set($type, $message);
}

/**
 * Render a styled HTML status badge.
 */
function status_badge(string $status): string
{
    $classes = badge_status_class($status);
    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold ' . $classes . '">' . e($status) . '</span>';
}
