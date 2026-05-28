<?php
// db.php
function connectToDatabase() {
    static $db = null;
    if ($db === null) {
        $host = 'localhost';
        $user = 'u82462';
        $pass = '9164341';
        $name = 'u82462';
        $dsn = "mysql:host=$host;dbname=$name;charset=utf8mb4";
        try {
            $db = new PDO($dsn, $user, $pass);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            exit('Ошибка подключения к БД: ' . $e->getMessage());
        }
    }
    return $db;
}

// Модели BMW
function getCarModels() {
    return [
        '1 Series' => 'BMW 1 Series',
        '3 Series' => 'BMW 3 Series',
        '5 Series' => 'BMW 5 Series',
        'X3' => 'BMW X3',
        'X5' => 'BMW X5',
        'X6' => 'BMW X6',
        'i8' => 'BMW i8',
        'M4' => 'BMW M4 Competition',
        'M8' => 'BMW M8 Competition'
    ];
}

// Цвета
function getCarColors() {
    return ['Черный', 'Белый', 'Синий', 'Розовый'];
}

// Опции
function getCarOptions() {
    return [
        'panorama' => 'Панорамная крыша',
        'premium_sound' => 'Премиум-аудиосистема',
        'assist_package' => 'Пакет ассистентов',
        'sport_package' => 'Спортивный пакет'
    ];
}

// Тип двигателя
function getEngineTypes() {
    return ['Бензиновый', 'Дизельный', 'Гибрид', 'Электрический'];
}

// Коробка передач
function getTransmissions() {
    return ['Механика', 'Автомат', 'Робот'];
}

// Привод
function getDriveTypes() {
    return ['Передний', 'Задний', 'Полный'];
}

$allowedGenders = ['male', 'female'];

function validateFormData($formData) {
    global $allowedGenders;
    $errors = [];

    // ФИО
    if (empty($formData['full_name'])) {
        $errors['full_name'] = 'Поле обязательно.';
    } elseif (!preg_match('/^[a-zA-Zа-яА-ЯёЁ\s\-]+$/u', $formData['full_name'])) {
        $errors['full_name'] = 'Только буквы, пробелы, дефис.';
    } elseif (strlen($formData['full_name']) > 150) {
        $errors['full_name'] = 'Максимум 150 символов.';
    } else {
        preg_match_all('/[a-zA-Zа-яА-ЯёЁ]/u', $formData['full_name'], $letters);
        if (count($letters[0]) < 2) $errors['full_name'] = 'Минимум 2 буквы.';
    }

    // Телефон
    if (empty($formData['phone'])) {
        $errors['phone'] = 'Поле обязательно.';
    } elseif (!preg_match('/^\+7[\s\(]*[0-9]{3}[\)\s]*[0-9]{3}[\s\-]*[0-9]{2}[\s\-]*[0-9]{2}$/', $formData['phone'])) {
        $errors['phone'] = 'Неверный формат. Пример: +7 999 123-45-67';
    } else {
        $digits = preg_replace('/\D/', '', $formData['phone']);
        if (strlen($digits) !== 11) $errors['phone'] = 'Нужно 11 цифр.';
        elseif ($digits[0] !== '7') $errors['phone'] = 'Номер должен начинаться с 7.';
    }

    // Email
    if (empty($formData['email'])) {
        $errors['email'] = 'Поле обязательно.';
    } elseif (!preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $formData['email'])) {
        $errors['email'] = 'Некорректный email.';
    }

    // Дата рождения
    if (empty($formData['birth_date'])) {
        $errors['birth_date'] = 'Поле обязательно.';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $formData['birth_date'])) {
        $errors['birth_date'] = 'Формат ГГГГ-ММ-ДД';
    } else {
        $dateObj = DateTime::createFromFormat('Y-m-d', $formData['birth_date']);
        if (!$dateObj || $dateObj->format('Y-m-d') !== $formData['birth_date']) $errors['birth_date'] = 'Неверная дата.';
        elseif ($dateObj > new DateTime('today')) $errors['birth_date'] = 'Дата не может быть в будущем.';
    }

    // Пол
    if (empty($formData['gender'])) $errors['gender'] = 'Выберите пол.';
    elseif (!in_array($formData['gender'], $allowedGenders)) $errors['gender'] = 'Недопустимое значение.';

    // Контракт
    if (!$formData['contract_agreed']) $errors['contract_agreed'] = 'Подтвердите ознакомление с контрактом.';

    // Автомобильные поля
    $carModels = getCarModels();
    if (empty($formData['car_model'])) $errors['car_model'] = 'Выберите модель.';
    elseif (!isset($carModels[$formData['car_model']])) $errors['car_model'] = 'Недопустимая модель.';

    $carColors = getCarColors();
    if (empty($formData['car_color'])) $errors['car_color'] = 'Выберите цвет.';
    elseif (!in_array($formData['car_color'], $carColors)) $errors['car_color'] = 'Недопустимый цвет.';

    if (!empty($formData['car_options'])) {
        $allowedOptions = array_keys(getCarOptions());
        foreach ($formData['car_options'] as $opt) {
            if (!in_array($opt, $allowedOptions)) { $errors['car_options'] = 'Недопустимая опция.'; break; }
        }
    }

    $engineTypes = getEngineTypes();
    if (empty($formData['engine_type'])) $errors['engine_type'] = 'Выберите тип двигателя.';
    elseif (!in_array($formData['engine_type'], $engineTypes)) $errors['engine_type'] = 'Недопустимый тип.';

    $transmissions = getTransmissions();
    if (empty($formData['transmission'])) $errors['transmission'] = 'Выберите коробку передач.';
    elseif (!in_array($formData['transmission'], $transmissions)) $errors['transmission'] = 'Недопустимая КПП.';

    $driveTypes = getDriveTypes();
    if (empty($formData['drive_type'])) $errors['drive_type'] = 'Выберите тип привода.';
    elseif (!in_array($formData['drive_type'], $driveTypes)) $errors['drive_type'] = 'Недопустимый привод.';

    if (!empty($formData['desired_hp']) && (!is_numeric($formData['desired_hp']) || $formData['desired_hp'] < 50 || $formData['desired_hp'] > 2000)) {
        $errors['desired_hp'] = 'Мощность должна быть числом от 50 до 2000 л.с.';
    }

    return $errors;
}

