<?php
require_once 'db.php';
$auth_realm = 'Admin Panel';
if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('HTTP/1.0 401 Unauthorized');
    header('WWW-Authenticate: Basic realm="' . $auth_realm . '"');
    echo 'Требуется авторизация';
    exit;
}
$login = $_SERVER['PHP_AUTH_USER'];
$password = $_SERVER['PHP_AUTH_PW'];
$pdo = connectToDatabase();
$stmt = $pdo->prepare("SELECT password_hash FROM admins WHERE login = ?");
$stmt->execute([$login]);
$admin = $stmt->fetch();
if (!$admin || !password_verify($password, $admin['password_hash'])) {
    header('HTTP/1.0 401 Unauthorized');
    header('WWW-Authenticate: Basic realm="' . $auth_realm . '"');
    echo 'Неверный логин или пароль';
    exit;
}

// Удаление анкеты
if (isset($_GET['delete']) && ctype_digit($_GET['delete'])) {
    $id = $_GET['delete'];
    $pdo->prepare("DELETE FROM applications WHERE id = ?")->execute([$id]);
    header('Location: admin.php?msg=deleted');
    exit;
}

// Все анкеты
$applications = $pdo->query("
    SELECT id, full_name, phone, email, birth_date, gender,
           car_model, car_color, car_options, engine_type, transmission, drive_type, desired_hp
    FROM applications ORDER BY id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Статистика (можно сделать по моделям, но для простоты – количество анкет)
$totalCount = count($applications);
$modelStats = $pdo->query("SELECT car_model, COUNT(*) as cnt FROM applications GROUP BY car_model ORDER BY cnt DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админ-панель BMW</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { background: #f5f5f5; font-family: 'Roboto', sans-serif; }
        .container { max-width: 1400px; margin: 0 auto; background: white; border-radius: 20px; padding: 2rem; }
        h1, h2 { color: #333; }
        .stats { background: #e9ecef; padding: 1rem; border-radius: 10px; margin-bottom: 2rem; display: flex; gap: 2rem; flex-wrap: wrap; }
        .stats div { background: white; padding: 1rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .actions a { margin-right: 15px; text-decoration: none; }
        .edit-btn { color: #3498db; }
        .delete-btn { color: #e74c3c; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; vertical-align: top; }
        th { background: #f8f9fa; }
        .msg { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
        .btn { display: inline-block; margin-top: 20px; background: #3498db; color: white; padding: 8px 20px; border-radius: 5px; text-decoration: none; }
    </style>
</head>
<body>
<div class="container">
    <h1>👑 Админ-панель BMW</h1>
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
        <div class="msg">✅ Анкета успешно удалена.</div>
    <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
        <div class="msg">✅ Анкета успешно обновлена.</div>
    <?php endif; ?>

    <div class="stats">
        <div><strong>Всего анкет:</strong> <?= $totalCount ?></div>
        <div><strong>Популярные модели:</strong> <?php foreach($modelStats as $m): ?><?= htmlspecialchars($m['car_model']) ?> (<?= $m['cnt'] ?>) <?php endforeach; ?></div>
    </div>

    <h2>📋 Список анкет</h2>
    <?php if (empty($applications)): ?>
        <p>Нет ни одной анкеты.</p>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr><th>ID</th><th>ФИО</th><th>Телефон</th><th>Email</th><th>Дата рожд.</th><th>Пол</th><th>Модель</th><th>Цвет</th><th>Двигатель</th><th>КПП</th><th>Привод</th><th>Мощность</th><th>Опции</th><th>Действия</th></tr>
                </thead>
                <tbody>
                    <?php foreach($applications as $app): ?>
                    <tr>
                        <td><?= $app['id'] ?></td>
                        <td><?= htmlspecialchars($app['full_name']) ?></td>
                        <td><?= htmlspecialchars($app['phone']) ?></td>
                        <td><?= htmlspecialchars($app['email']) ?></td>
                        <td><?= htmlspecialchars($app['birth_date']) ?></td>
                        <td><?= $app['gender'] === 'male' ? 'Мужской' : 'Женский' ?></td>
                        <td><?= htmlspecialchars($app['car_model']) ?></td>
                        <td><?= htmlspecialchars($app['car_color']) ?></td>
                        <td><?= htmlspecialchars($app['engine_type']) ?></td>
                        <td><?= htmlspecialchars($app['transmission']) ?></td>
                        <td><?= htmlspecialchars($app['drive_type']) ?></td>
                        <td><?= $app['desired_hp'] ? $app['desired_hp'] . ' л.с.' : '—' ?></td>
                        <td><?php $opts = json_decode($app['car_options'] ?? '[]', true); echo htmlspecialchars(implode(', ', $opts)); ?></td>
                        <td class="actions">
                            <a href="edit.php?id=<?= $app['id'] ?>" class="edit-btn">✏️ Ред.</a>
                            <a href="admin.php?delete=<?= $app['id'] ?>" class="delete-btn" onclick="return confirm('Удалить анкету?')">🗑️ Удалить</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    <div style="text-align:center; margin-top:30px;"><a href="index.php" class="btn">← На главную</a></div>
</div>
</body>
</html>