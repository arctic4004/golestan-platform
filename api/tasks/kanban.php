<?php
// api/tasks/kanban.php
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'لطفاً وارد شوید']);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// =============================================
// GET TASKS (دریافت همه تسک‌های کاربر)
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $db->prepare("
        SELECT * FROM tasks 
        WHERE user_id = ? 
        ORDER BY FIELD(priority, 'high', 'medium', 'low'), position ASC
    ");
    $stmt->execute([$user_id]);
    $tasks = $stmt->fetchAll();
    
    // گروه‌بندی بر اساس status
    $board = [
        'todo' => [],
        'in_progress' => [],
        'done' => []
    ];
    
    foreach ($tasks as $task) {
        $board[$task['status']][] = $task;
    }
    
    echo json_encode(['success' => true, 'board' => $board]);
    exit;
}

// =============================================
// CREATE TASK
// =============================================
if ($action === 'create') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priority = $_POST['priority'] ?? 'medium';
    $category = $_POST['category'] ?? 'general';
    $due_date = $_POST['due_date'] ?? null;
    
    if (empty($title)) {
        echo json_encode(['error' => 'عنوان تسک الزامی است']);
        exit;
    }
    
    $stmt = $db->prepare("SELECT MAX(position) FROM tasks WHERE user_id = ? AND status = 'todo'");
    $stmt->execute([$user_id]);
    $max_pos = $stmt->fetchColumn() ?? 0;
    
    $stmt = $db->prepare("INSERT INTO tasks (user_id, title, description, status, priority, category, due_date, position) VALUES (?, ?, ?, 'todo', ?, ?, ?, ?)");
    $stmt->execute([$user_id, $title, $description, $priority, $category, $due_date, $max_pos + 1]);
    
    $task_id = $db->lastInsertId();
    $stmt = $db->prepare("SELECT * FROM tasks WHERE id = ?");
    $stmt->execute([$task_id]);
    $task = $stmt->fetch();
    
    logActivity($user_id, 'task_create', "ایجاد تسک: $title");
    
    echo json_encode(['success' => true, 'task' => $task]);
    exit;
}

// =============================================
// UPDATE TASK STATUS (درگ & دراپ)
// =============================================
if ($action === 'move') {
    $task_id = $_POST['task_id'] ?? 0;
    $new_status = $_POST['status'] ?? 'todo';
    $new_position = intval($_POST['position'] ?? 0);
    
    $allowed_statuses = ['todo', 'in_progress', 'done'];
    if (!in_array($new_status, $allowed_statuses)) {
        echo json_encode(['error' => 'وضعیت نامعتبر']);
        exit;
    }
    
    // آپدیت وضعیت و موقعیت
    $stmt = $db->prepare("UPDATE tasks SET status = ?, position = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
    $stmt->execute([$new_status, $new_position, $task_id, $user_id]);
    
    // بازچینی موقعیت‌ها
    $stmt = $db->prepare("SELECT id FROM tasks WHERE user_id = ? AND status = ? ORDER BY position ASC");
    $stmt->execute([$user_id, $new_status]);
    $tasks_in_column = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tasks_in_column as $index => $id) {
        $stmt = $db->prepare("UPDATE tasks SET position = ? WHERE id = ?");
        $stmt->execute([$index + 1, $id]);
    }
    
    echo json_encode(['success' => true]);
    exit;
}

// =============================================
// UPDATE TASK DETAILS
// =============================================
if ($action === 'update') {
    $task_id = $_POST['task_id'] ?? 0;
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priority = $_POST['priority'] ?? 'medium';
    $due_date = $_POST['due_date'] ?? null;
    
    $stmt = $db->prepare("UPDATE tasks SET title=?, description=?, priority=?, due_date=?, updated_at=NOW() WHERE id=? AND user_id=?");
    $stmt->execute([$title, $description, $priority, $due_date, $task_id, $user_id]);
    
    echo json_encode(['success' => true]);
    exit;
}

// =============================================
// DELETE TASK
// =============================================
if ($action === 'delete') {
    $task_id = $_POST['task_id'] ?? 0;
    
    $stmt = $db->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
    $stmt->execute([$task_id, $user_id]);
    
    echo json_encode(['success' => true]);
    exit;
}
?>