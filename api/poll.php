<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

$user = current_user();

if (!$user) {
    echo json_encode([
        'success'   => false,
        'logged_in' => false,
        'message'   => 'User is not authenticated'
    ]);
    exit;
}

$pdo = get_db();
$response = [
    'success'     => true,
    'logged_in'   => true,
    'role'        => $user['role'],
    'server_time' => date('Y-m-d H:i:s')
];

if ($user['role'] === 'administrator') {
    // Admin Polling Data
    $openCount = (int)$pdo->query("
        SELECT COUNT(*) FROM support_requests 
        WHERE status IN ('Submitted', 'In Review', 'Scheduled')
    ")->fetchColumn();

    $totalStudents = (int)$pdo->query("
        SELECT COUNT(*) FROM users WHERE role = 'user'
    ")->fetchColumn();

    $latestRequest = $pdo->query("
        SELECT r.request_id, r.subject, r.status, r.submitted_at, u.full_name as student_name
        FROM support_requests r
        JOIN users u ON r.user_id = u.user_id
        ORDER BY r.submitted_at DESC
        LIMIT 1
    ")->fetch();

    $response['admin_data'] = [
        'open_requests'  => $openCount,
        'total_students' => $totalStudents,
        'latest_request' => $latestRequest ? [
            'id'           => $latestRequest['request_id'],
            'student_name' => $latestRequest['student_name'],
            'subject'      => $latestRequest['subject'],
            'status'       => $latestRequest['status'],
            'submitted_at' => $latestRequest['submitted_at']
        ] : null
    ];
} else {
    // Student Polling Data (Strict Privacy: check-ins are NEVER queried or exposed in polling)
    $userId = (int)$user['user_id'];

    $openCount = (int)$pdo->prepare("
        SELECT COUNT(*) FROM support_requests 
        WHERE user_id = :uid AND status IN ('Submitted', 'In Review', 'Scheduled')
    ");
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM support_requests 
        WHERE user_id = :uid AND status IN ('Submitted', 'In Review', 'Scheduled')
    ");
    $stmt->execute(['uid' => $userId]);
    $openCount = (int)$stmt->fetchColumn();

    // Check if there is an update on student's requests
    $stmtLatestUpdate = $pdo->prepare("
        SELECT up.update_id, up.request_id, up.status, up.update_note, up.created_at, u.full_name as admin_name
        FROM support_request_updates up
        JOIN support_requests r ON up.request_id = r.request_id
        JOIN users u ON up.updated_by = u.user_id
        WHERE r.user_id = :uid
        ORDER BY up.created_at DESC
        LIMIT 1
    ");
    $stmtLatestUpdate->execute(['uid' => $userId]);
    $latestUpdate = $stmtLatestUpdate->fetch();

    $response['student_data'] = [
        'open_requests' => $openCount,
        'latest_update' => $latestUpdate ? [
            'update_id'   => $latestUpdate['update_id'],
            'request_id'  => $latestUpdate['request_id'],
            'status'      => $latestUpdate['status'],
            'update_note' => $latestUpdate['update_note'],
            'created_at'  => $latestUpdate['created_at'],
            'admin_name'  => $latestUpdate['admin_name']
        ] : null
    ];
}

echo json_encode($response, JSON_PRETTY_PRINT);
exit;
