<?php
require_once 'db.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$parts = explode('/', trim($path, '/'));
if ($parts[0] !== 'api' || $parts[1] !== 'application') {
    http_response_code(404); echo json_encode(['error' => 'Not found']); exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($parts[2]) && ctype_digit($parts[2]) ? (int)$parts[2] : null;

function authenticateUser() {
    if (!isset($_SERVER['PHP_AUTH_USER'])) return null;
    $login = $_SERVER['PHP_AUTH_USER'];
    $password = $_SERVER['PHP_AUTH_PW'];
    $pdo = connectToDatabase();
    $stmt = $pdo->prepare("SELECT id, password_hash FROM applications WHERE login = ?");
    $stmt->execute([$login]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($password, $user['password_hash'])) return $user['id'];
    return null;
}

try {
    if ($method === 'POST' && $id === null) {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) { http_response_code(400); echo json_encode(['error' => 'Invalid JSON']); exit; }
        $formData = [
            'full_name' => trim($input['full_name'] ?? ''),
            'phone' => trim($input['phone'] ?? ''),
            'email' => trim($input['email'] ?? ''),
            'birth_date' => trim($input['birth_date'] ?? ''),
            'gender' => $input['gender'] ?? '',
            'bio' => trim($input['bio'] ?? ''),
            'contract_agreed' => (bool)($input['contract_agreed'] ?? false),
            'languages' => $input['languages'] ?? []
        ];
        $errors = validateFormData($formData);
        if (!empty($errors)) { http_response_code(422); echo json_encode(['errors' => $errors]); exit; }
        $result = createApplication($formData);
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'id' => $result['id'],
            'login' => $result['login'],
            'password' => $result['password'],
            'profile_url' => '/'
        ]);
    } elseif ($method === 'GET' && $id !== null) {
        $userId = authenticateUser();
        if (!$userId || $userId != $id) { http_response_code(401); echo json_encode(['error' => 'Unauthorized']); exit; }
        $data = getApplicationById($id);
        if (!$data) { http_response_code(404); echo json_encode(['error' => 'Not found']); exit; }
        echo json_encode(['success' => true, 'data' => $data]);
    } elseif ($method === 'PUT' && $id !== null) {
        $userId = authenticateUser();
        if (!$userId || $userId != $id) { http_response_code(401); echo json_encode(['error' => 'Unauthorized']); exit; }
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) { http_response_code(400); echo json_encode(['error' => 'Invalid JSON']); exit; }
        $formData = [
            'full_name' => trim($input['full_name'] ?? ''),
            'phone' => trim($input['phone'] ?? ''),
            'email' => trim($input['email'] ?? ''),
            'birth_date' => trim($input['birth_date'] ?? ''),
            'gender' => $input['gender'] ?? '',
            'bio' => trim($input['bio'] ?? ''),
            'contract_agreed' => (bool)($input['contract_agreed'] ?? false),
            'languages' => $input['languages'] ?? []
        ];
        $errors = validateFormData($formData);
        if (!empty($errors)) { http_response_code(422); echo json_encode(['errors' => $errors]); exit; }
        saveApplication($id, $formData);
        echo json_encode(['success' => true, 'message' => 'Данные обновлены']);
    } else {
        http_response_code(405); echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500); echo json_encode(['error' => $e->getMessage()]);
}
?>