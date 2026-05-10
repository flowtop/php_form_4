<?php
require 'db.php';

header('Content-Type: text/html; charset=UTF-8');

$languages = [
    'Pascal',
    'C',
    'C++',
    'JavaScript',
    'PHP',
    'Python',
    'Haskell',
    'Java',
    'Clojure',
    'Prolog',
    'Scala'
];

$errors = [];
$messages = [];

/*
|--------------------------------------------------------------------------
| ЧТЕНИЕ ОШИБОК ИЗ COOKIE
|--------------------------------------------------------------------------
*/

if (!empty($_COOKIE['errors'])) {
    $errors = json_decode($_COOKIE['errors'], true);

    setcookie('errors', '', time() - 3600);
}

/*
|--------------------------------------------------------------------------
| ЧТЕНИЕ СТАРЫХ ДАННЫХ ИЗ COOKIE
|--------------------------------------------------------------------------
*/

$form_data = [];

if (!empty($_COOKIE['form_data'])) {
    $form_data = json_decode($_COOKIE['form_data'], true);
}

/*
|--------------------------------------------------------------------------
| СООБЩЕНИЕ ОБ УСПЕХЕ
|--------------------------------------------------------------------------
*/

if (!empty($_COOKIE['success'])) {
    $messages[] = $_COOKIE['success'];

    setcookie('success', '', time() - 3600);
}

/*
|--------------------------------------------------------------------------
| ОБРАБОТКА POST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $birth_date = trim($_POST['birth_date'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $selected_languages = $_POST['languages'] ?? [];
    $biography = trim($_POST['biography'] ?? '');
    $contract = isset($_POST['contract']);

    $form_data = [
        'full_name' => $full_name,
        'phone' => $phone,
        'email' => $email,
        'birth_date' => $birth_date,
        'gender' => $gender,
        'languages' => $selected_languages,
        'biography' => $biography
    ];

    /*
    |--------------------------------------------------------------------------
    | ВАЛИДАЦИЯ
    |--------------------------------------------------------------------------
    */

    // ФИО
    if (empty($full_name)) {
        $errors['full_name'] = 'Заполните ФИО.';
    } elseif (!preg_match('/^[a-zA-Zа-яА-ЯёЁ\s]+$/u', $full_name)) {
        $errors['full_name'] =
            'ФИО может содержать только буквы русского/латинского алфавита и пробелы.';
    } elseif (strlen($full_name) > 150) {
        $errors['full_name'] =
            'ФИО не должно превышать 150 символов.';
    }

    // Телефон
    if (empty($phone)) {
        $errors['phone'] = 'Введите телефон.';
    } elseif (!preg_match('/^\+?[0-9\s\-]+$/', $phone)) {
        $errors['phone'] =
            'Телефон может содержать только цифры, пробелы, + и дефис.';
    }

    // Email
    if (empty($email)) {
        $errors['email'] = 'Введите email.';
    } elseif (!preg_match('/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/', $email)) {
        $errors['email'] =
            'Email содержит недопустимые символы.';
    }

    // Дата рождения
    if (empty($birth_date)) {
        $errors['birth_date'] = 'Укажите дату рождения.';
    }

    // Пол
    if (!in_array($gender, ['male', 'female'])) {
        $errors['gender'] = 'Выберите корректный пол.';
    }

    // Языки
    if (empty($selected_languages)) {
        $errors['languages'] =
            'Выберите хотя бы один язык программирования.';
    } else {

        foreach ($selected_languages as $lang) {

            if (!in_array($lang, $languages)) {

                $errors['languages'] =
                    'Выбран недопустимый язык программирования.';

                break;
            }
        }
    }

    // Биография
    if (empty($biography)) {
        $errors['biography'] = 'Введите биографию.';
    }

    // Контракт
    if (!$contract) {
        $errors['contract'] =
            'Необходимо ознакомиться с контрактом.';
    }

    /*
    |--------------------------------------------------------------------------
    | ЕСЛИ ЕСТЬ ОШИБКИ
    |--------------------------------------------------------------------------
    */

    if (!empty($errors)) {

        setcookie(
            'errors',
            json_encode($errors),
            0
        );

        setcookie(
            'form_data',
            json_encode($form_data),
            0
        );

        header('Location: index.php');
        exit();
    }

    /*
    |--------------------------------------------------------------------------
    | СОХРАНЕНИЕ В БАЗУ
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        INSERT INTO applications
        (full_name, phone, email, birth_date, gender, biography, contract_accepted)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $full_name,
        $phone,
        $email,
        $birth_date,
        $gender,
        $biography,
        1
    ]);

    $application_id = $pdo->lastInsertId();

    foreach ($selected_languages as $lang_name) {

        $stmt = $pdo->prepare("
            SELECT id FROM programming_languages
            WHERE name = ?
        ");

        $stmt->execute([$lang_name]);

        $language_id = $stmt->fetchColumn();

        $stmt = $pdo->prepare("
            INSERT INTO application_languages
            (application_id, language_id)
            VALUES (?, ?)
        ");

        $stmt->execute([
            $application_id,
            $language_id
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | СОХРАНЕНИЕ ДАННЫХ НА 1 ГОД
    |--------------------------------------------------------------------------
    */

    setcookie(
        'form_data',
        json_encode($form_data),
        time() + 60 * 60 * 24 * 365
    );

    setcookie(
        'success',
        'Данные успешно сохранены.',
        0
    );

    header('Location: index.php');
    exit();
}

