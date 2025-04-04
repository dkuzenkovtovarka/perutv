<?php
// Version 03-04-25
const API_URL = "https://sendmelead.com/api/v3/lead/add";
const OFFER_ID = '88d1c0d1-8574-4b94-a86f-7bee4cf3e30a';
const WEBMASTER_TOKEN = 'ea40186d5802e7fb07d801738af4b056';
const NAME_FIELD = 'name';
const PHONE_FIELD = 'phone';

$urlForNotPost = 'index.php';
$urlForEmptyRequiredFields = 'index.php';
$urlForNotJson = 'index.php';
$urlSuccess = 'success.php';

// ------------------------ Вспомогательные функции ------------------------
function writeToLog(array $data, $response) {
    $log = "<?php /* " . date("F j, Y, g:i a") . " */ ?>" . PHP_EOL .
           "----------- DATA -------------" . PHP_EOL .
           print_r($data, true) . PHP_EOL .
           "----------- RESPONSE ---------" . PHP_EOL .
           $response . PHP_EOL .
           "----------- END --------------" . PHP_EOL;
    file_put_contents('./log_' . date("j.n.Y") . '.php', $log, FILE_APPEND);
}

function getUserIp() {
    if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
        $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
        $_SERVER['HTTP_CLIENT_IP'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
    }
    $client  = @$_SERVER['HTTP_CLIENT_IP'];
    $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
    $remote  = $_SERVER['REMOTE_ADDR'];

    if (filter_var($client, FILTER_VALIDATE_IP)) return $client;
    if (filter_var($forward, FILTER_VALIDATE_IP)) return $forward;
    return $remote;
}

// ------------------------ Проверка curl ------------------------
if (!function_exists('curl_version')) {
    echo "<pre>pls install curl\nsudo apt-get install curl && apt-get install php-curl</pre>";
    die;
}

// ------------------------ Обработка запроса ------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Обработка фоновой записи
$json = file_get_contents("php://input");
$decoded = json_decode($json, true);

if (is_array($decoded) && isset($decoded['background']) && $decoded['background']) {
    // Сохраняем быстро в файл
    $log = date("Y-m-d H:i:s") . " | " . $decoded['name'] . " | " . $decoded['phone'] . "\n";
    file_put_contents("leads_local.txt", $log, FILE_APPEND);
    exit; // Не мешаем основной логике
}

    if (empty($_POST[NAME_FIELD]) || empty($_POST[PHONE_FIELD])) {
        header('Location: ' . $urlForEmptyRequiredFields);
        exit;
    }

    $args = [
        'name' => $_POST[NAME_FIELD],
        'phone' => $_POST[PHONE_FIELD],
        'offerId' => OFFER_ID,
        'domain' => "https://m.facebook.com/?fbclid",
        'ip' => getUserIp(),
        'utm_campaign' => $_POST['utm_campaign'] ?? null,
        'utm_content' => $_POST['utm_content'] ?? null,
        'utm_medium' => $_POST['utm_medium'] ?? null,
        'utm_source' => $_POST['utm_source'] ?? null,
        'utm_term' => $_POST['utm_term'] ?? null,
        'clickid' => $_POST['clickid'] ?? null,
        'fbpx' => $_POST['fbpx'] ?? null,
    ];

    // ✅ 1. Сохраняем локально моментально
    $line = implode(" | ", [
        date("Y-m-d H:i:s"),
        $args['name'],
        $args['phone'],
        $args['clickid'] ?? '',
        $args['fbpx'] ?? ''
    ]) . PHP_EOL;

    file_put_contents('leads_' . date("d-m-Y") . '.txt', $line, FILE_APPEND | LOCK_EX);

    // ✅ 2. Отправляем по API
    $data = json_encode($args);
    $curl = curl_init(API_URL);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data),
            'X-Token: ' . WEBMASTER_TOKEN,
        ],
    ]);

    $response = curl_exec($curl);
    curl_close($curl);

    writeToLog($args, $response);

    // ✅ 3. Редирект
    $parameters = [
        'fbpx' => $args["fbpx"],
        'fio' => $args['name'],
        'name' => $args['name'],
        'phone' => $args['phone']
    ];
    $urlSuccess .= '?' . http_build_query($parameters);

    header('Location: ' . $urlSuccess);
    exit;
} else {
    header('Location: ' . $urlForNotPost);
    exit;
}
?>