function getApplicationById($id) {
    $pdo = connectToDatabase();
    $stmt = $pdo->prepare("SELECT id, full_name, phone, email, birth_date, gender, contract_agreed,
                                  car_model, car_color, car_options, engine_type, transmission, drive_type, desired_hp
                           FROM applications WHERE id = ?");
    $stmt->execute([$id]);
    $app = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$app) return null;
    $app['car_options'] = json_decode($app['car_options'] ?? '[]', true) ?: [];
    return $app;
}

function saveApplication($id, $formData) {
    $pdo = connectToDatabase();
    $stmt = $pdo->prepare("
        UPDATE applications 
        SET full_name = :fn, phone = :ph, email = :em, birth_date = :bd, gender = :gen,
            contract_agreed = :ca, car_model = :cm, car_color = :cc, car_options = :co,
            engine_type = :et, transmission = :tr, drive_type = :dt, desired_hp = :hp
        WHERE id = :id
    ");
    $stmt->execute([
        ':fn' => $formData['full_name'], ':ph' => $formData['phone'],
        ':em' => $formData['email'], ':bd' => $formData['birth_date'],
        ':gen' => $formData['gender'], ':ca' => $formData['contract_agreed'] ? 1 : 0,
        ':cm' => $formData['car_model'], ':cc' => $formData['car_color'],
        ':co' => json_encode($formData['car_options'] ?? []),
        ':et' => $formData['engine_type'], ':tr' => $formData['transmission'],
        ':dt' => $formData['drive_type'], ':hp' => $formData['desired_hp'] ?: null,
        ':id' => $id
    ]);
}

function createApplication($formData) {
    $pdo = connectToDatabase();
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("
        INSERT INTO applications (full_name, phone, email, birth_date, gender, contract_agreed,
                                  car_model, car_color, car_options, engine_type, transmission, drive_type, desired_hp)
        VALUES (:fn, :ph, :em, :bd, :gen, :ca, :cm, :cc, :co, :et, :tr, :dt, :hp)
    ");
    $stmt->execute([
        ':fn' => $formData['full_name'], ':ph' => $formData['phone'],
        ':em' => $formData['email'], ':bd' => $formData['birth_date'],
        ':gen' => $formData['gender'], ':ca' => $formData['contract_agreed'] ? 1 : 0,
        ':cm' => $formData['car_model'], ':cc' => $formData['car_color'],
        ':co' => json_encode($formData['car_options'] ?? []),
        ':et' => $formData['engine_type'], ':tr' => $formData['transmission'],
        ':dt' => $formData['drive_type'], ':hp' => $formData['desired_hp'] ?: null
    ]);
    $id = $pdo->lastInsertId();

    $login = 'user_' . $id . '_' . bin2hex(random_bytes(4));
    $plainPassword = bin2hex(random_bytes(6));
    $passwordHash = password_hash($plainPassword, PASSWORD_DEFAULT);
    $updStmt = $pdo->prepare("UPDATE applications SET login = ?, password_hash = ? WHERE id = ?");
    $updStmt->execute([$login, $passwordHash, $id]);

    $pdo->commit();
    return ['id' => $id, 'login' => $login, 'password' => $plainPassword];
}
?>