function field_error($field, $errors)
{
    return isset($errors[$field])
        ? 'style="border:2px solid red;"'
        : '';
}

?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Форма</title>
    <link rel="stylesheet" href="./css/style.css">
</head>

<body>
    <header class="header">
        <div class="header__container container">
            <h1>Отправка формы. Cookies</h1>
        </div>
    </header>

    <?php foreach ($messages as $message): ?>
    <div style="color:green; margin-bottom:15px;">
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endforeach; ?>

    <main>
        <div class="main__container container">
            <form action="" method="POST">


                <input type="text" name="full_name" placeholder="ФИО"
                    value="<?= htmlspecialchars($form_data['full_name'] ?? '') ?>" <?=field_error('full_name', $errors)
                    ?>
                >

                <?php if (isset($errors['full_name'])): ?>
                <div style="color:red;">
                    <?= htmlspecialchars($errors['full_name']) ?>
                </div>
                <?php endif; ?>


                <input type="text" name="phone" placeholder="Номер телефона"
                    value="<?= htmlspecialchars($form_data['phone'] ?? '') ?>" <?=field_error('phone', $errors) ?>
                >

                <?php if (isset($errors['phone'])): ?>
                <div style="color:red;">
                    <?= htmlspecialchars($errors['phone']) ?>
                </div>
                <?php endif; ?>

                <input type="text" name="email" placeholder="Email"
                    value="<?= htmlspecialchars($form_data['email'] ?? '') ?>" <?=field_error('email', $errors) ?>
                >

                <?php if (isset($errors['email'])): ?>
                <div style="color:red;">
                    <?= htmlspecialchars($errors['email']) ?>
                </div>
                <?php endif; ?>

                <p>
                    Дата рождения:<br>

                    <input type="date" name="birth_date" value="<?= htmlspecialchars($form_data['birth_date'] ?? '') ?>"
                        <?=field_error('birth_date', $errors) ?>
                    >

                    <?php if (isset($errors['birth_date'])): ?>
                <div style="color:red;">
                    <?= htmlspecialchars($errors['birth_date']) ?>
                </div>
                <?php endif; ?>
                </p>

                <p>
                    Пол:<br>

                    <label class="radio_btn">
                        <input type="radio" name="gender" value="male" <?=(($form_data['gender'] ?? '' )==='male' )
                            ? 'checked' : '' ?>
                        >
                        Мужской
                    </label>

                    <label class="radio_btn">
                        <input type="radio" name="gender" value="female" <?=(($form_data['gender'] ?? '' )==='female' )
                            ? 'checked' : '' ?>
                        >
                        Женский
                    </label>

                    <?php if (isset($errors['gender'])): ?>
                <div style="color:red;">
                    <?= htmlspecialchars($errors['gender']) ?>
                </div>
                <?php endif; ?>
                </p>

                <p>
                    Любимые ЯП:<br>

                    <select name="languages[]" multiple size="11" <?=field_error('languages', $errors) ?>
                        >

                        <?php foreach ($languages as $lang): ?>

                        <option value="<?= htmlspecialchars($lang) ?>" <?=( isset($form_data['languages']) &&
                            in_array($lang, $form_data['languages']) ) ? 'selected' : '' ?>
                            >
                            <?= htmlspecialchars($lang) ?>
                        </option>

                        <?php endforeach; ?>

                    </select>

                    <?php if (isset($errors['languages'])): ?>
                <div style="color:red;">
                    <?= htmlspecialchars($errors['languages']) ?>
                </div>
                <?php endif; ?>
                </p>

                <p>
                    Биография:<br>

                    <textarea name="biography" rows="6" cols="40" <?=field_error('biography', $errors) ?>
        ><?= htmlspecialchars($form_data['biography'] ?? '') ?></textarea>

                    <?php if (isset($errors['biography'])): ?>
                <div style="color:red;">
                    <?= htmlspecialchars($errors['biography']) ?>
                </div>
                <?php endif; ?>
                </p>

                <p>

                    <label form_checkbox>
                        <input type="checkbox" name="contract">
                        С контрактом ознакомлен
                    </label>

                    <?php if (isset($errors['contract'])): ?>
                <div style="color:red;">
                    <?= htmlspecialchars($errors['contract']) ?>
                </div>
                <?php endif; ?>
                </p>

                <button type="submit">
                    Отправить
                </button>

            </form>
        </div>
    </main>

</body>

</html>