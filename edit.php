<?php
require_once 'db.php';
$auth_realm = 'Admin Panel';
if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('HTTP/1.0 401 Unauthorized');
    header('WWW-Authenticate: Basic realm="' . $auth_realm . '"');
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
    exit;
}

$id = isset($_GET['id']) && ctype_digit($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) die('Неверный ID');
$errors = [];
$formData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'full_name' => trim($_POST['full_name'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'birth_date' => trim($_POST['birth_date'] ?? ''),
        'gender' => $_POST['gender'] ?? '',
        'contract_agreed' => isset($_POST['contract_agreed']),
        'car_model' => $_POST['car_model'] ?? '',
        'car_color' => $_POST['car_color'] ?? '',
        'car_options' => $_POST['car_options'] ?? [],
        'engine_type' => $_POST['engine_type'] ?? '',
        'transmission' => $_POST['transmission'] ?? '',
        'drive_type' => $_POST['drive_type'] ?? '',
        'desired_hp' => isset($_POST['desired_hp']) && is_numeric($_POST['desired_hp']) ? (int)$_POST['desired_hp'] : null
    ];
    $errors = validateFormData($formData);
    if (empty($errors)) {
        saveApplication($id, $formData);
        header('Location: admin.php?msg=updated');
        exit;
    }
} else {
    $formData = getApplicationById($id);
    if (!$formData) die('Анкета не найдена');
    $formData['contract_agreed'] = (bool)$formData['contract_agreed'];
}

$carModels = getCarModels();
$carColors = getCarColors();
$carOptionsList = getCarOptions();
$engineTypes = getEngineTypes();
$transmissions = getTransmissions();
$driveTypes = getDriveTypes();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактирование анкеты №<?= $id ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        .container { max-width: 800px; margin: 2rem auto; background: white; border-radius: 20px; padding: 2rem; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        .form-group input, .form-group select { width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 5px; }
        .radio-group, .options-group { display: flex; flex-wrap: wrap; gap: 1rem; }
        .field-error { color: #e74c3c; font-size: 0.85rem; margin-top: 5px; }
        .btn { background: #e91e63; color: white; border: none; padding: 0.8rem 2rem; border-radius: 30px; cursor: pointer; }
        .cancel { margin-left: 1rem; text-decoration: none; color: #666; }
    </style>
</head>
<body>
<div class="container">
    <h1>Редактирование анкеты #<?= $id ?></h1>
    <?php if (!empty($errors)): ?><div class="alert error">Исправьте ошибки</div><?php endif; ?>
    <form method="post">
        <div class="form-group"><label>ФИО *</label><input type="text" name="full_name" value="<?= htmlspecialchars($formData['full_name']) ?>" required></div>
        <div class="form-group"><label>Телефон *</label><input type="tel" name="phone" value="<?= htmlspecialchars($formData['phone']) ?>" required></div>
        <div class="form-group"><label>Email *</label><input type="email" name="email" value="<?= htmlspecialchars($formData['email']) ?>" required></div>
        <div class="form-group"><label>Дата рождения *</label><input type="date" name="birth_date" value="<?= htmlspecialchars($formData['birth_date']) ?>" required></div>
        <div class="form-group"><label>Пол *</label><div class="radio-group"><label><input type="radio" name="gender" value="male" <?= $formData['gender'] === 'male' ? 'checked' : '' ?>> Мужской</label><label><input type="radio" name="gender" value="female" <?= $formData['gender'] === 'female' ? 'checked' : '' ?>> Женский</label></div></div>
        
        <div class="form-group"><label>Модель BMW *</label><select name="car_model"><?php foreach($carModels as $val=>$label): ?><option value="<?= $val ?>" <?= $formData['car_model'] == $val ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label>Цвет *</label><div class="radio-group"><?php foreach($carColors as $color): ?><label><input type="radio" name="car_color" value="<?= $color ?>" <?= $formData['car_color'] == $color ? 'checked' : '' ?>> <?= $color ?></label><?php endforeach; ?></div></div>
        <div class="form-group"><label>Тип двигателя *</label><select name="engine_type"><?php foreach($engineTypes as $type): ?><option value="<?= $type ?>" <?= $formData['engine_type'] == $type ? 'selected' : '' ?>><?= $type ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label>Коробка передач *</label><select name="transmission"><?php foreach($transmissions as $trans): ?><option value="<?= $trans ?>" <?= $formData['transmission'] == $trans ? 'selected' : '' ?>><?= $trans ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label>Привод *</label><select name="drive_type"><?php foreach($driveTypes as $drive): ?><option value="<?= $drive ?>" <?= $formData['drive_type'] == $drive ? 'selected' : '' ?>><?= $drive ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label>Желаемая мощность (л.с.)</label><input type="number" name="desired_hp" value="<?= htmlspecialchars($formData['desired_hp']) ?>" min="50" max="2000"></div>
        <div class="form-group"><label>Опции</label><div class="options-group"><?php foreach($carOptionsList as $key=>$label): ?><label><input type="checkbox" name="car_options[]" value="<?= $key ?>" <?= in_array($key, $formData['car_options']) ? 'checked' : '' ?>> <?= htmlspecialchars($label) ?></label><?php endforeach; ?></div></div>
        <div class="form-group"><label><input type="checkbox" name="contract_agreed" <?= $formData['contract_agreed'] ? 'checked' : '' ?> required> С контрактом ознакомлен</label></div>
        <button type="submit" class="btn">Сохранить</button>
        <a href="admin.php" class="cancel">Отмена</a>
    </form>
</div>
</body>
</html>