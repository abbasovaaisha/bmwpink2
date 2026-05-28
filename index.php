<?php
require_once 'db.php';
session_start();

// ---------- Функции для cookies и flash ----------
function setJsonCookie($name, $data, $expire = 0) {
    setcookie($name, json_encode($data, JSON_UNESCAPED_UNICODE), $expire, '/', '', false, true);
}
function getJsonCookie($name) {
    if (isset($_COOKIE[$name])) {
        $data = json_decode($_COOKIE[$name], true);
        if (is_array($data)) return $data;
    }
    return null;
}
function deleteCookie($name) { setcookie($name, '', time() - 3600, '/'); }
function setSessionFlash($key, $data) { $_SESSION['flash'][$key] = $data; }
function getSessionFlash($key) {
    $value = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $value;
}

// ---------- Обработка выхода ----------
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['SCRIPT_NAME']);
    exit;
}

// ---------- Обработка входа пользователя ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    $error = null;
    if ($login === '' || $password === '') $error = 'Заполните оба поля.';
    else {
        $pdo = connectToDatabase();
        $stmt = $pdo->prepare("SELECT id, login, password_hash FROM applications WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['authenticated'] = true;
            $_SESSION['app_id'] = $user['id'];
            $_SESSION['login'] = $user['login'];
            header('Location: ' . $_SERVER['SCRIPT_NAME']);
            exit;
        } else $error = 'Неверный логин или пароль.';
    }
    setSessionFlash('login_error', $error);
    header('Location: ' . $_SERVER['SCRIPT_NAME']);
    exit;
}

// ---------- Обработка отправки формы (фоллбек) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['action']) || $_POST['action'] !== 'login')) {
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
    $isAuthenticated = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;

    if (!empty($errors)) {
        if ($isAuthenticated) { setSessionFlash('auth_errors', $errors); setSessionFlash('auth_input', $formData); }
        else { setJsonCookie('form_errors', $errors, 0); setJsonCookie('sticky_form_data', $formData, 0); }
        header('Location: ' . $_SERVER['SCRIPT_NAME']);
        exit;
    }

    try {
        if ($isAuthenticated) {
            saveApplication($_SESSION['app_id'], $formData);
            setSessionFlash('success_message', 'Данные успешно обновлены!');
        } else {
            $result = createApplication($formData);
            setJsonCookie('new_credentials', ['login' => $result['login'], 'password' => $result['password']], 0);
            unset($formData['contract_agreed']);
            setJsonCookie('default_form_data', $formData, time() + 365*24*3600);
            setJsonCookie('success_flash', ['message' => 'Анкета сохранена!'], 0);
        }
        header('Location: ' . $_SERVER['SCRIPT_NAME']);
        exit;
    } catch (Exception $e) {
        $errorMsg = 'Ошибка сохранения: ' . $e->getMessage();
        if ($isAuthenticated) { setSessionFlash('auth_errors', ['database' => $errorMsg]); setSessionFlash('auth_input', $formData); }
        else { setJsonCookie('form_errors', ['database' => $errorMsg], 0); setJsonCookie('sticky_form_data', $formData, 0); }
        header('Location: ' . $_SERVER['SCRIPT_NAME']);
        exit;
    }
}

// ---------- GET-запрос: загрузка данных для формы ----------
$formInput = [
    'full_name' => '', 'phone' => '', 'email' => '', 'birth_date' => '',
    'gender' => '', 'contract_agreed' => false,
    'car_model' => '', 'car_color' => '', 'car_options' => [],
    'engine_type' => '', 'transmission' => '', 'drive_type' => '', 'desired_hp' => null
];
$errorList = [];
$successMessage = '';
$credentialsMessage = null;
$loginError = getSessionFlash('login_error');
$isAuthenticated = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;

if ($isAuthenticated) {
    $authErrors = getSessionFlash('auth_errors');
    $authInput = getSessionFlash('auth_input');
    if ($authErrors !== null && $authInput !== null) {
        $errorList = $authErrors;
        $formInput = array_merge($formInput, $authInput);
    } else {
        $appId = $_SESSION['app_id'];
        $pdo = connectToDatabase();
        $stmt = $pdo->prepare("SELECT full_name, phone, email, birth_date, gender, contract_agreed,
                                      car_model, car_color, car_options, engine_type, transmission, drive_type, desired_hp
                               FROM applications WHERE id = ?");
        $stmt->execute([$appId]);
        $dbData = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($dbData) {
            $formInput['full_name'] = $dbData['full_name'];
            $formInput['phone'] = $dbData['phone'];
            $formInput['email'] = $dbData['email'];
            $formInput['birth_date'] = $dbData['birth_date'];
            $formInput['gender'] = $dbData['gender'];
            $formInput['contract_agreed'] = (bool)$dbData['contract_agreed'];
            $formInput['car_model'] = $dbData['car_model'];
            $formInput['car_color'] = $dbData['car_color'];
            $formInput['car_options'] = json_decode($dbData['car_options'] ?? '[]', true) ?: [];
            $formInput['engine_type'] = $dbData['engine_type'];
            $formInput['transmission'] = $dbData['transmission'];
            $formInput['drive_type'] = $dbData['drive_type'];
            $formInput['desired_hp'] = $dbData['desired_hp'];
        }
    }
    $successMessage = getSessionFlash('success_message') ?? '';
} else {
    $stickyErrors = getJsonCookie('form_errors');
    $stickyData = getJsonCookie('sticky_form_data');
    if ($stickyErrors !== null && $stickyData !== null) {
        $errorList = $stickyErrors;
        $formInput = array_merge($formInput, $stickyData);
        deleteCookie('form_errors');
        deleteCookie('sticky_form_data');
    } else {
        $defaultData = getJsonCookie('default_form_data');
        if ($defaultData !== null) $formInput = array_merge($formInput, $defaultData);
    }
    $successMessage = getJsonCookie('success_flash')['message'] ?? '';
    if ($successMessage) deleteCookie('success_flash');
    $creds = getJsonCookie('new_credentials');
    if ($creds && isset($creds['login'], $creds['password'])) {
        $credentialsMessage = "Логин: {$creds['login']}<br>Пароль: {$creds['password']}<br><strong>Сохраните их для редактирования!</strong>";
        deleteCookie('new_credentials');
    }
}

// Получаем списки для выпадающих меню
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BMW - Розовый проект</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Open+Sans:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Дополнительные стили для анкеты */
        .anketa-section { background: #f9f9f9; padding: 3rem 1rem; }
        .anketa-container { max-width: 800px; margin: 0 auto; background: white; border-radius: 20px; padding: 2rem; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 5px; font-family: inherit; }
        .form-group select[multiple] { min-height: 120px; }
        .radio-group, .options-group { display: flex; flex-wrap: wrap; gap: 1rem; margin-top: 0.5rem; }
        .field-error { color: #e74c3c; font-size: 0.85rem; margin-top: 5px; }
        .has-error input, .has-error select, .has-error textarea { border-color: #e74c3c; background-color: #fff5f5; }
        .alert { padding: 1rem; border-radius: 5px; margin-bottom: 1rem; }
        .alert.success { background: #d4edda; color: #155724; }
        .alert.error { background: #f8d7da; color: #721c24; }
        .credentials-box { background: #d4edda; border-left: 5px solid #28a745; padding: 1rem; border-radius: 5px; margin: 1rem 0; }
        .auth-panel { background: #f0f4ff; padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; }
        .auth-form input { padding: 0.5rem; margin-right: 0.5rem; border: 1px solid #ccc; border-radius: 4px; }
        .auth-form button { background: #2c3e50; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer; }
        .logout-btn { background: #e74c3c; color: white; padding: 0.5rem 1rem; border-radius: 4px; text-decoration: none; }
        .btn { background: #e91e63; color: white; border: none; padding: 0.8rem 2rem; border-radius: 30px; cursor: pointer; transition: all 0.3s; }
        .btn:hover { background: #ad1457; transform: translateY(-2px); }
    </style>
</head>
<body>
    <!-- Header with Video -->
    <header>
        <div class="video-container">
            <video autoplay muted loop>
                <source src="bmwvideo.mp4" type="video/mp4">
                Ваш браузер не поддерживает видео.
            </video>
            <div class="overlay"></div>
        </div>
        <nav>
            <a href="#" class="logo">BMW <span>Pink</span></a>
            <ul class="nav-links">
                <li><a href="#">Главная</a></li>
                <li><a href="#models">Модели</a></li>
                <li><a href="#calculator">Калькулятор</a></li>
                <li><a href="#tuning">Тюнинг</a></li>
                <li><a href="#slider">Галерея</a></li>
                <li><a href="#contact">Контакты</a></li>
                <li><a href="#anketa" class="btn">Анкета</a></li>
                <li><a href="admin.php" class="btn" style="background:#e67e22;">Админ</a></li>
            </ul>
            <div class="burger">
                <div></div>
                <div></div>
                <div></div>
            </div>
        </nav>
        <div class="hero">
            <h1>BMW в розовом стиле</h1>
            <p>Откройте для себя новый взгляд на легендарные автомобили BMW в эксклюзивной розовой тематике</p>
            <a href="#models" class="btn">Исследовать модели</a>
        </div>
    </header>

    <!-- Models Section -->
    <section id="models">
        <div class="section-title">
            <h2>Наши модели</h2>
            <p>Выберите идеальный BMW для себя из нашего эксклюзивного каталога</p>
        </div>
        <div class="models-grid">
            <div class="model-card" id="m1">
                <div class="model-img"><img src="bmw1black.jpeg" alt="BMW 1 Series" id="model-1-img"></div>
                <div class="model-info"><h3>BMW 1 Series</h3><p>Компактный премиальный автомобиль.</p><div class="model-price">от 2 500 000 ₽</div>
                    <div class="color-picker"><h4>Выберите цвет:</h4><div class="color-options"><div class="color-option active" style="background-color:#2A2A2A;" data-model="1" data-color-name="Черный"></div><div class="color-option" style="background-color:#FFFFFF;border:1px solid #eee;" data-model="1" data-color-name="Белый"></div><div class="color-option" style="background-color:#003DA5;" data-model="1" data-color-name="Синий"></div><div class="color-option" style="background-color:#E91E63;" data-model="1" data-color-name="Розовый"></div></div></div>
                </div>
            </div>
            <div class="model-card" id="m3">
                <div class="model-img"><img src="bmw3black.jpeg" alt="BMW 3 Series" id="model-2-img"></div>
                <div class="model-info"><h3>BMW 3 Series</h3><p>Спортивный седан.</p><div class="model-price">от 3 800 000 ₽</div>
                    <div class="color-picker"><h4>Выберите цвет:</h4><div class="color-options"><div class="color-option active" style="background-color:#2A2A2A;" data-model="2" data-color-name="Черный"></div><div class="color-option" style="background-color:#FFFFFF;border:1px solid #eee;" data-model="2" data-color-name="Белый"></div><div class="color-option" style="background-color:#003DA5;" data-model="2" data-color-name="Синий"></div><div class="color-option" style="background-color:#E91E63;" data-model="2" data-color-name="Розовый"></div></div></div>
                </div>
            </div>
            <div class="model-card" id="m5">
                <div class="model-img"><img src="bmw5black.jpeg" alt="BMW 5 Series" id="model-3-img"></div>
                <div class="model-info"><h3>BMW 5 Series</h3><p>Бизнес-класс.</p><div class="model-price">от 4 900 000 ₽</div>
                    <div class="color-picker"><h4>Выберите цвет:</h4><div class="color-options"><div class="color-option active" style="background-color:#2A2A2A;" data-model="3" data-color-name="Черный"></div><div class="color-option" style="background-color:#FFFFFF;border:1px solid #eee;" data-model="3" data-color-name="Белый"></div><div class="color-option" style="background-color:#003DA5;" data-model="3" data-color-name="Синий"></div><div class="color-option" style="background-color:#E91E63;" data-model="3" data-color-name="Розовый"></div></div></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Fast Models Section -->
    <section class="fast-models">
        <div class="section-title"><h2>Самые быстрые BMW в мире</h2><p>Рейтинг самых скоростных моделей</p></div>
        <table class="fast-table">
            <thead><tr><th>Модель</th><th>Разгон 0-100 км/ч</th><th>Макс. скорость</th><th>Двигатель</th><th>Мощность</th></tr></thead>
            <tbody>
                <tr><td>BMW M8 Competition Coupe</td><td>3.2 с</td><td>305 км/ч</td><td>4.4л V8 twin-turbo</td><td>625–640 л.с.</td></tr>
                <tr><td>BMW M5 CS</td><td>3.0 с</td><td>305 км/ч</td><td>4.4л V8 twin-turbo</td><td>635 л.с.</td></tr>
                <tr><td>BMW M5 Competition</td><td>3.3 с</td><td>305 км/ч</td><td>4.4л V8 twin-turbo</td><td>625 л.с.</td></tr>
                <tr><td>BMW M4 CSL</td><td>3.6 с</td><td>307 км/ч</td><td>3.0л рядный 6 twin-turbo</td><td>550 л.с.</td></tr>
                <tr><td>BMW M3 Competition xDrive</td><td>3.5 с</td><td>290 км/ч</td><td>3.0л рядный 6 twin-turbo</td><td>510 л.с.</td></tr>
                <tr><td>BMW X3 M Competition</td><td>3.8 с</td><td>285 км/ч</td><td>3.0л рядный 6 twin-turbo</td><td>510 л.с.</td></tr>
            </tbody>
        </table>
    </section>

    <!-- Tuning Section -->
    <section id="tuning">
        <div class="section-title"><h2>Дополнительные услуги тюнинга</h2><p>Индивидуальные решения</p></div>
        <div class="tuning-grid">
            <div class="tuning-card"><div class="tuning-img"><img src="obves.jpg"></div><div class="tuning-info"><h3>Аэродинамический обвес</h3><p>Улучшение аэродинамики</p><div class="tuning-price">от 150 000 ₽</div></div></div>
            <div class="tuning-card"><div class="tuning-img"><img src="sport.jpg"></div><div class="tuning-info"><h3>Спортивная выхлопная система</h3><p>Улучшение мощности и звука</p><div class="tuning-price">от 80 000 ₽</div></div></div>
            <div class="tuning-card"><div class="tuning-img"><img src="chip.jpg"></div><div class="tuning-info"><h3>Чип-тюнинг</h3><p>Оптимизация работы двигателя</p><div class="tuning-price">от 50 000 ₽</div></div></div>
        </div>
    </section>

    <!-- Slider Section -->
    <section id="slider" class="slider-section">
        <div class="section-title"><h2>Галерея BMW</h2><p>Лучшие модели</p></div>
        <div class="slider-container">
            <div class="slider">
                <div class="slide"><img src="m4.jpg"><div class="slide-content"><h3>BMW M4 Competition</h3><p>510 л.с., M xDrive</p></div></div>
                <div class="slide"><img src="bmwi8.jpg"><div class="slide-content"><h3>BMW i8 Roadster</h3><p>Гибридный спорткар</p></div></div>
                <div class="slide"><img src="bmwx6.jpeg"><div class="slide-content"><h3>BMW X6 M</h3><p>Кроссовер-купе V8</p></div></div>
            </div>
            <div class="slider-controls"><button class="slider-btn prev-btn"><i class="fas fa-chevron-left"></i></button><button class="slider-btn next-btn"><i class="fas fa-chevron-right"></i></button></div>
            <div class="slider-dots"><span class="dot active" data-slide="0"></span><span class="dot" data-slide="1"></span><span class="dot" data-slide="2"></span></div>
        </div>
    </section>

    <!-- Calculator Section -->
    <section id="calculator">
        <div class="section-title"><h2>Калькулятор стоимости</h2><p>Рассчитайте стоимость вашего будущего BMW</p></div>
        <div class="calculator">
            <form class="calculator-form" id="price-calculator">
                <div class="form-group"><label for="model">Модель</label><select id="model"><option value="2500000">BMW 1 Series - 2 500 000 ₽</option><option value="3800000">BMW 3 Series - 3 800 000 ₽</option><option value="4900000">BMW 5 Series - 4 900 000 ₽</option><option value="4200000">BMW X3 - 4 200 000 ₽</option><option value="6500000">BMW X5 - 6 500 000 ₽</option><option value="12000000">BMW i8 - 12 000 000 ₽</option></select></div>
                <div class="form-group"><label for="color">Цвет</label><select id="color"><option value="0">Черный (стандартный)</option><option value="50000">Белый (+50 000 ₽)</option><option value="100000">Синий (+100 000 ₽)</option><option value="150000">Розовый (+150 000 ₽)</option></select></div>
                <div class="form-group"><label for="interior">Салон</label><select id="interior"><option value="0">Стандартная кожа</option><option value="150000">Кожа Nappa (+150 000 ₽)</option><option value="250000">Индивидуальная (+250 000 ₽)</option></select></div>
                <div class="form-group"><label for="wheels">Диски</label><select id="wheels"><option value="0">Стандартные 17"</option><option value="80000">Легкосплавные 18" (+80 000 ₽)</option><option value="150000">Легкосплавные 19" (+150 000 ₽)</option></select></div>
                <div class="form-group full-width"><label>Опции</label><div class="options-group"><div class="option-checkbox"><input type="checkbox" id="panorama" value="120000"><label for="panorama">Панорамная крыша (+120 000 ₽)</label></div><div class="option-checkbox"><input type="checkbox" id="premium-sound" value="90000"><label for="premium-sound">Премиум-аудио (+90 000 ₽)</label></div><div class="option-checkbox"><input type="checkbox" id="assist-package" value="150000"><label for="assist-package">Пакет ассистентов (+150 000 ₽)</label></div><div class="option-checkbox"><input type="checkbox" id="sport-package" value="180000"><label for="sport-package">Спортивный пакет (+180 000 ₽)</label></div></div></div>
                <div class="calculator-result"><h3>Итоговая стоимость</h3><div class="total-price" id="total-price">2 500 000 ₽</div></div>
            </form>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact">
        <div class="section-title"><h2>Свяжитесь с нами</h2><p>Оставьте заявку, и наш менеджер свяжется с вами</p></div>
        <form class="contact-form" id="contact-form">
            <div class="form-group"><label for="name">Ваше имя</label><input type="text" id="name" name="name" required></div>
            <div class="form-group"><label for="email">Электронная почта</label><input type="email" id="email" name="email" required></div>
            <div class="form-group"><label for="phone">Телефон</label><input type="tel" id="phone" name="phone" required></div>
            <div class="form-group"><label for="message">О себе</label><textarea id="message" name="message" placeholder="Расскажите о ваших пожеланиях (минимум 10 символов)..." required></textarea></div>
            <button type="submit" class="btn">Отправить заявку</button>
            <div class="form-message" id="form-message"></div>
        </form>
    </section>

    <!-- Анкета (автомобильная) -->
    <section id="anketa" class="anketa-section">
        <div class="section-title">
            <h2>Анкета для получения логина и пароля</h2>
            <p>Заполните форму, чтобы получить доступ к редактированию ваших данных</p>
        </div>
        <div class="anketa-container">
            <?php if ($loginError): ?>
                <div class="alert error">❌ <?= htmlspecialchars($loginError) ?></div>
            <?php endif; ?>
            <?php if ($credentialsMessage): ?>
                <div class="credentials-box">🔐 <?= $credentialsMessage ?></div>
            <?php endif; ?>
            <?php if ($successMessage): ?>
                <div class="alert success">✅ <?= htmlspecialchars($successMessage) ?></div>
            <?php endif; ?>
            <?php if (isset($errorList['database'])): ?>
                <div class="alert error">❌ <?= htmlspecialchars($errorList['database']) ?></div>
            <?php endif; ?>
            
            <?php if ($isAuthenticated): ?>
                <div class="auth-panel">
                    <span>👋 Вы вошли как <strong><?= htmlspecialchars($_SESSION['login']) ?></strong></span>
                    <a href="?logout=1" class="logout-btn">🚪 Выйти</a>
                </div>
            <?php else: ?>
                <div class="auth-panel">
                    <form method="post" class="auth-form">
                        <input type="hidden" name="action" value="login">
                        <input type="text" name="login" placeholder="Логин" required>
                        <input type="password" name="password" placeholder="Пароль" required>
                        <button type="submit">Войти для редактирования</button>
                    </form>
                </div>
            <?php endif; ?>

            <form method="post" action="" id="anketa-form">
                <div class="form-group <?= isset($errorList['full_name']) ? 'has-error' : '' ?>">
                    <label>ФИО *</label>
                    <input type="text" name="full_name" value="<?= htmlspecialchars($formInput['full_name'] ?? '') ?>" required>
                    <?php if (isset($errorList['full_name'])): ?><div class="field-error"><?= $errorList['full_name'] ?></div><?php endif; ?>
                </div>
                <div class="form-group <?= isset($errorList['phone']) ? 'has-error' : '' ?>">
                    <label>Телефон *</label>
                    <input type="tel" name="phone" value="<?= htmlspecialchars($formInput['phone'] ?? '') ?>" required>
                    <?php if (isset($errorList['phone'])): ?><div class="field-error"><?= $errorList['phone'] ?></div><?php endif; ?>
                </div>
                <div class="form-group <?= isset($errorList['email']) ? 'has-error' : '' ?>">
                    <label>Email *</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($formInput['email'] ?? '') ?>" required>
                    <?php if (isset($errorList['email'])): ?><div class="field-error"><?= $errorList['email'] ?></div><?php endif; ?>
                </div>
                <div class="form-group <?= isset($errorList['birth_date']) ? 'has-error' : '' ?>">
                    <label>Дата рождения *</label>
                    <input type="date" name="birth_date" value="<?= htmlspecialchars($formInput['birth_date'] ?? '') ?>" required>
                    <?php if (isset($errorList['birth_date'])): ?><div class="field-error"><?= $errorList['birth_date'] ?></div><?php endif; ?>
                </div>
                <div class="form-group <?= isset($errorList['gender']) ? 'has-error' : '' ?>">
                    <label>Пол *</label>
                    <div class="radio-group">
                        <label><input type="radio" name="gender" value="male" <?= ($formInput['gender'] ?? '') === 'male' ? 'checked' : '' ?> required> Мужской</label>
                        <label><input type="radio" name="gender" value="female" <?= ($formInput['gender'] ?? '') === 'female' ? 'checked' : '' ?>> Женский</label>
                    </div>
                    <?php if (isset($errorList['gender'])): ?><div class="field-error"><?= $errorList['gender'] ?></div><?php endif; ?>
                </div>

                <div class="form-group <?= isset($errorList['car_model']) ? 'has-error' : '' ?>">
                    <label>Модель BMW *</label>
                    <select name="car_model" required>
                        <option value="">Выберите модель</option>
                        <?php foreach ($carModels as $value => $label): ?>
                            <option value="<?= $value ?>" <?= ($formInput['car_model'] ?? '') == $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errorList['car_model'])): ?><div class="field-error"><?= $errorList['car_model'] ?></div><?php endif; ?>
                </div>

                <div class="form-group <?= isset($errorList['car_color']) ? 'has-error' : '' ?>">
                    <label>Цвет *</label>
                    <div class="radio-group">
                        <?php foreach ($carColors as $color): ?>
                            <label><input type="radio" name="car_color" value="<?= $color ?>" <?= ($formInput['car_color'] ?? '') == $color ? 'checked' : '' ?> required> <?= $color ?></label>
                        <?php endforeach; ?>
                    </div>
                    <?php if (isset($errorList['car_color'])): ?><div class="field-error"><?= $errorList['car_color'] ?></div><?php endif; ?>
                </div>

                <div class="form-group <?= isset($errorList['engine_type']) ? 'has-error' : '' ?>">
                    <label>Тип двигателя *</label>
                    <select name="engine_type" required>
                        <option value="">Выберите</option>
                        <?php foreach ($engineTypes as $type): ?>
                            <option value="<?= $type ?>" <?= ($formInput['engine_type'] ?? '') == $type ? 'selected' : '' ?>><?= $type ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errorList['engine_type'])): ?><div class="field-error"><?= $errorList['engine_type'] ?></div><?php endif; ?>
                </div>

                <div class="form-group <?= isset($errorList['transmission']) ? 'has-error' : '' ?>">
                    <label>Коробка передач *</label>
                    <select name="transmission" required>
                        <option value="">Выберите</option>
                        <?php foreach ($transmissions as $trans): ?>
                            <option value="<?= $trans ?>" <?= ($formInput['transmission'] ?? '') == $trans ? 'selected' : '' ?>><?= $trans ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errorList['transmission'])): ?><div class="field-error"><?= $errorList['transmission'] ?></div><?php endif; ?>
                </div>

                <div class="form-group <?= isset($errorList['drive_type']) ? 'has-error' : '' ?>">
                    <label>Привод *</label>
                    <select name="drive_type" required>
                        <option value="">Выберите</option>
                        <?php foreach ($driveTypes as $drive): ?>
                            <option value="<?= $drive ?>" <?= ($formInput['drive_type'] ?? '') == $drive ? 'selected' : '' ?>><?= $drive ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errorList['drive_type'])): ?><div class="field-error"><?= $errorList['drive_type'] ?></div><?php endif; ?>
                </div>

                <div class="form-group <?= isset($errorList['desired_hp']) ? 'has-error' : '' ?>">
                    <label>Желаемая мощность (л.с.)</label>
                    <input type="number" name="desired_hp" value="<?= htmlspecialchars($formInput['desired_hp'] ?? '') ?>" min="50" max="2000">
                    <?php if (isset($errorList['desired_hp'])): ?><div class="field-error"><?= $errorList['desired_hp'] ?></div><?php endif; ?>
                </div>

                <div class="form-group">
                    <label>Дополнительные опции</label>
                    <div class="options-group">
                        <?php foreach ($carOptionsList as $key => $label): ?>
                            <label><input type="checkbox" name="car_options[]" value="<?= $key ?>" <?= in_array($key, ($formInput['car_options'] ?? [])) ? 'checked' : '' ?>> <?= htmlspecialchars($label) ?></label>
                        <?php endforeach; ?>
                    </div>
                    <?php if (isset($errorList['car_options'])): ?><div class="field-error"><?= $errorList['car_options'] ?></div><?php endif; ?>
                </div>

                <div class="form-group checkbox-group <?= isset($errorList['contract_agreed']) ? 'has-error' : '' ?>">
                    <label><input type="checkbox" name="contract_agreed" <?= ($formInput['contract_agreed'] ?? false) ? 'checked' : '' ?> required> С контрактом ознакомлен(а) *</label>
                    <?php if (isset($errorList['contract_agreed'])): ?><div class="field-error"><?= $errorList['contract_agreed'] ?></div><?php endif; ?>
                </div>

                <button type="submit" class="btn"><?= $isAuthenticated ? 'Обновить данные' : 'Сохранить' ?></button>
            </form>
            <div id="api-message" style="margin-top: 1rem; display: none;"></div>
            <div id="api-credentials" style="margin-top: 1rem; display: none;"></div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-logo">BMW <span>Pink</span></div>
            <ul class="footer-links">
                <li><a href="#">Главная</a></li>
                <li><a href="#models">Модели</a></li>
                <li><a href="#calculator">Калькулятор</a></li>
                <li><a href="#tuning">Тюнинг</a></li>
                <li><a href="#slider">Галерея</a></li>
                <li><a href="#contact">Контакты</a></li>
                <li><a href="#anketa">Анкета</a></li>
                <li><a href="admin.php">Админ</a></li>
            </ul>
            <div class="quote-section">
                <p class="inspiration-quote">«BMWs are about more than just cars. They're about the journey that you make with your heart, mind, and soul» — BMW.</p>
            </div>
            <div class="copyright">© 2025 BMW Pink Project. Все права защищены.</div>
        </div>
    </footer>

    <!-- Modal для связи -->
    <div class="modal" id="contact-modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2>Связь с нами</h2>
            <form id="modal-contact-form">
                <div class="form-group"><label for="modal-name">Ваше имя</label><input type="text" id="modal-name" name="name" required></div>
                <div class="form-group"><label for="modal-phone">Телефон</label><input type="tel" id="modal-phone" name="phone" required></div>
                <div class="form-group"><label for="modal-message">О себе</label><textarea id="modal-message" name="message" placeholder="Расскажите о ваших пожеланиях (минимум 10 символов)..." required></textarea></div>
                <button type="submit" class="btn">Отправить</button>
            </form>
        </div>
    </div>

    <script src="s.js" defer></script>
</body>
</html>