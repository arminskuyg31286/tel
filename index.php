<?php

if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
} elseif (function_exists('litespeed_finish_request')) {
    litespeed_finish_request();
} else {
    error_log('Neither fastcgi_finish_request nor litespeed_finish_request is available.');
}

ini_set('error_log', 'error_log');
date_default_timezone_set('Asia/Tehran');

require_once 'config.php';
require_once 'jdf.php';
require_once 'text.php';
require_once 'keyboard.php';
require_once 'functions.php';
require_once 'panels.php';
require_once 'vendor/autoload.php';

use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;

if (is_dir('installer')) {
    deleteFolder('installer');
}

$first_name = sanitizeUserName($first_name);

if (!in_array($Chat_type, ["private"])) {
    return;
}

if (!checktelegramip()) {
    die("Unauthorized access");
}

#-------------Variable----------#

$users_ids = select("user", "id", null, null, "FETCH_COLUMN");
$setting   = select("setting", "*");
if (intval($from_id) != 0 && !in_array($from_id, $users_ids)) {
    $newuser = sprintf($textbotlang['Admin']['ManageUser']['NewUserMessage'], $first_name, $username, $from_id, $from_id);
    if (!empty($setting['Channel_Report'])) {
        sendmessage($setting['Channel_Report'], $newuser, null, 'HTML');
    }
}

if (intval($from_id) != 0) {
    $verify = (intval($setting['status_verify']) == 1) ? 0 : 1;
    do {
        $ref_code = random_int(1000000000, 9999999999);
        $stmt_check = $pdo->prepare("SELECT 1 FROM user WHERE ref_code = :ref_code");
        $stmt_check->bindParam(':ref_code', $ref_code);
        $stmt_check->execute();
    } while ($stmt_check->fetchColumn());
    $stmt = $pdo->prepare("INSERT IGNORE INTO user (id, ref_code, step, limit_usertest, User_Status, number, Balance, discount_number, username, message_count, last_message_time, affiliatescount, affiliates, verify) VALUES (:from_id, :ref_code, 'none', :limit_usertest_all, 'Active', 'none', '0', '0', :username, '0', '0', '0', '0', :verify)");
    $stmt->bindParam(':ref_code', $ref_code);
    $stmt->bindParam(':verify', $verify);
    $stmt->bindParam(':from_id', $from_id);
    $stmt->bindParam(':limit_usertest_all', $setting['limit_usertest_all']);
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->execute();
}

$user = select("user", "*", "id", $from_id, "select") ?: [
    'step' => '',
    'Processing_value' => '',
    'User_Status' => '',
    'username' => '',
    'limit_usertest' => '',
    'last_message_time' => '',
    'affiliates' => '',
];

if ($setting['status_verify'] == "1" && intval($user['verify']) == 0 && !in_array($from_id, $admin_ids)) {
    sendmessage($from_id, $textbotlang['users']['VerifyUser'], null, 'html');
    return;
}

$helpdata           = select("help", "*");
$datatextbotget     = select("textbot", "*", null, null, "fetchAll");
$id_invoice         = select("invoice", "id_invoice", null, null, "FETCH_COLUMN");
$channels           = select("channels", "*");
$usernameinvoice    = select("invoice", "username", null, null, "FETCH_COLUMN");
$code_Discount      = select("Discount", "code", null, null, "FETCH_COLUMN");
$users_ids          = select("user", "id", null, null, "FETCH_COLUMN");
$marzban_list       = select("marzban_panel", "name_panel", null, null, "FETCH_COLUMN");
$name_product       = select("product", "name_product", null, null, "FETCH_COLUMN");
$SellDiscount       = select("DiscountSell", "codeDiscount", null, null, "FETCH_COLUMN");

$ManagePanel = new ManagePanel();

$datatxtbot = [];
foreach ($datatextbotget as $row) {
    $datatxtbot[] = [
        'id_text' => $row['id_text'],
        'text'    => $row['text']
    ];
}

$datatextbot = [
    'text_Add_Balance'     => '',
    'text_Discount'        => '',
    'text_Purchased_services' => '',
    'text_account'         => '',
    'text_bot_off'         => '',
    'text_channel'         => '',
    'text_help'            => '',
    'text_sell'            => '',
    'text_start'           => '',
    'text_support'         => '',
    'text_support_qs'      => '',
    'text_usertest'        => '',
    'text_myid'            => '',
    'text_affiliates'      => '',
    'text_linkapp'         => '',
    'text_inline_key'      => '',
    'text_BalanceBuy'      => '',
    'text_discountcode'    => '',
    'text_freetest'        => '',
    'text_info_test'       => '',
];

foreach ($datatxtbot as $item) {
    if (isset($datatextbot[$item['id_text']])) {
        $datatextbot[$item['id_text']] = $item['text'];
    }
}

if (function_exists('shell_exec') && is_callable('shell_exec')) {
    $existingCronCommands = shell_exec('crontab -l');
    $phpFilePath = "https://$domainhosts/cron/sendmessage.php";
    $cronCommand = "*/1 * * * * curl $phpFilePath";
    if (strpos($existingCronCommands, $cronCommand) === false) {
        shell_exec("(crontab -l ; echo '$cronCommand') | crontab -");
    }
}

if (empty($user['username']) || $user['username'] === "none") {
    update("user", "username", $username, "id", $from_id);
}

if ($user['User_Status'] === "block") {
    $textblock = sprintf($textbotlang['Admin']['ManageUser']['BlockedUser'], $user['description_blocking']);
    sendmessage($from_id, $textblock, null, 'html');
    return;
}

if ($from_id) {
    $result = $connect->query("SELECT inline_keyboard FROM setting LIMIT 1");
    $settings = $result->fetch_assoc();
    define('USER_INLINE_KEYBOARD', $settings['inline_keyboard'] === 'on');
}

if (strpos($text, "/start ") !== false) {
    if ($user['affiliates'] != 0) {
        sendmessage($from_id, $datatextbot['text_start'], $keyboard, 'html');
        return;
    }
    $token = str_replace("/start ", "", $text);
    $refRow = select("user", "id", "ref_code", $token, "select");
    if ($refRow !== false) {
        $affiliatesid = $refRow['id'];
    } elseif (ctype_digit($token)) {
        $affiliatesid = (int) $token;
    } else {
        $affiliatesid = 0;
    }
    if (ctype_digit($affiliatesid)) {
        if (!in_array($affiliatesid, $users_ids)) {
            sendmessage($from_id, $textbotlang['users']['affiliates']['affiliatesyou'], null, 'html');
            return;
        }
        if ($affiliatesid == $from_id) {
            sendmessage($from_id, $datatextbot['text_start'], $keyboard, 'html');
            return;
        }
        $inviterData = select("user", "affiliates", "id", $affiliatesid, "select");
        if ($inviterData && intval($inviterData['affiliates']) === intval($from_id)) {
            sendmessage($from_id, $textbotlang['users']['affiliates']['invalidMutual'], null, 'html');
            return;
        }
        $affiliatesDiscount = select("affiliates", "*", null, null, "select");
        if ($affiliatesDiscount['Discount'] === "onDiscountaffiliates") {
            $Balance_user       = select("user", "*", "id", $affiliatesid, "select");
            $Balance_add_user   = $Balance_user['Balance'] + $affiliatesDiscount['price_Discount'];
            update("user", "Balance", $Balance_add_user, "id", $affiliatesid);
            $addbalancediscount = number_format($affiliatesDiscount['price_Discount'], 0);
            sendmessage($affiliatesid, sprintf($textbotlang['users']['affiliates']['giftuser'], $addbalancediscount), null, 'html');
        }
        sendmessage($from_id, $datatextbot['text_start'], $keyboard, 'html');
        $useraffiliates    = select("user", "*", "id", $affiliatesid, "select");
        $addcountaffiliates = intval($useraffiliates['affiliatescount']) + 1;
        update("user", "affiliates", $affiliatesid, "id", $from_id);
        update("user", "affiliatescount", $addcountaffiliates, "id", $affiliatesid);
    }
}

$timebot = time();
$TimeLastMessage = $timebot - intval($user['last_message_time']);
if (floor($TimeLastMessage / 60) >= 1) {
    update("user", "last_message_time", $timebot, "id", $from_id);
    update("user", "message_count", "1", "id", $from_id);
} else {
    if (!in_array($from_id, $admin_ids)) {
        $addmessage = intval($user['message_count']) + 1;
        update("user", "message_count", $addmessage, "id", $from_id);
        if ($user['message_count'] >= 30) {
            $User_Status = "block";
            update("user", "User_Status", $User_Status, "id", $from_id);
            update("user", "description_blocking", $textbotlang['users']['spamtext'], "id", $from_id);
            return;
        }
    }
    if ($setting['Bot_Status'] === "✅  ربات روشن است" && !in_array($from_id, $admin_ids)) {
        sendmessage($from_id, $textbotlang['users']['updatingbot'], null, 'html');
        return;
    }
}

#----------- Channel ------------#
if ($channels && isset($channels['link'])) {
    $chanelcheck = channel($channels['link']);
    if (!is_array($chanelcheck)) {
        $chanelcheck = [];
    }
} else {
    $chanelcheck = [];
}

if ($datain == "confirmchannel") {
    if (!empty($chanelcheck) && !in_array($from_id, $admin_ids)) {
        alert($textbotlang['users']['channel']['notconfirmed'], true);
    } else {
        deletemessage($from_id, $message_id);
        sendmessage($from_id, $textbotlang['users']['channel']['confirmed'], $keyboard, 'html');
    }
    return;
} elseif (!empty($chanelcheck) && !in_array($from_id, $admin_ids)) {
    $link_channel = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['channel']['text_join'], 'url' => "https://t.me/" . $chanelcheck[0], 'style' => 'success'],
            ],
            [
                ['text' => $textbotlang['users']['channel']['confirmjoin'], 'callback_data' => "confirmchannel", 'style' => 'primary'],
            ],
        ]
    ]);
    sendmessage($from_id, $datatextbot['text_channel'], $link_channel, 'html');
    return;
}

#----------- Bot Status -----------#
if ($setting['Bot_Status'] == "0" && !in_array($from_id, $admin_ids)) {
    sendmessage($from_id, $datatextbot['text_bot_off'], null, 'html');
    return;
}

#----------- /start Command -----------#
if ($text == $textbotlang['users']['backtominmenu'] || $text == "/start") {
    update("user", "Processing_value", "0", "id", $from_id);
    update("user", "Processing_value_one", "0", "id", $from_id);
    update("user", "Processing_value_tow", "0", "id", $from_id);
    if (USER_INLINE_KEYBOARD) {
        $keyboard_empty = json_encode(['remove_keyboard' => true]);
        $sent = sendmessage($from_id, "⌛️", $keyboard_empty);
        $sent_message_id = $sent['result']['message_id'];
        deletemessage($from_id, $sent_message_id);
        sendmessage($from_id, $datatextbot['text_start'], $keyboard, 'HTML');
    } else {
        sendmessage($from_id, $datatextbot['text_start'], $keyboard, 'HTML');
    }
    step('home', $from_id);
    return;
}

#----------- Back Command -----------#
if ($text == $textbotlang['users']['backmenu'] || $datain == "backuser") {
    update("user", "Processing_value", "0", "id", $from_id);
    update("user", "Processing_value_one", "0", "id", $from_id);
    update("user", "Processing_value_tow", "0", "id", $from_id);
    if (USER_INLINE_KEYBOARD) {
        Editmessagetext($from_id, $message_id, $datatextbot['text_start'], $keyboard);
    } else {
        sendmessage($from_id, $datatextbot['text_start'], $keyboard, 'html');
    }
    step('home', $from_id);
    return;
}

#----------- Back to Panel List -----------#
if ($datain == "backtopelan") {
    $panellist = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    if ($panellist && is_array($panellist)) {
        $keyboardPanel = KeyboardProduct($panellist['name_panel'], "buy", $panellist['MethodUsername']);
        $textPanel = sprintf($textbotlang['users']['buy']['selectService'], $panellist['name_panel']);
        Editmessagetext($from_id, $message_id, $textPanel, $keyboardPanel);
    } else {
        alert("در حال حاضر هیچ سرور فعالی وجود ندارد", false);
    }
}

#----------- Get Number -----------#
if ($user['step'] === 'get_number') {
    if (empty($user_phone)) {
        sendmessage($from_id, $textbotlang['users']['number']['false'], $request_contact, 'html');
        return;
    }
    if ($contact_id != $from_id) {
        sendmessage($from_id, $textbotlang['users']['number']['Warning'], $request_contact, 'html');
        return;
    }
    if (USER_INLINE_KEYBOARD) {
        $keyboard_empty = json_encode(['remove_keyboard' => true]);
        $sent = sendmessage($from_id, "⌛️", $keyboard_empty);
        $sent_message_id = $sent['result']['message_id'];
        deletemessage($from_id, $sent_message_id);
        sendmessage($from_id, $textbotlang['users']['number']['active'], $keyboard, 'html');
    } else {
        sendmessage($from_id, $textbotlang['users']['number']['active'], $keyboard, 'html');
    }
    update("user", "number", $user_phone, "id", $from_id);
    step('home', $from_id);
}

#-----------Purchased services------------#
if ($text == $datatextbot['text_Purchased_services'] || $datain == "services" || $text == "/services") {
    $keyboard = [
        'inline_keyboard' => [
            [['text' => "V2Ray", 'callback_data' => "show_services_v2ray", 'style' => 'primary']]
        ]
    ];
    if (USER_INLINE_KEYBOARD) {
        $keyboard['inline_keyboard'][] = [
            ['text' => $textbotlang['users']['backmenu'], 'callback_data' => "backuser", 'style' => 'primary']
        ];
        $keyboard_json = json_encode($keyboard, JSON_UNESCAPED_UNICODE);
        Editmessagetext($from_id, $message_id, $textbotlang['users']['Service']['InfoService'], $keyboard_json);
    } else {
        $keyboard_json = json_encode($keyboard, JSON_UNESCAPED_UNICODE);
        sendmessage($from_id, $textbotlang['users']['Service']['InfoService'], $keyboard_json);
    }
} elseif ($datain == "back_to_services") {
    $keyboard = ['inline_keyboard' => [[['text' => "V2Ray", 'callback_data' => "show_services_v2ray", 'style' => 'primary']]]];
    if (USER_INLINE_KEYBOARD) {
        $keyboard['inline_keyboard'][] = [['text' => $textbotlang['users']['backmenu'], 'callback_data' => "backuser", 'style' => 'primary']];
    }
    $keyboard_json = json_encode($keyboard, JSON_UNESCAPED_UNICODE);
    Editmessagetext($from_id, $message_id, $textbotlang['users']['Service']['InfoService'], $keyboard_json);
} elseif ($datain == "back_to_services") {
    $keyboard = ['inline_keyboard' => [[['text' => "V2Ray", 'callback_data' => "show_services_v2ray", 'style' => 'primary']]]];
    $keyboard_json = json_encode($keyboard);
    Editmessagetext($from_id, $message_id, $textbotlang['users']['Service']['InfoService'], $keyboard_json);
} elseif ($datain == "show_services_v2ray" || $datain == "backorder") {
    $stmt = $pdo->prepare("SELECT * FROM invoice WHERE id_user = :id_user AND status IN ('active', 'end_of_time', 'end_of_volume', 'sendedwarn') ORDER BY time_sell DESC");
    $stmt->bindParam(':id_user', $from_id);
    $stmt->execute();
    $invoices = $stmt->rowCount();
    if ($invoices == 0) {
        Editmessagetext($from_id, $message_id, $textbotlang['users']['sell']['service_not_available'], null);
        return;
    }
    $keyboardlists = ['inline_keyboard' => []];
    $all_buttons = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $all_buttons[] = ['text' => $row['username'], 'callback_data' => "product_" . $row['username']];
    }
    $total_buttons = count($all_buttons);
    if ($total_buttons == 0) {
    } elseif ($total_buttons == 1) {
        $keyboardlists['inline_keyboard'][] = [$all_buttons[0]];
    } else {
        if ($total_buttons % 2 != 0) {
            $keyboardlists['inline_keyboard'][] = [$all_buttons[0]];
            $remaining = array_slice($all_buttons, 1);
        } else {
            $remaining = $all_buttons;
        }
        $keyboard_row = [];
        foreach ($remaining as $btn) {
            $keyboard_row[] = $btn;
            if (count($keyboard_row) == 2) {
                $keyboardlists['inline_keyboard'][] = $keyboard_row;
                $keyboard_row = [];
            }
        }
        if (!empty($keyboard_row)) {
            $keyboardlists['inline_keyboard'][] = $keyboard_row;
        }
    }
    if ($setting['NotUser'] == "1") {
        $keyboardlists['inline_keyboard'][] = [
            ['text' => $textbotlang['Admin']['Status']['notusenameinbot'], 'callback_data' => 'usernotlist', 'style' => 'primary'],
            ['text' => $textbotlang['users']['backmenu'], 'callback_data' => "back_to_services", 'style' => 'primary']
        ];
    } else {
        $keyboardlists['inline_keyboard'][] = [
            ['text' => $textbotlang['users']['backmenu'], 'callback_data' => "back_to_services", 'style' => 'primary']
        ];
    }
    $keyboard_json = json_encode($keyboardlists);
    Editmessagetext($from_id, $message_id, $textbotlang['users']['sell']['service_sell'], $keyboard_json);
} elseif ($datain == "usernotlist") {
    sendmessage($from_id, $textbotlang['users']['stateus']['SendUsername'], $backuser, 'html');
    step('getusernameinfo', $from_id);
} elseif ($user['step'] == "getusernameinfo") {
    if (!preg_match('/^\w{3,32}$/', $text)) {
        sendmessage($from_id, $textbotlang['users']['stateus']['Invalidusername'], $backuser, 'html');
        return;
    }
    $stmt = $pdo->prepare("SELECT * FROM invoice WHERE username = :username AND id_user = :id_user AND status IN ('active','end_of_time','end_of_volume','sendedwarn') LIMIT 1");
    $stmt->execute([':username' => $text, ':id_user' => $from_id]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$invoice) {
        sendmessage($from_id, $textbotlang['users']['stateus']['Invalidusername'], $backuser, 'html');
        return;
    }
    sendmessage($from_id, "سفارش {$text} یافت شد:", $keyboard, 'html');
    update("user", "Processing_value", $text, "id", $from_id);
    $keyboardlists = [
        'inline_keyboard' => [[['text' => $text, 'callback_data' => "product_" . $text]], [['text' => $textbotlang['users']['backmenu'], 'callback_data' => "back_to_services", 'style' => 'primary']]]
    ];
    $keyboard_json = json_encode($keyboardlists, JSON_UNESCAPED_UNICODE);
    sendmessage($from_id, $textbotlang['users']['sell']['service_sell'], $keyboard_json, 'html');
    step('home', $from_id);
} elseif (preg_match('/product_(\w+)/', $datain, $dataget)) {
    $username = $dataget[1];
    $nameloc  = select("invoice", "*", "username", $username, "select");
    if (!$nameloc) {
        alert($textbotlang['users']['stateus']['usernotfound'], true);
        return;
    }
    $DataUserOut = $ManagePanel->DataUser($nameloc['Service_location'], $username);
    if (isset($DataUserOut['msg']) && $DataUserOut['msg'] == "User not found") {
        alert($textbotlang['users']['stateus']['usernotfound'], true);
        update("invoice", "Status", "usernotfound", "id_invoice", $nameloc['id_invoice']);
        return;
    }
    if (($DataUserOut['status'] ?? null) == "Unsuccessful") {
        alert($textbotlang['users']['stateus']['error'], true);
        return;
    }
    $expireRaw = $DataUserOut['expire'] ?? null;
    $expireTime = null;
    if ($expireRaw !== null) {
        if (is_string($expireRaw) && (strpos($expireRaw, 'T') !== false || strpos($expireRaw, '-') !== false)) {
            $expireTime = strtotime($expireRaw);
        } elseif (is_numeric($expireRaw)) {
            $expireTime = (int)$expireRaw;
        }
    }
    if (($DataUserOut['online_at'] ?? null) == "online") {
        $lastonline = $textbotlang['users']['online'];
    } elseif (($DataUserOut['online_at'] ?? null) == "offline") {
        $lastonline = $textbotlang['users']['offline'];
    } elseif (!empty($DataUserOut['online_at'])) {
        $lastonline = jdate('Y/m/d h:i:s', strtotime($DataUserOut['online_at']));
    } else {
        $lastonline = $textbotlang['users']['stateus']['notconnected'];
    }
    $status = $DataUserOut['status'] ?? '';
    $statusMap = [
        'active' => $textbotlang['users']['stateus']['active'],
        'limited' => $textbotlang['users']['stateus']['limited'],
        'disabled' => $textbotlang['users']['stateus']['disabled'],
        'expired' => $textbotlang['users']['stateus']['expired'],
        'on_hold' => $textbotlang['users']['stateus']['onhold'],
    ];
    $status_var = $statusMap[$status] ?? $status;
    $dataLimit = $DataUserOut['data_limit'] ?? 0;
    $usedTraffic = $DataUserOut['used_traffic'] ?? 0;
    $expirationDate = $expireTime ? jdate('H:i d-m-Y', $expireTime) : $textbotlang['users']['stateus']['Unlimited'];
    $LastTraffic = $dataLimit ? formatBytes($dataLimit) : $textbotlang['users']['stateus']['Unlimited'];
    $RemainingVolume = $dataLimit ? formatBytes(max(0, $dataLimit - $usedTraffic)) : $textbotlang['users']['unlimited'];
    $usedTrafficGb = $usedTraffic ? formatBytes($usedTraffic) : $textbotlang['users']['stateus']['Notconsumed'];
    if ($expireTime) {
        $timeDiff = $expireTime - time();
        $daysLeft = floor($timeDiff / 86400) + 1;
        $day = ($daysLeft > 0 ? $daysLeft : 0) . " " . $textbotlang['users']['stateus']['day'];
    } else {
        $day = $textbotlang['users']['stateus']['Unlimited'];
    }
    $subscriptionUrl = $DataUserOut['subscription_url'] ?? '';
    $rows = [];
    if (filter_var($subscriptionUrl, FILTER_VALIDATE_URL)) {
        $rows[] = [['text' => "📂 آموزش اتصال", 'style' => 'primary', 'url' => $subscriptionUrl]];
    }
    $rows[] = [
        ['text' => $textbotlang['users']['changelink']['btntitle'], 'callback_data' => 'changelink_' . $username, 'style' => 'primary']
    ];
    $rows[] = [
        ['text' => $textbotlang['users']['stateus']['config'], 'callback_data' => 'config_' . $username, 'style' => 'primary'],
        ['text' => $textbotlang['users']['stateus']['qrcode'], 'callback_data' => 'crqrcode_' . $username, 'style' => 'primary'],
    ];
    $rows[] = [
        ['text' => "💎 نمایش حجم سرویس", 'callback_data' => 'configused_' . $username, 'style' => 'primary']
    ];

    // HWID
    $hwidInfo = getUserHWIDStatus($nameloc['Service_location'], $username);
    $hwidText = "🛡️ دستگاه: " . $hwidInfo['text'];
    $rows[] = [
        ['text' => $hwidText, 'callback_data' => 'show_hwids_' . $username, 'style' => 'primary']
    ];

    $rows[] = [
        ['text' => "⏰ انقضا: {$expirationDate}", 'callback_data' => 'show_expire_' . $username, 'style' => 'primary']
    ];
    $rows[] = [
        ['text' => $textbotlang['users']['extend']['title'], 'callback_data' => 'extend_' . $username, 'style' => 'primary']
    ];
    $rows[] = [
        ['text' => $textbotlang['users']['stateus']['backlist'], 'callback_data' => 'backorder', 'style' => 'primary'],
        ['text' => $textbotlang['users']['stateus']['RemoveSerivecbtn'], 'callback_data' => 'removebywarn_' . $username, 'style' => 'danger'],
    ];
    $keyboardsetting = json_encode(['inline_keyboard' => $rows]);
    $textinfo = sprintf(
        $textbotlang['users']['stateus']['InfoSerivceActive'],
        $nameloc['name_product'],
        $nameloc['Service_location'],
        $day,
        $status_var,
        $DataUserOut['username'],
        $subscriptionUrl
    );
    editmessagetext($from_id, $message_id, $textinfo, $keyboardsetting, 'HTML');
} elseif (preg_match('/^show_hwids_(.+)$/', $datain, $matches)) {
    $username = urldecode($matches[1]);
    $invoice = select("invoice", "*", "username", $username, "select");
    $location = $invoice['Service_location'] ?? '';
    $hwidInfo = getUserHWIDStatus($location, $username);
    $text = "🛡️ وضعیت دستگاه‌های متصل\n\n";
    $text .= "👤 کاربر: {$username}\n\n";
    $text .= "📊 تعداد دستگاه ثبت شده: {$hwidInfo['used']} از {$hwidInfo['limit']}\n\n";
    if ($hwidInfo['used'] >= $hwidInfo['limit']) {
        $text .= "⚠️ شما به حداکثر تعداد دستگاه مجاز رسیده‌اید.\n";
        $text .= "اتصال دستگاه جدید باعث غیرفعال شدن حساب خواهد شد.";
    } else {
        $text .= "✅ شما هنوز می‌توانید " . ($hwidInfo['limit'] - $hwidInfo['used']) . " دستگاه دیگر اضافه کنید.";
    }
    alert($text, true);
} elseif (preg_match('/^show_expire_(.+)$/', $datain, $matches)) {
    $username = urldecode($matches[1]);
    $invoice = select("invoice", "*", "username", $username, "select");
    $location = $invoice['Service_location'] ?? '';
    $userData = $ManagePanel->DataUser($location, $username);
    $expireRaw = $userData['expire'] ?? null;
    $expireTime = null;
    if ($expireRaw !== null) {
        if (is_string($expireRaw)) {
            $expireTime = strtotime($expireRaw);
        } elseif (is_numeric($expireRaw)) {
            $expireTime = (int)$expireRaw;
        }
    }
    $text = "⏰ اطلاعات انقضا\n\n";
    $text .= "👤 کاربر: {$username}\n";
    $text .= "📅 تاریخ انقضا: " . ($expireTime ? jdate('Y/m/d H:i', $expireTime) : 'نامحدود') . "\n";
    $text .= "⏳ روزهای باقی‌مانده: " . ($expireTime ? max(0, floor(($expireTime - time()) / 86400) + 1) : 'نامحدود') . " روز";
    alert($text, true);
}

if (preg_match('/crqrcode_(\w+)/', $datain, $matches)) {
    $username    = $matches[1];
    $invoiceData = select("invoice", "*", "username", $username, "select");
    alert("⏳ در حال ساخت کیوآر کد...", false);
    if (!$invoiceData) {
        alert($textbotlang['users']['stateus']['usernotfound'], true);
        return;
    }
    $userData = $ManagePanel->DataUser($invoiceData['Service_location'], $username);
    $subscriptionUrl = $userData['subscription_url'] ?? null;
    if (empty($subscriptionUrl)) {
        alert("❌ لینک سابسکریپشن یافت نشد.", true);
        return;
    }
    $fileName = rtrim(sys_get_temp_dir(), '/') . "/qr_{$from_id}_" . bin2hex(random_bytes(4)) . ".png";
    try {
        $writer = new PngWriter();
        $qrCode = QrCode::create($subscriptionUrl)
            ->setEncoding(new Encoding('UTF-8'))
            ->setErrorCorrectionLevel(ErrorCorrectionLevel::Low)
            ->setSize(400)
            ->setMargin(10)
            ->setRoundBlockSizeMode(RoundBlockSizeMode::Margin);
        $writer->write($qrCode, null, null)->saveToFile($fileName);
        $caption  = "🔳 <b>کیوآر کد سابسکریپشن</b>\n";
        $caption .= "━━━━━━━━━━━━━━━━━━\n";
        $caption .= "👤 کاربر: <b>{$username}</b>\n\n";
        $caption .= "📱 با اسکن این کد توسط اپلیکیشن خودتون، سرویس به‌صورت خودکار اضافه می‌شود.";
        telegram('sendphoto', [
            'chat_id'      => $from_id,
            'photo'        => new CURLFile($fileName),
            'caption'      => $caption,
            'parse_mode'   => 'HTML',
        ]);
    } catch (\Throwable $e) {
        alert("❌ خطا در ساخت کیوآر کد، لطفاً دوباره تلاش کنید.", true);
    } finally {
        if (file_exists($fileName)) {
            unlink($fileName);
        }
    }
} elseif (preg_match('/configused_(\w+)/', $datain, $matches)) {
    $username    = $matches[1];
    $invoiceData = select("invoice", "*", "username", $username, "select");
    if (!$invoiceData) {
        alert($textbotlang['users']['stateus']['usernotfound'], true);
        return;
    }
    alert("⏳ در حال محاسبه حجم باقی‌مانده...", false);
    $userData = $ManagePanel->DataUser($invoiceData['Service_location'], $username);
    if (empty($userData) || !is_array($userData)) {
        editmessagetext($from_id, $message_id, "❌ این کاربر یافت نشد.", json_encode([
            'inline_keyboard' => [[
                ['text' => $textbotlang['users']['backmenu'], 'callback_data' => "product_" . urlencode($username), 'style' => 'primary']
            ]]
        ]), 'HTML');
        return;
    }
    $dataLimit   = isset($userData['data_limit']) ? (float)$userData['data_limit'] : 0;
    $usedTraffic = isset($userData['used_traffic']) ? (float)$userData['used_traffic'] : 0;
    $totalVolume     = $dataLimit ? formatBytes($dataLimit) : $textbotlang['users']['unlimited'];
    $usedVolume      = $usedTraffic ? formatBytes($usedTraffic) : "0 بایت";
    $remainingVolume = $dataLimit ? formatBytes(max(0, $dataLimit - $usedTraffic)) : $textbotlang['users']['unlimited'];
    $percentUsed     = $dataLimit ? min(100, round(($usedTraffic / $dataLimit) * 100)) : 0;
    $progressLine = $dataLimit
        ? generateProgressBar($percentUsed) . "  {$percentUsed}٪"
        : "✨ سرویس نامحدود";
    $message = sprintf(
        $textbotlang['user']['Service']['InfoVolume'],
        $username,
        $invoiceData['Service_location'],
        $totalVolume,
        $usedVolume,
        $remainingVolume,
        $progressLine
    );
    $keyboard = json_encode([
        'inline_keyboard' => [
            [['text' => "🔄 بروزرسانی", 'callback_data' => "configused_" . $username, 'style' => 'success']],
            [['text' => $textbotlang['users']['backmenu'], 'callback_data' => "product_" . urlencode($username), 'style' => 'primary']]
        ]
    ]);
    editmessagetext($from_id, $message_id, $message, $keyboard, 'HTML');
} elseif (preg_match('/^config_([\w.]+)$/', $datain, $matches)) {
    $username    = urldecode($matches[1]);
    $invoiceData = select("invoice", "*", "username", $username, "select");
    if (!$invoiceData) {
        alert($textbotlang['users']['stateus']['usernotfound'], true);
        return;
    }
    $userData = $ManagePanel->DataUser($invoiceData['Service_location'], $username);
    $configs = [];
    if (!empty($userData['links']) && is_array($userData['links'])) {
        $configs = $userData['links'];
    } elseif (!empty($userData['subscription_url'])) {
        $subUrl = $userData['subscription_url'];
        $ch = curl_init($subUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 12);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'Accept: text/plain, application/json'
        ]);
        $subContent = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode === 200 && $subContent !== false && trim($subContent) !== '') {
            $trimmed = trim($subContent);
            if (preg_match('/^[A-Za-z0-9+\/=]+$/', $trimmed)) {
                $decoded = base64_decode($trimmed, true);
                if ($decoded !== false) {
                    $subContent = $decoded;
                }
            }
            $lines = array_filter(array_map('trim', explode("\n", $subContent)));
            $configs = array_values($lines);
        } else {
            $configs = [$subUrl];
        }
    }

    if (empty($configs)) {
        alert("❌ هیچ کانفیگی یافت نشد.", true);
        return;
    }
    $buttons = [];
    foreach ($configs as $index => $config) {
        $name = "🔗 کانفیگ " . ($index + 1);
        if (strpos($config, 'vmess://') === 0) {
            $base64   = substr($config, 8);
            $jsonStr  = base64_decode($base64);
            $jsonData = json_decode($jsonStr, true);
            if (json_last_error() === JSON_ERROR_NONE && !empty($jsonData['ps'])) {
                $name = "🔗 " . $jsonData['ps'];
            }
        } elseif (strpos($config, '#') !== false) {
            $parts = explode('#', $config);
            if (!empty($parts[1])) {
                $name = "🔗 " . urldecode(trim($parts[1]));
            }
        } elseif (filter_var($config, FILTER_VALIDATE_URL)) {
            $name = "🔗 لینک اشتراک";
        }
        $buttons[] = [['text' => $name, 'callback_data' => "showconfig_" . urlencode($username) . "_{$index}", 'style' => 'success']];
    }
    $buttons[] = [['text' => $textbotlang['users']['backmenu'], 'callback_data' => "product_" . urlencode($username), 'style' => 'primary']];
    $keyboard = json_encode(['inline_keyboard' => $buttons]);
    $text  = "📋 <b>لیست کانفیگ‌های شما</b>\n\n";
    $text .= "👤 کاربر: <b>{$username}</b>\n";
    $text .= "🌍 لوکیشن: <b>{$invoiceData['Service_location']}</b>\n";
    $text .= "📦 تعداد کانفیگ: <b>" . count($configs) . "</b>\n\n";
    $text .= "👇 برای مشاهده هر کانفیگ روی دکمه بزنید:";
    editmessagetext($from_id, $message_id, $text, $keyboard, 'HTML');
} elseif (preg_match('/^showconfig_(.+)_(\d+)$/', $datain, $matches)) {
    $username = urldecode($matches[1]);
    $index    = (int)$matches[2];
    $invoiceData = select("invoice", "*", "username", $username, "select");
    if (!$invoiceData) {
        alert($textbotlang['users']['stateus']['usernotfound'], true);
        return;
    }
    $userData = $ManagePanel->DataUser($invoiceData['Service_location'], $username);
    $configs = [];
    if (!empty($userData['links']) && is_array($userData['links'])) {
        $configs = $userData['links'];
    } elseif (!empty($userData['subscription_url'])) {
        $ch = curl_init($userData['subscription_url']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]);
        $subContent = curl_exec($ch);
        curl_close($ch);
        if ($subContent) {
            $trimmed = trim($subContent);
            if (preg_match('/^[A-Za-z0-9+\/=]+$/', $trimmed)) {
                $decoded = base64_decode($trimmed, true);
                if ($decoded) $subContent = $decoded;
            }
            $lines = array_filter(array_map('trim', explode("\n", $subContent)));
            $configs = array_values($lines);
        } else {
            $configs = [$userData['subscription_url']];
        }
    }
    if (empty($configs[$index])) {
        alert("❌ این کانفیگ دیگر وجود ندارد.", true);
        return;
    }
    $config = $configs[$index];
    $keyboard = json_encode([
        'inline_keyboard' => [[
            ['text' => $textbotlang['users']['backmenu'], 'callback_data' => "config_" . urlencode($username), 'style' => 'primary']
        ]]
    ]);
    $text  = "🔹 <b>کانفیگ شماره " . ($index + 1) . "</b>\n";
    $text .= "👤 کاربر: <b>{$username}</b>\n";
    $text .= "━━━━━━━━━━━━━━━━━━\n\n";
    $text .= "<code>" . htmlspecialchars($config) . "</code>\n\n";
    $text .= "💡 برای کپی، روی متن بالا لمس طولانی کنید.";
    editmessagetext($from_id, $message_id, $text, $keyboard, 'HTML');
} elseif (preg_match('/extend_(\w+)/', $datain, $matches)) {
    alert("📍 در حال دریافت لیست پلن‌ها", false);
    $username = $matches[1];
    $invoiceData = select("invoice", "*", "username", $username, "select");
    $userData = $ManagePanel->DataUser($invoiceData['Service_location'], $username);
    if (in_array($userData['status'], ["Unsuccessful", "on_hold"])) {
        alert($textbotlang['users']['stateus']['error'], true);
        return;
    }
    update("user", "Processing_value", $username, "id", $from_id);
    $stmt_discount = $pdo->prepare("SELECT discount_number FROM user WHERE id = :user_id LIMIT 1");
    $stmt_discount->bindParam(':user_id', $from_id);
    $stmt_discount->execute();
    $user = $stmt_discount->fetch(PDO::FETCH_ASSOC);
    $discount = 0;
    if ($user && isset($user['discount_number'])) {
        $discount = max(0, min(100, intval($user['discount_number'])));
    }
    $stmt = $pdo->prepare("SELECT * FROM product WHERE Location = :Location OR Location = '/all'");
    $stmt->bindValue(':Location', $invoiceData['Service_location']);
    $stmt->execute();
    $inlineKeyboard = ['inline_keyboard' => []];
    while ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $originalPrice = floatval($product['price_product']);
        $finalPrice = ($discount > 0) ? $originalPrice * (100 - $discount) / 100 : $originalPrice;
        $priceText = ($finalPrice <= 0) ? "رایگان" : number_format($finalPrice) . " تومان";
        $inlineKeyboard['inline_keyboard'][] = [['text' => "{$product['name_product']} - {$priceText}", 'callback_data' => "serviceextendselect_{$product['code_product']}", 'style' => 'success']];
    }
    $inlineKeyboard['inline_keyboard'][] = [['text' => $textbotlang['users']['backmenu'], 'callback_data' => "product_{$username}", 'style' => 'primary']];
    Editmessagetext($from_id, $message_id, $textbotlang['users']['extend']['selectservice'], json_encode($inlineKeyboard));
} elseif (preg_match('/serviceextendselect_(\w+)/', $datain, $matches)) {
    $codeProduct = $matches[1];
    $invoiceData = select("invoice", "*", "username", $user['Processing_value'], "select");
    if (!$invoiceData) {
        alert($textbotlang['users']['extend']['error2'], true);
        return;
    }
    $stmt = $pdo->prepare("SELECT * FROM product WHERE (Location = :Location OR Location = '/all') AND code_product = :code_product LIMIT 1");
    $stmt->bindValue(':Location', $invoiceData['Service_location']);
    $stmt->bindValue(':code_product', $codeProduct);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$product) {
        alert($textbotlang['users']['extend']['error2'], true);
        return;
    }
    $discount = isset($user['discount_number']) ? intval($user['discount_number']) : 0;
    $discount = max(0, min(100, $discount));
    $priceWithDiscount = $product['price_product'] * (100 - $discount) / 100;
    update("invoice", "name_product", $product['name_product'], "username", $user['Processing_value']);
    update("invoice", "Service_time", $product['Service_time'], "username", $user['Processing_value']);
    update("invoice", "Volume", $product['Volume_constraint'], "username", $user['Processing_value']);
    update("invoice", "price_product", $priceWithDiscount, "username", $user['Processing_value']);
    update("user", "Processing_value_one", $codeProduct, "id", $from_id);
    $keyboardExtend = [
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['extend']['confirm'], 'callback_data' => "confirmserivce-{$codeProduct}", 'style' => 'success'],
            ],
            [
                ['text' => $textbotlang['users']['backmenu'], 'callback_data' => "backuser", 'style' => 'primary']
            ]
        ]
    ];
    Editmessagetext($from_id, $message_id, $textbotlang['users']['extend']['invoicExtend'], json_encode($keyboardExtend));
} elseif (preg_match('/confirmserivce-(.*)/', $datain, $dataget)) {
    $codeproduct = $dataget[1];
    deletemessage($from_id, $message_id);
    $nameloc = select("invoice", "*", "username", $user['Processing_value'], "select");
    if ($nameloc == false) {
        alert($textbotlang['users']['extend']['error2'], true);
        return;
    }
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    if ($marzban_list_get == false) {
        alert($textbotlang['users']['extend']['error2'], true);
        return;
    }
    $stmt = $pdo->prepare("SELECT * FROM product WHERE (Location = :Location OR location = '/all') AND code_product = :code_product LIMIT 1");
    $stmt->bindValue(':Location', $nameloc['Service_location']);
    $stmt->bindValue(':code_product', $codeproduct);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($product == false) {
        alert($textbotlang['users']['extend']['error2'], true);
        return;
    }
    $discount = intval($user['discount_number'] ?? 0);
    if ($discount < 0) $discount = 0;
    if ($discount > 100) $discount = 100;
    $price_product_with_discount = $product['price_product'] * (100 - $discount) / 100;
    if ($user['Balance'] < $price_product_with_discount) {
        $Balance_prim = $price_product_with_discount - $user['Balance'];
        alert("💡 موجودی کیف پول (".number_format($user['Balance'])." تومان) کافی نیست، لطفا به مقدار ".number_format($Balance_prim)." تومان شارژ کنید", true);
        return;
    }
    $usernamepanel = $nameloc['username'];
    $Balance_Low_user = $user['Balance'] - $price_product_with_discount;
    update("user", "Balance", $Balance_Low_user, "id", $from_id);
    $ManagePanel->ResetUserDataUsage($nameloc['Service_location'], $user['Processing_value']);
    if ($marzban_list_get['type'] == "marzban") {
        if (intval($product['Service_time']) == 0) {
            $newDate = 0;
        } else {
            $date = strtotime("+" . $product['Service_time'] . "day");
            $newDate = strtotime(date("Y-m-d H:i:s", $date));
        }
        $data_limit = intval($product['Volume_constraint']) * pow(1024, 3);
        $datam = array(
            "expire" => $newDate,
            "data_limit" => $data_limit
        );
        $ManagePanel->Modifyuser($user['Processing_value'], $nameloc['Service_location'], $datam);
    } elseif ($marzban_list_get['type'] == "marzneshin") {
        if (intval($product['Service_time']) == 0) {
            $newDate = 0;
        } else {
            $date = strtotime("+" . $product['Service_time'] . "day");
            $newDate = strtotime(date("Y-m-d H:i:s", $date));
        }
        $data_limit = intval($product['Volume_constraint']) * pow(1024, 3);
        $datam = array(
            "expire_date" => $newDate,
            "data_limit" => $data_limit
        );
        $ManagePanel->Modifyuser($user['Processing_value'], $nameloc['Service_location'], $datam);
    } elseif ($marzban_list_get['type'] == "x-ui") {
        $date = strtotime("+" . $product['Service_time'] . "day");
        $newDate = strtotime(date("Y-m-d H:i:s", $date)) * 1000;
        $data_limit = intval($product['Volume_constraint']) * pow(1024, 3);
        $config = array(
            'settings' => json_encode(
                array(
                    'clients' => array(
                        array(
                            "totalGB" => $data_limit,
                            "expiryTime" => $newDate,
                            "enable" => true,
                        )
                    ),
                )
            ),
        );
        $ManagePanel->Modifyuser($user['Processing_value'], $nameloc['Service_location'], $config);
    } elseif ($marzban_list_get['type'] == "alireza") {
        $date = strtotime("+" . $product['Service_time'] . "day");
        $newDate = strtotime(date("Y-m-d H:i:s", $date)) * 1000;
        $data_limit = intval($product['Volume_constraint']) * pow(1024, 3);
        $config = array(
            'id' => intval($marzban_list_get['inboundid']),
            'settings' => json_encode(
                array(
                    'clients' => array(
                        array(
                            "totalGB" => $data_limit,
                            "expiryTime" => $newDate,
                            "enable" => true,
                        )
                    ),
                )
            ),
        );
        $ManagePanel->Modifyuser($user['Processing_value'], $nameloc['Service_location'], $config);
    }
    $keyboardextendfnished = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['stateus']['backlist'], 'callback_data' => "product_" . $usernamepanel, 'style' => 'primary'],
            ]
        ]
    ]);
    $discount = intval($user['discount_number'] ?? 0);
    if ($discount < 0) $discount = 0;
    if ($discount > 100) $discount = 100;
    $price_product_with_discount = $product['price_product'] * (100 - $discount) / 100;
    $priceproductformat = $price_product_with_discount;
    $balanceformatsell = number_format(select("user", "Balance", "id", $from_id, "select")['Balance']);
    update("invoice", "Status", "active", "id_invoice", $nameloc['id_invoice']);
    sendmessage($from_id, $textbotlang['users']['extend']['thanks'], $keyboardextendfnished, 'HTML');
    $text_report = sprintf($textbotlang['Admin']['Report']['extend'], $from_id, $username, $product['name_product'], $priceproductformat, $usernamepanel, $balanceformatsell, $nameloc['Service_location']);
    if (!empty($setting['Channel_Report'])) {
        sendmessage($setting['Channel_Report'], $text_report, null, 'HTML');
    }
} elseif (preg_match('/changelink_(\w+)/', $datain, $dataget)) {
    $username = htmlspecialchars($dataget[1]);
    $nameloc = select("invoice", "*", "username", $username, "select");
    $keyboardchange = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['changelink']['confirm'], 'callback_data' => "confirmchange_" . $username, 'style' => 'danger'],
                ['text' => $textbotlang['users']['changelink']['no'], 'callback_data' => "product_" . $username, 'style' => 'primary']
            ]
        ]
    ]);

    Editmessagetext($from_id, $message_id, $textbotlang['users']['changelink']['warnchange'], $keyboardchange, 'HTML');
} elseif (preg_match('/confirmchange_(\w+)/', $datain, $dataget)) {
    $usernameconfig = htmlspecialchars($dataget[1]);
    $nameloc = select("invoice", "*", "username", $usernameconfig, "select");
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    $ManagePanel->Revoke_sub($marzban_list_get['name_panel'], $usernameconfig);
    $keyboardchange = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['stateus']['backservice'], 'callback_data' => "product_" . $usernameconfig, 'style' => 'primary'],
            ]
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['users']['changelink']['confirmed'], $keyboardchange, 'HTML');
} elseif (preg_match('/removebywarn_(\w+)/', $datain, $dataget)) {
    $username = $dataget[1];
    $nameloc = select("invoice", "*", "username", $username, "select");
    $keyboardchange = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['removebysub']['confirm'], 'callback_data' => "removebyuser-" . htmlspecialchars($username), 'style' => 'danger'],
                ['text' => $textbotlang['users']['removebysub']['no'], 'callback_data' => "product_" . htmlspecialchars($username), 'style' => 'primary']
            ]
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['users']['removebysub']['warnchange'], $keyboardchange, 'HTML');
} elseif (preg_match('/removebyuser-(\w+)/', $datain, $dataget)) {
    $username = $dataget[1];
    $nameloc = select("invoice", "*", "username", $username, "select");
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $nameloc['Service_location'], "select");
    $ManagePanel->RemoveUser($nameloc['Service_location'], $nameloc['username']);
    update('invoice', 'status', 'removebyuser', 'id_invoice', $nameloc['id_invoice']);
    $textinfo = sprintf($textbotlang['users']['stateus']['RemovedService'], $nameloc['username']);
    Editmessagetext($from_id, $message_id, $textinfo, null, 'HTML');
    $tetremove = sprintf($textbotlang['Admin']['Report']['NotifRemoveByUser'], $nameloc['username']);
    if (strlen($setting['Channel_Report']) > 0) {
        sendmessage($setting['Channel_Report'], $tetremove, null, 'HTML');
    }
}

#-----------usertest------------#

if ($text == $datatextbot['text_usertest'] || $datain == "testserver") {
    $usertest_panels = select("marzban_panel", "*", "statusTest", "ontestshowpanel", "count");
    if ($usertest_panels == 0) {
        alert("⚠️ در حال حاضر هیچ پلن تست فعالی موجود نیست.", true);
        return;
    }
    if ($setting['get_number'] == "1" && $user['step'] != "get_number" && $user['number'] == "none") {
        sendmessage($from_id, $textbotlang['users']['number']['Confirming'], $request_contact, 'HTML');
        step('get_number', $from_id);
        return;
    }
    if ($user['number'] == "none" && $setting['get_number'] == "1") {
        return;
    }
    if ($user['limit_usertest'] <= 0) {
        respondView($from_id, $message_id, $textbotlang['users']['usertest']['limitwarning'], $keyboard_json);
        return;
    }
    respondView($from_id, $message_id, $textbotlang['users']['Service']['Test'], $list_marzban_usertest);
} elseif (preg_match('/^tozihat_(\d+)$/', $datain, $dataget)) {
    $idl   = $dataget[1];
    $panel = select("marzban_panel", "*", "id", $idl, "select");
    if (!$panel) {
        alert($textbotlang['users']['stateus']['error'], true);
        return;
    }
    $btn = [
        'inline_keyboard' => [
            [['text' => $datatextbot['text_usertest'], 'callback_data' => "locationtests_{$idl}", 'style' => 'success']],
            [['text' => $textbotlang['users']['backmenu'], 'callback_data' => "back_to_list", 'style' => 'primary']],
        ]
    ];
    $textin = sprintf($textbotlang['users']['Service']['TextTest'], $panel['name_panel'], $datatextbot['text_info_test']);
    editmessagetext($from_id, $message_id, $textin, json_encode($btn, JSON_UNESCAPED_UNICODE), "HTML");
} elseif ($datain == "back_to_list") {
    editmessagetext($from_id, $message_id, $textbotlang['users']['Service']['Test'], $list_marzban_usertest, "HTML");
} elseif ($user['step'] == "createusertest" || preg_match('/locationtests_(.*)/', $datain, $dataget)) {
    deletemessage($from_id, $message_id);
    if ($user['limit_usertest'] <= 0) {
        sendmessage($from_id, $textbotlang['users']['usertest']['limitwarning'], $keyboard, 'HTML');
        return;
    }
    $id_panel = $dataget[1] ?? null;
    if (empty($id_panel)) {
        alert("❌ لطفاً دوباره از لیست سرویس‌های تست انتخاب کنید.", true);
        step('home', $from_id);
        return;
    }
    alert("⏳ در حال ساخت سرویس تست...", false);
    $marzban_list_get = select("marzban_panel", "*", "id", $id_panel, "select");
    if (!$marzban_list_get) {
        alert($textbotlang['users']['stateus']['error'], true);
        return;
    }
    $name_panel   = $marzban_list_get['name_panel'];
    $randomString = bin2hex(random_bytes(2));
    $username_ac  = strtolower(generateUsername($from_id, $marzban_list_get['MethodUsername'], $user['username'], $randomString));
    $DataUserOut  = $ManagePanel->DataUser($name_panel, $username_ac);
    if (isset($DataUserOut['username']) || in_array($username_ac, $usernameinvoice)) {
        $random_number = random_int(1000, 9999);
        $username_ac  .= $random_number;
    }
    $datac = [
        'expire'     => strtotime(date("Y-m-d H:i:s", strtotime("+" . $setting['time_usertest'] . "hours"))),
        'data_limit' => $setting['val_usertest'] * 1048576,
    ];
    $dataoutput = $ManagePanel->createUser($name_panel, $username_ac, $datac, true);
    if (empty($dataoutput) || ($dataoutput['username'] ?? null) === null) {
        $msgForReport = json_encode($dataoutput['msg'] ?? null);
        alert($textbotlang['users']['usertest']['errorcreat'], true);
        $texterros = sprintf($textbotlang['users']['buy']['errorInCreate'], $msgForReport, $from_id, $username);
        if (!empty($setting['Channel_Report'])) {
            sendmessage($setting['Channel_Report'], $texterros, null, 'HTML');
        }
        return;
    }
    $date         = time();
    $randomString = bin2hex(random_bytes(4));
    $sql  = "INSERT IGNORE INTO invoice (id_user, id_invoice, username, time_sell, Service_location, name_product, price_product, Volume, Service_time, Status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $from_id,
        $randomString,
        $username_ac,
        $date,
        $name_panel,
        "usertest",
        "0",
        $setting['val_usertest'],
        $setting['time_usertest'],
        "active",
    ]);
    $config             = "";
    $text_config        = "";
    $output_config_link = "";
    if ($marzban_list_get['sublink'] === "onsublink") {
        $output_config_link = $dataoutput['subscription_url'];
    }
    if ($marzban_list_get['configManual'] === "onconfig" && is_array($dataoutput['configs'])) {
        foreach ($dataoutput['configs'] as $configs) {
            $config .= "\n" . $configs;
        }
        $text_config = $config;
    }
    $Shoppinginfo = json_encode([
        'inline_keyboard' => [
            [['text' => "🚀 " . $textbotlang['users']['help']['btninlinebuy'], 'url' => $dataoutput['subscription_url']]]
        ]
    ]);
    $textcreatuser = sprintf($textbotlang['users']['buy']['createservicetest'], $marzban_list_get['name_panel'], $username_ac, $output_config_link, $text_config);
    sendmessage($from_id, $textcreatuser, $Shoppinginfo, 'HTML');
    $limit_usertest = $user['limit_usertest'] - 1;
    update("user", "limit_usertest", $limit_usertest, "id", $from_id);
    step('home', $from_id);
    if (!empty($setting['Channel_Report'])) {
        $text_report = sprintf($textbotlang['Admin']['Report']['ReportTestCreate'], $from_id, $username, $username_ac, $first_name, $marzban_list_get['name_panel'], $user['number']);
        sendmessage($setting['Channel_Report'], $text_report, null, 'HTML');
    }
}

#-----------help------------#

if ($text == $datatextbot['text_help'] || $datain == "helpbtn" || $text == "/help") {
    if ($setting['help_Status'] == "0") {
        if (USER_INLINE_KEYBOARD) {
            Editmessagetext($from_id, $message_id, $textbotlang['users']['help']['disablehelp'], $backuser, 'HTML');
        } else {
            sendmessage($from_id, $textbotlang['users']['help']['disablehelp'], null, 'HTML');
        }
        return;
    }
    $keyboard_first = json_encode(['inline_keyboard' => $help_buttons]);
    if (USER_INLINE_KEYBOARD) {
        Editmessagetext($from_id, $message_id, $textbotlang['users']['help']['selectoption'], $keyboard_first, 'HTML');
    } else {
        sendmessage($from_id, $textbotlang['users']['help']['selectoption'], $keyboard_first, 'HTML');
    }
    step('sendhelp', $from_id);
} elseif ($user['step'] == "sendhelp" && str_starts_with($datain, "help_")) {
    $help_name = htmlspecialchars(substr($datain, 5));
    $helpdata = select("help", "*", "name_os", $help_name, "select");
    $text_to_send = htmlspecialchars($helpdata['Description_os'] ?: "not set");
    $json_keyboard = json_encode([
        'inline_keyboard' => [
            [['text' => $textbotlang['users']['backmenu'], 'callback_data' => "helpbtsn"]]
        ]
    ]);
    if (!empty($helpdata['Media_os'])) {
        if ($helpdata['type_Media_os'] == "video") {
            sendvideo($from_id, $helpdata['Media_os'], $text_to_send);
        } elseif ($helpdata['type_Media_os'] == "photo") {
            sendphoto($from_id, $helpdata['Media_os'], $text_to_send);
        }
    } else {
        Editmessagetext($from_id, $message_id, $text_to_send, $json_keyboard, 'HTML');
    }
} elseif ($user['step'] == "sendhelp" && $datain == "helpbtsn") {
    $keyboard_first = json_encode(['inline_keyboard' => $help_buttons]);
    Editmessagetext($from_id, $message_id, $textbotlang['users']['help']['selectoption'], $keyboard_first, 'HTML');
}

#-----------support------------#

if ($text == $datatextbot['text_support'] || $datain == "support" || $text == "/support") {
    if (USER_INLINE_KEYBOARD) {
        Editmessagetext($from_id, $message_id, $datatextbot['text_support_qs'], $backuser);
    } else {
        sendmessage($from_id, $datatextbot['text_support_qs'], $backuser, 'HTML');
    }
    step('gettextpm', $from_id);
} elseif ($user['step'] == 'gettextpm') {
    sendmessage($from_id, $textbotlang['users']['support']['sendmessageadmin'], $keyboard, 'HTML');
    $Response = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['support']['answermessage'], 'callback_data' => 'Response_' . $from_id],
            ],
        ]
    ]);
    $text_safe = htmlspecialchars($text);
    $caption_safe = htmlspecialchars($caption ?? '');
    foreach ($admin_ids as $id_admin) {
        if (!empty($text_safe)) {
            $textsendadmin = sprintf($textbotlang['users']['support']['GetMessageOfUser'], $from_id, htmlspecialchars($username), $text_safe);
            sendmessage($id_admin, $textsendadmin, $Response, 'HTML');
        }
        if (!empty($photoid)) {
            $textsendadmin = sprintf($textbotlang['users']['support']['GetMessageOfUser'], $from_id, htmlspecialchars($username), $caption_safe);
            telegram('sendphoto', [
                'chat_id' => $id_admin,
                'photo' => $photoid,
                'reply_markup' => $Response,
                'caption' => $textsendadmin,
                'parse_mode' => "HTML",
            ]);
        }
    }
    step('home', $from_id);
}

if ($text == $datatextbot['text_account'] || $datain == "account") {
    include_once("jdf.php");
    $datetime = jdate('l j F Y ، ساعت H:i:s', time());
    $first_name_safe = htmlspecialchars($first_name);
    $Balanceuser = number_format($user['Balance'], 0);
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM invoice WHERE id_user = :id_user AND status = 'active'");
    $stmt->bindParam(':id_user', $from_id);
    $stmt->execute();
    $countorder = $stmt->fetchColumn();
    $text_account = sprintf($textbotlang['users']['account'], $first_name_safe, $from_id, $user['affiliatescount'], $Balanceuser, $countorder, $datetime);
    if (USER_INLINE_KEYBOARD) {
        Editmessagetext($from_id, $message_id, $text_account, $keyboardPanel);
    } else {
        sendmessage($from_id, $text_account, $keyboardPanel, 'HTML');
    }
}

#------------------------#

if ($text == $datatextbot['text_sell'] || $datain == "buyserver" || $text == "/buy") {
    if ($setting['get_number'] == "1" && $user['step'] != "get_number" && $user['number'] === "none") {
        sendmessage($from_id, $textbotlang['users']['number']['Confirming'], $request_contact, 'HTML');
        step('get_number', $from_id);
        return;
    }
    if ($user['number'] === "none" && $setting['get_number'] == "1") {
        return;
    }
    $keyboard = [
        'inline_keyboard' => [
            [['text' => htmlspecialchars($datatextbot['text_inline_key']), 'callback_data' => "buy"]]
        ]
    ];
    if (USER_INLINE_KEYBOARD) {
        $keyboard['inline_keyboard'][] = [
            ['text' => $textbotlang['users']['backmenu'], 'callback_data' => "backuser"]
        ];
        $keyboard_json = json_encode($keyboard, JSON_UNESCAPED_UNICODE);
        Editmessagetext($from_id, $message_id, $textbotlang['users']['Service']['LocService'], $keyboard_json);
    } else {
        $keyboard_json = json_encode($keyboard, JSON_UNESCAPED_UNICODE);
        sendmessage($from_id, $textbotlang['users']['Service']['LocService'], $keyboard_json, 'HTML');
    }
    return;
} elseif ($datain === "back_buy_server") {
    $keyboard = ['inline_keyboard' => [
            [['text' => htmlspecialchars($datatextbot['text_inline_key']), 'callback_data' => "buy"]]
        ]
    ];
    if (USER_INLINE_KEYBOARD) {
        $keyboard['inline_keyboard'][] = [
            ['text' => $textbotlang['users']['backmenu'], 'callback_data' => "backuser"]
        ];
    }
    $keyboard_json = json_encode($keyboard, JSON_UNESCAPED_UNICODE);
    Editmessagetext($from_id, $message_id, $textbotlang['users']['Service']['LocService'], $keyboard_json);
} elseif ($datain == "buy") {
    $locationproduct = select("marzban_panel", "*", "status", "activepanel", "count");
    if ($locationproduct == 0) {
        alert($textbotlang['Admin']['managepanel']['nullpanel'], true);
        return;
    }
    if ($locationproduct == 0) {
        $panel = select("marzban_panel", "*", "status", "activepanel", "select");
        update("user", "Processing_value", $panel['name_panel'], "id", $from_id, "select");
        if ($setting['statuscategory'] == "0") {
            $nullproduct = select("product", "*", null, null, "count");
            if ($nullproduct == 0) {
                Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Product']['nullpProduct']);
                return;
            }
            $textproduct = sprintf($textbotlang['users']['buy']['selectService'], $panel['name_panel']);
            $keyboard = KeyboardProduct($panel['name_panel'], "back_buy_server", $panel['MethodUsername']);
            Editmessagetext($from_id, $message_id, $textproduct, $keyboard);
        } else {
            $emptycategory = select("category", "*", null, null, "count");
            if ($emptycategory == 0) {
                alert($textbotlang['users']['category']['NotFound'], false);
                return;
            }
            $keyboard = KeyboardCategorybuy("back_buy_server", $panel['name_panel']);
            Editmessagetext($from_id, $message_id, $textbotlang['users']['category']['selectCategory'], $keyboard);
        }
    } else {
        $keyboard = $list_marzban_panel_user;
        $arr = json_decode($keyboard, true);
        $arr['inline_keyboard'][] = [['text' => $textbotlang['users']['backmenu'], 'callback_data' => "back_buy_server"]];
        $keyboard = json_encode($arr);
        Editmessagetext($from_id, $message_id, $textbotlang['users']['Service']['Location'], $keyboard);
    }
} elseif (preg_match('/^categorylist_(.*)/', $datain, $dataget)) {
    $categoryid = $dataget[1];
    $productCount = select("product", "*", null, null, "count");
    if ($productCount == 0) {
        sendmessage($from_id, $textbotlang['Admin']['Product']['nullpProduct'], null, 'HTML');
        return;
    }
    $location = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    if ($location === false) {
        alert($textbotlang['users']['category']['error'], true);
        return;
    }
    $panelNameSafe = htmlspecialchars($location['name_panel']);
    $textMessage = sprintf($textbotlang['users']['buy']['selectService'], $panelNameSafe);
    $keyboard = KeyboardProduct($location['name_panel'], "buy", $location['MethodUsername'], $categoryid);
    Editmessagetext($from_id, $message_id, $textMessage, $keyboard);
    update("user", "Processing_value", $location['name_panel'], "id", $from_id);
} elseif (preg_match('/^location_(.*)/', $datain, $dataget)) {
    $locationid = $dataget[1];
    $panellist = select("marzban_panel", "*", "id", $locationid, "select");
    if (!$panellist) {
        alert($textbotlang['users']['category']['error'], true);
        return;
    }
    $location = $panellist['name_panel'];
    update("user", "Processing_value", $location, "id", $from_id);
    if ($setting['statuscategory'] == "0") {
        $productCount = select("product", "*", null, null, "count");
        if ($productCount == 0) {
            Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Product']['nullpProduct'], null);
            return;
        }
        $panelNameSafe = htmlspecialchars($panellist['name_panel']);
        $textMessage = sprintf($textbotlang['users']['buy']['selectService'], $panelNameSafe);
        $keyboard = KeyboardProduct($panellist['name_panel'], "buy", $panellist['MethodUsername']);
        Editmessagetext($from_id, $message_id, $textMessage, $keyboard);
    } else {
        $categoryCount = select("category", "*", null, null, "count");
        if ($categoryCount == 0) {
            alert($textbotlang['users']['category']['NotFound'], false);
            return;
        }
        $keyboard = KeyboardCategorybuy("buy", $panellist['name_panel']);
        Editmessagetext($from_id, $message_id, $textbotlang['users']['category']['selectCategory'], $keyboard);
    }
} elseif (preg_match('/prodcutservice_(.*)/', $datain, $dataget)) {
    $productCode = $dataget[1];
    $panel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    if (!$panel) {
        alert($textbotlang['users']['category']['error'], true);
        step("home", $from_id);
        return;
    }
    if (empty($productCode)) {
        alert($textbotlang['users']['category']['error'], true);
        step("home", $from_id);
        return;
    }
    update("user", "Processing_value_one", $productCode, "id", $from_id);
    $stmt = $pdo->prepare("SELECT * FROM product WHERE code_product = :code_product AND (location = :loc OR location = '/all') LIMIT 1");  
    $stmt->bindValue(':code_product', $productCode);
    $stmt->bindValue(':loc', $user['Processing_value']);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$product) {
        alert($textbotlang['users']['stateus']['error2'], true);
        step("home", $from_id);
        return;
    }
    $randomString = bin2hex(random_bytes(2));
    $username_ac = strtolower(generateUsername($from_id, $panel['MethodUsername'], $username, $randomString));
    $DataUserOut = $ManagePanel->DataUser($panel['name_panel'], $username_ac);
    if (isset($DataUserOut['username']) || in_array($username_ac, $usernameinvoice)) {
        $username_ac = $username_ac . $random_int(1000, 9999);
    }
    update("user", "Processing_value_tow", $username_ac, "id", $from_id);
    if ($product['Volume_constraint'] == 0) {
        $product['Volume_constraint'] = $textbotlang['users']['stateus']['Unlimited'];
    }
    $discount = intval($user['discount_number'] ?? 0);
    $discount = max(0, min(100, $discount));
    $price_with_discount = round($product['price_product'] * (100 - $discount) / 100);
    $price_with_discount = max(0, $price_with_discount);
    $price_formatted = ($price_with_discount <= 0) ? "رایگان" : number_format($price_with_discount) . " تومان";
    $productName = htmlspecialchars($product['name_product']);
    $panelName = htmlspecialchars($user['Processing_value']);
    $username_ac_safe = htmlspecialchars($username_ac);
    $textMessage = sprintf($textbotlang['users']['buy']['invoicebuy'], $productName, $panelName, $price_formatted, $username_ac_safe);
    Editmessagetext($from_id, $message_id, $textMessage, $payment);
    step('payment', $from_id);
} elseif ($user['step'] == "payment" && $datain == "confirmandgetservice" || $datain == "confirmandgetserviceDiscount") {
    $partsdic = explode("_", $user['Processing_value_four']);
    $stmt = $pdo->prepare("SELECT * FROM product WHERE code_product = :code AND (location = :loc1 OR location = '/all') LIMIT 1");
    $stmt->bindValue(':code', $user['Processing_value_one']);
    $stmt->bindValue(':loc1', $user['Processing_value']);
    $stmt->execute();
    $info_product = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($info_product == false) {
        alert($textbotlang['users']['stateus']['error2'], true);
        return;
    }
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    if ($marzban_list_get == false) {
        alert($textbotlang['users']['stateus']['error2'], true);
        return;
    }
    if ($marzban_list_get['linksubx'] == null and in_array($marzban_list_get['type'], ["x-ui", "alireza"])) {
        foreach ($admin_ids as $admin) {
            sendmessage($admin, sprintf($textbotlang['Admin']['managepanel']['notsetlinksub'], $marzban_list_get['name_panel']), null, 'HTML');
        }
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['paneldeactive'], $keyboard, 'HTML');
        return;
    }
    $username_ac = $user['Processing_value_tow'];
    $date = time();
    $randomString = bin2hex(random_bytes(4));
    if (empty($info_product['price_product']))
        return;
    if ($datain == "confirmandgetserviceDiscount") {
        $priceproduct = $partsdic[2];
    } else {
        $priceproduct = $info_product['price_product'];
    }
    $discount = intval($user['discount_number'] ?? 0);
    if ($discount < 0) $discount = 0;
    if ($discount > 100) $discount = 100;
    $price_with_discount = $priceproduct * (100 - $discount) / 100;
    if ($price_with_discount > $user['Balance']) {
        $Balance_prim = $price_with_discount - $user['Balance'];
        $userwallet = $user['Balance'];
        $needamount = $Balance_prim;
        alert("💡 موجودی کیف پول (" . number_format($userwallet) . " تومان) کافی نیست، لطفا به مقدار " . number_format($needamount) . " تومان شارژ کنید", true);
        return;
    }
    if (in_array($randomString, $id_invoice)) {
        $randomString = $random_number . $randomString;
    }
    $sql = "INSERT IGNORE INTO invoice (id_user, id_invoice, username, time_sell, Service_location, name_product, price_product, Volume, Service_time, Status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $Status = "active";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(1, $from_id);
    $stmt->bindParam(2, $randomString);
    $stmt->bindParam(3, $username_ac, PDO::PARAM_STR);
    $stmt->bindParam(4, $date);
    $stmt->bindParam(5, $user['Processing_value'], PDO::PARAM_STR);
    $stmt->bindParam(6, $info_product['name_product'], PDO::PARAM_STR);
    $stmt->bindParam(7, $price_with_discount);
    $stmt->bindParam(8, $info_product['Volume_constraint']);
    $stmt->bindParam(9, $info_product['Service_time']);
    $stmt->bindParam(10, $Status);
    $stmt->execute();
    if ($info_product['Service_time'] == "0") {
        $data = "0";
    } else {
        $date = strtotime("+" . $info_product['Service_time'] . "days");
        $data = strtotime(date("Y-m-d H:i:s", $date));
    }
    $datac = array(
        'expire' => $data,
        'data_limit' => $info_product['Volume_constraint'] * pow(1024, 3),
    );
    $dataoutput = $ManagePanel->createUser($marzban_list_get['name_panel'], $username_ac, $datac);
    if ($dataoutput['username'] == null) {
        $dataoutput['msg'] = json_encode($dataoutput['msg']);
        alert($textbotlang['users']['sell']['ErrorConfig'], true);
        $texterros = sprintf($textbotlang['users']['buy']['errorInCreate'], $dataoutput['msg'], $from_id, $username);
        if (isset($setting['Channel_Report']) && strlen($setting['Channel_Report']) > 0) {
            sendmessage($setting['Channel_Report'], $texterros, null, 'HTML');
        }
        return;
    }
    if ($datain == "confirmandgetserviceDiscount") {
        $SellDiscountlimit = select("DiscountSell", "*", "codeDiscount", $partsdic[0], "select");
        $value = intval($SellDiscountlimit['usedDiscount']) + 1;
        update("DiscountSell", "usedDiscount", $value, "codeDiscount", $partsdic[0]);
        $text_report = sprintf($textbotlang['users']['Report']['discountused'], $username, $from_id, $partsdic[0]);
        if (isset($setting['Channel_Report']) && strlen($setting['Channel_Report']) > 0) {
            sendmessage($setting['Channel_Report'], $text_report, null, 'HTML');
        }
    }
    $affiliatescommission = select("affiliates", "*", null, null, "select");
    if ($affiliatescommission['status_commission'] == "oncommission" && ($user['affiliates'] !== null || $user['affiliates'] != "0")) {
        $affiliatescommission = select("affiliates", "*", null, null, "select");
        $result = ($priceproduct * $affiliatescommission['affiliatespercentage']) / 100;
        $user_Balance = select("user", "*", "id", $user['affiliates'], "select");
        if ($user_Balance) {
            $Balance_prim = $user_Balance['Balance'] + $result;
            update("user", "Balance", $Balance_prim, "id", $user['affiliates']);
            $result = number_format($result);
            $textadd = sprintf($textbotlang['users']['affiliates']['porsantuser'], $result);
            sendmessage($user['affiliates'], $textadd, null, 'HTML');
        }
    }
    $link_config = "";
    $text_config = "";
    $config = "";
    $configqr = "";
    if ($marzban_list_get['sublink'] == "onsublink") {
        $output_config_link = $dataoutput['subscription_url'];
        $link_config = $output_config_link;
    }
    if ($marzban_list_get['configManual'] == "onconfig") {
        if (is_array($dataoutput['configs']) and count($dataoutput['configs']) != 0) {
            foreach ($dataoutput['configs'] as $configs) {
                $config .= "\n" . $configs;
                $configqr .= $configs;
            }
        } else {
            $config .= "";
            $configqr .= "";
        }
        $text_config = $config;
    }
    $Shoppinginfo = json_encode([
        'inline_keyboard' => [
            [
                ['text' => "🚀 " . $textbotlang['users']['help']['btninlinebuy'], 'url' => $output_config_link],
            ]
        ]
    ]);
    if ($marzban_list_get['type'] == "marzban") {
        $textcreatuser = sprintf($textbotlang['users']['buy']['createservice'], $marzban_list_get['name_panel'], $username_ac, $text_config, $link_config);
    } else {
        $textcreatuser = sprintf($textbotlang['users']['buy']['createservice'], $marzban_list_get['name_panel'], $username_ac, $text_config, $link_config);
    }
    if ($marzban_list_get['sublink'] == "onsublink") {
        deletemessage($from_id, $message_id);
        sendmessage($from_id, $textcreatuser, $Shoppinginfo, 'HTML');
    } elseif ($marzban_list_get['config'] == "onconfig") {
        if (count($dataoutput['configs']) == 1) {
            $urlimage = "$from_id$randomString.png";
            $writer = new PngWriter();
            $qrCode = QrCode::create($configqr)
                ->setEncoding(new Encoding('UTF-8'))
                ->setErrorCorrectionLevel(ErrorCorrectionLevel::Low)
                ->setSize(400)
                ->setMargin(0)
                ->setRoundBlockSizeMode(RoundBlockSizeMode::Margin);
            $result = $writer->write($qrCode, null, null);
            $result->saveToFile($urlimage);
            telegram('sendphoto', [
                'chat_id' => $from_id,
                'photo' => new CURLFile($urlimage),
                'reply_markup' => $Shoppinginfo,
                'caption' => $textcreatuser,
                'parse_mode' => "HTML",
            ]);
            unlink($urlimage);
        } else {
            sendmessage($from_id, $textcreatuser, $Shoppinginfo, 'HTML');
        }
    } else {
        sendmessage($from_id, $textcreatuser, $Shoppinginfo, 'HTML');
    }
    $Balance_prim = $user['Balance'] - $price_with_discount;
    update("user", "Balance", $Balance_prim, "id", $from_id);
    $user['Balance'] = number_format($user['Balance'], 0);
    $text_report = sprintf($textbotlang['users']['Report']['reportbuy'], $username_ac, number_format($price_with_discount, 0), $info_product['Volume_constraint'], $from_id, $user['number'], $user['Processing_value'], $user['Balance'], $username);  
    if (isset($setting['Channel_Report']) && strlen($setting['Channel_Report']) > 0) {
        sendmessage($setting['Channel_Report'], $text_report, null, 'HTML');
    }
    step('home', $from_id);
} elseif ($datain == "aptdc") {
    sendmessage($from_id, $textbotlang['users']['Discount']['getcodesell'], $backuser, 'HTML');
    step('getcodesellDiscount', $from_id);
    deletemessage($from_id, $message_id);
} elseif ($user['step'] == "getcodesellDiscount") {
    if (!in_array($text, $SellDiscount)) {
        sendmessage($from_id, $textbotlang['users']['Discount']['notcode'], $backuser, 'HTML');
        return;
    }
    $SellDiscountlimit = select("DiscountSell", "*", "codeDiscount", $text, "select");
    if ($SellDiscountlimit == false) {
        sendmessage($from_id, $textbotlang['Admin']['Discount']['invalidcodedis'], null, 'HTML');
        return;
    }
    $stmt = $pdo->prepare("SELECT * FROM product WHERE code_product = :code AND (location = :loc1 OR location = '/all') LIMIT 1");
    $stmt->bindValue(':code', $user['Processing_value_one']);
    $stmt->bindValue(':loc1', $user['Processing_value']);
    $stmt->execute();
    $info_product = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($info_product == false) {
        alert($textbotlang['users']['stateus']['error2'], true);
        step('home', $from_id);
        return;
    }
    if ($SellDiscountlimit['limitDiscount'] == $SellDiscountlimit['usedDiscount']) {
        sendmessage($from_id, $textbotlang['users']['Discount']['erorrlimit'], null, 'HTML');
        return;
    }
    if ($SellDiscountlimit['usefirst'] == "1") {
        $stmt = $pdo->prepare("SELECT * FROM invoice WHERE id_user = :id_user");
        $stmt->bindParam(':id_user', $from_id);
        $stmt->execute();
        $countinvoice = $stmt->rowCount();
        if ($countinvoice != 0) {
            sendmessage($from_id, $textbotlang['users']['Discount']['firstdiscount'], null, 'HTML');
            return;
        }
    }
    sendmessage($from_id, $textbotlang['users']['Discount']['correctcode'], $keyboard, 'HTML');
    step('payment', $from_id);
    $result = ($SellDiscountlimit['price'] / 100) * $info_product['price_product'];
    $info_product['price_product'] = $info_product['price_product'] - $result;
    $info_product['price_product'] = round($info_product['price_product']);
    if ($info_product['price_product'] < 0)
        $info_product['price_product'] = 0;
    $price_text = ($info_product['price_product'] <= 0) ? "رایگان" : number_format($info_product['price_product']) . " تومان";
    $textin = sprintf($textbotlang['users']['buy']['invoicebuy'], $info_product['name_product'], $user['Processing_value'], $price_text, $user['Processing_value_tow']);
    $paymentDiscount = json_encode([
        'inline_keyboard' => [
            [['text' => $textbotlang['users']['buy']['payandGet'], 'callback_data' => "confirmandgetserviceDiscount"]],
            [['text' => $textbotlang['users']['backmenu'], 'callback_data' => "backuser"]]
        ]
    ]);
    $parametrsendvalue = "dis_" . $text . "_" . $info_product['price_product'];
    update("user", "Processing_value_four", $parametrsendvalue, "id", $from_id);
    sendmessage($from_id, $textin, $paymentDiscount, 'HTML');
}

#-------------------text_Add_Balance---------------------#

if ($text == $datatextbot['text_Add_Balance'] || $datain == "wallet" || $text == "/wallet") {
    update("user", "Processing_value", "0", "id", $from_id);
    update("user", "Processing_value_one", "0", "id", $from_id);
    update("user", "Processing_value_tow", "0", "id", $from_id);
    if ($setting['get_number'] == "1" && $user['step'] != "get_number" && $user['number'] == "none") {
        sendmessage($from_id, $textbotlang['users']['number']['Confirming'], $request_contact, 'HTML');
        step('get_number', $from_id);
    }
    if ($user['number'] == "none" && $setting['get_number'] == "1")
        return;
    if (USER_INLINE_KEYBOARD) {
        Editmessagetext($from_id, $message_id, $textbotlang['users']['Balance']['priceinput'], $backuser);
    } else {
        sendmessage($from_id, $textbotlang['users']['Balance']['priceinput'], $backuser, 'HTML');
    }
    step('getprice', $from_id);
} elseif ($user['step'] == "getprice") {
    if (!is_numeric($text))
        return sendmessage($from_id, $textbotlang['users']['Balance']['errorprice'], null, 'HTML');
    if ($text > 10000000 or $text < 10000)
        return sendmessage($from_id, $textbotlang['users']['Balance']['errorpricelimit'], null, 'HTML');
    update("user", "Processing_value", $text, "id", $from_id);
    sendmessage($from_id, $textbotlang['users']['Balance']['selectPatment'], $step_payment, 'HTML');
    step('get_step_payment', $from_id);
} elseif ($user['step'] == "get_step_payment") {
    if ($datain == "cart_to_offline") {
        $PaySetting = select("PaySetting", "ValuePay", "NamePay", "CartDescription", "select")['ValuePay'];
        $Processing_value = number_format($user['Processing_value']);
        $textcart = sprintf($textbotlang['users']['moeny']['carttext'], $Processing_value, $PaySetting);
        preg_match_all('/\d+/', $PaySetting, $Matches);
        if (!empty($Matches[0]) && intval($setting['copy_cart']) == 1) {
            $peymentSettings['card_number'] = implode('', $Matches[0]);
            $MESSAGE = $textcart;
            $KEYBOARD = json_encode(["inline_keyboard" => [[['text' => $textbotlang['users']['moeny']['copy_card_number'], 'copy_text' => ['text' => $peymentSettings['card_number']]], ['text' => $textbotlang['users']['moeny']['copy_price'], 'copy_text' => ['text' => $user['Processing_value']]]], [['text' => $textbotlang['users']['backmenu'], 'callback_data' => 'backuser']]]]);
            Editmessagetext($from_id, $message_id, $MESSAGE, $KEYBOARD);
        } else {
            deletemessage($from_id, $message_id);
            sendmessage($from_id, $textcart, $backuser, 'HTML');
        }
        step('cart_to_cart_user', $from_id);
    }
    if ($datain == "aqayepardakht") {
        deletemessage($from_id, $message_id);
        if ($user['Processing_value'] < 5000) {
            sendmessage($from_id, $textbotlang['users']['Balance']['zarinpal'], null, 'HTML');
            return;
        }
        alert($textbotlang['users']['Balance']['linkpayments'], false);
        $dateacc = date('Y/m/d H:i:s');
        $randomString = bin2hex(random_bytes(5));
        $payment_Status = "Unpaid";
        $Payment_Method = "aqayepardakht";
        if ($user['Processing_value_tow'] == "getconfigafterpay") {
            $invoice = "{$user['Processing_value_tow']}|{$user['Processing_value_one']}";
        } else {
            $invoice = "0|0";
        }
        $stmt = $pdo->prepare("INSERT INTO Payment_report (id_user, id_order, time, price, payment_Status, Payment_Method,invoice) VALUES (?, ?, ?, ?, ?, ?,?)");
        $stmt->bindParam(1, $from_id);
        $stmt->bindParam(2, $randomString);
        $stmt->bindParam(3, $dateacc);
        $stmt->bindParam(4, $user['Processing_value'], PDO::PARAM_STR);
        $stmt->bindParam(5, $payment_Status);
        $stmt->bindParam(6, $Payment_Method);
        $stmt->bindParam(7, $invoice);
        $stmt->execute();
        $paymentkeyboard = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => $textbotlang['users']['Balance']['payments'], 'url' => "https://" . "$domainhosts" . "/payment/aqayepardakht/aqayepardakht.php?price={$user['Processing_value']}&order_id=$randomString"],
                ]
            ]
        ]);
        $user['Processing_value'] = number_format($user['Processing_value'], 0);
        $textnowpayments = sprintf($textbotlang['users']['moeny']['aqayepardakht'], $randomString, $user['Processing_value']);
        sendmessage($from_id, $textnowpayments, $paymentkeyboard, 'HTML');
    }
    if ($datain == "nowpayments") {
        deletemessage($from_id, $message_id);
        alert($textbotlang['users']['Balance']['linkpayments'], false);
        $price_rate = tronratee();
        $USD = $price_rate['result']['USD'];
        $usdprice = round($user['Processing_value'] / $USD, 2);
        $dateacc = date('Y/m/d H:i:s');
        $randomString = bin2hex(random_bytes(5));
        $payment_Status = "Unpaid";
        $Payment_Method = "Nowpayments";
        $invoice = $user['Processing_value_tow'] == "getconfigafterpay" ? "{$user['Processing_value_tow']}|{$user['Processing_value_one']}" : "0|0";
        $pay = nowPayments('invoice', $usdprice, $randomString, "order");
        if (!isset($pay['id'])) {
            $text_error = json_encode($pay);
            sendmessage($from_id, $textbotlang['users']['Balance']['errorLinkPayment'], $keyboard, 'HTML');
            step('home', $from_id);
            if (strlen($setting['Channel_Report']) > 0) {
                sendmessage($setting['Channel_Report'], sprintf($textbotlang['users']['moeny']['nowpayments_create_link_error'], $text_error, $from_id, $username), null, 'HTML');
            }
            return;
        }
        $stmt = $pdo->prepare("INSERT INTO Payment_report (id_user, id_order, time, price, payment_Status, Payment_Method,invoice) VALUES (?, ?, ?, ?, ?, ?,?)");
        $stmt->bindParam(1, $from_id);
        $stmt->bindParam(2, $randomString);
        $stmt->bindParam(3, $dateacc);
        $stmt->bindParam(4, $user['Processing_value'], PDO::PARAM_STR);
        $stmt->bindParam(5, $payment_Status);
        $stmt->bindParam(6, $Payment_Method);
        $stmt->bindParam(7, $invoice);
        $stmt->execute();
        $paymentkeyboard = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => $textbotlang['users']['Balance']['payments'], 'url' => $pay['invoice_url']],
                ]
            ]
        ]);
        $Processing_value = number_format($user['Processing_value'], 0);
        $USD = number_format($USD, 0);
        $textnowpayments = sprintf($textbotlang['users']['moeny']['nowpayment'], $Processing_value, $usdprice, $USD, $randomString);
        sendmessage($from_id, $textnowpayments, $paymentkeyboard, 'HTML');
    }
} elseif ($user['step'] == "cart_to_cart_user") {
    if (!$photo) {
        sendmessage($from_id, $textbotlang['users']['Balance']['Invalid-receipt'], null, 'HTML');
        return;
    }
    $dateacc = date('Y/m/d H:i:s');
    $randomString = bin2hex(random_bytes(5));
    $payment_Status = "waiting";
    $Payment_Method = "cart to cart";
    if ($user['Processing_value_tow'] == "getconfigafterpay") {
        $invoice = "{$user['Processing_value_tow']}|{$user['Processing_value_one']}";
    } else {
        $invoice = "0|0";
    }
    $stmt = $pdo->prepare("INSERT INTO Payment_report (id_user, id_order, time, price, payment_Status, Payment_Method,invoice) VALUES (?, ?, ?, ?, ?, ?,?)");
    $stmt->bindParam(1, $from_id);
    $stmt->bindParam(2, $randomString);
    $stmt->bindParam(3, $dateacc);
    $stmt->bindParam(4, $user['Processing_value'], PDO::PARAM_STR);
    $stmt->bindParam(5, $payment_Status);
    $stmt->bindParam(6, $Payment_Method);
    $stmt->bindParam(7, $invoice);
    $stmt->execute();
    if ($user['Processing_value_tow'] == "getconfigafterpay") {
        sendmessage($from_id, $textbotlang['users']['Balance']['Send-receip-buy'], $keyboard, 'HTML');
    } else {
        sendmessage($from_id, $textbotlang['users']['Balance']['Send-receipt'], $keyboard, 'HTML');
    }
    $Confirm_pay = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['Balance']['Confirmpaying'], 'callback_data' => "Confirm_pay_{$randomString}"],
                ['text' => $textbotlang['users']['Balance']['reject_pay'], 'callback_data' => "reject_pay_{$randomString}"],
            ]
        ]
    ]);
    $Processing_value = number_format($user['Processing_value']);
    $textsendrasid = sprintf($textbotlang['users']['moeny']['cartresid'], $from_id, $randomString, $username, $Processing_value, $caption);
    foreach ($admin_ids as $id_admin) {
        telegram('sendphoto', [
            'chat_id' => $id_admin,
            'photo' => $photoid,
            'reply_markup' => $Confirm_pay,
            'caption' => $textsendrasid,
            'parse_mode' => "HTML",
        ]);
    }
    step('home', $from_id);
}

#----------------Discount------------------#

if ($datain == "Discount") {
    sendmessage($from_id, $textbotlang['users']['Discount']['getcode'], $backuser, 'HTML');
    step('get_code_user', $from_id);
} elseif ($user['step'] == "get_code_user") {
    if (!in_array($text, $code_Discount)) {
        sendmessage($from_id, $textbotlang['users']['Discount']['notcode'], null, 'HTML');
        return;
    }
    $stmt = $pdo->prepare("SELECT * FROM Giftcodeconsumed WHERE id_user = :id_user");
    $stmt->bindParam(':id_user', $from_id);
    $stmt->execute();
    $Checkcode = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);
    if (in_array($text, $Checkcode)) {
        sendmessage($from_id, $textbotlang['users']['Discount']['onecode'], $keyboard, 'HTML');
        step('home', $from_id);
        return;
    }
    $stmt = $pdo->prepare("SELECT * FROM Discount WHERE code = :code LIMIT 1");
    $stmt->bindParam(':code', $text, PDO::PARAM_STR);
    $stmt->execute();
    $get_codesql = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$get_codesql) {
        sendmessage($from_id, $textbotlang['users']['Discount']['notcodeclear'], $keyboard, 'HTML');
        step('home', $from_id);
        return;
    }
    $balance_user = $user['Balance'] + $get_codesql['price'];
    update("user", "Balance", $balance_user, "id", $from_id);
    $stmt = $pdo->prepare("INSERT INTO Giftcodeconsumed (id_user, code) VALUES (?, ?)");
    $stmt->bindParam(1, $from_id);
    $stmt->bindParam(2, $text, PDO::PARAM_STR);
    $stmt->execute();
    $stmt = $pdo->prepare("DELETE FROM Discount WHERE code = :code");
    $stmt->bindParam(':code', $text, PDO::PARAM_STR);
    $stmt->execute();
    $price_formatted = number_format($get_codesql['price']);
    $text_balance_code = sprintf($textbotlang['users']['Discount']['acceptdiscount'], $price_formatted);
    sendmessage($from_id, $text_balance_code, $keyboard, 'HTML');
    step('home', $from_id);
    if (isset($setting['Channel_Report']) && strlen($setting['Channel_Report']) > 0) {
        $text_report = sprintf($textbotlang['users']['Report']['discountuser'], $text, $from_id, $username, $price_formatted);
        sendmessage($setting['Channel_Report'], $text_report, null, 'HTML');
    }
}

if ($text == $datatextbot['text_myid'] || $datain == "myid") {
    if (USER_INLINE_KEYBOARD) {
        Editmessagetext($from_id, $message_id, "آیدی عددی شما: <code>$from_id</code>", $backuser);
    } else {
        sendmessage($from_id, "آیدی عددی شما: <code>$from_id</code>", $keyboard, 'HTML');
    }
}

if ($text == $datatextbot['text_linkapp'] || $datain == "linkapp") {
    $linkappArray = [
        'inline_keyboard' => [
            [
                [
                    'text' => '🤖 HAPP Android',
                    'url' => 'https://play.google.com/store/apps/details?id=com.happproxy',
                    'style' => 'success'
                ],
                [
                    'text' => '📱HAPP iOS',
                    'url' => 'https://apps.apple.com/us/app/happ-proxy-utility/id6504287215',
                    'style' => 'success'
                ]
            ],
            [
                [
                    'text' => '🤖 V2rayNG Android',
                    'url' => 'https://github.com/2dust/v2rayNG/releases/download/2.2.4/v2rayNG_2.2.4_universal.apk',
                    'style' => 'primary'
                ],
                [
                    'text' => '🖥 V2rayN Windows',
                    'url' => 'https://github.com/2dust/v2rayN/releases/download/7.22.7/v2rayN-windows-64-desktop.zip',
                    'style' => 'primary'
                ],
            ],
            [
                [
                    'text' => '🤖 V2Box Android',
                    'url' => 'https://play.google.com/store/apps/details?id=dev.hexasoftware.v2box',
                    'style' => 'danger'
                ],
                [
                    'text' => '📱V2Box iOS',
                    'url' => 'https://apps.apple.com/us/app/v2box-v2ray-client/id6446814690',
                    'style' => 'danger'
                ]
            ]
        ]
    ];
    if (USER_INLINE_KEYBOARD) {
        $linkappArray['inline_keyboard'][] = [
            ['text' => $textbotlang['users']['backmenu'], 'callback_data' => "backuser", 'style' => 'primary']
        ];
    }
    $linkapp = json_encode($linkappArray, JSON_UNESCAPED_UNICODE);
    $messageText = "📗 لیست نرم‌افزارها به شرح زیر است، لطفاً یکی از موارد را انتخاب کنید\n\n🔶 می‌توانید به راحتی همه فایل‌ها را (به صورت رایگان) دریافت کنید.";
    if (USER_INLINE_KEYBOARD) {
        Editmessagetext($from_id, $message_id, $messageText, $linkapp);
    } else {
        sendmessage($from_id, $messageText, $linkapp, 'HTML');
    }
}

if ($text === $datatextbot['text_affiliates'] || $datain == "affiliates") {
    $affiliates = select("affiliates", "*", null, null, "select");
    if (!$affiliates || $affiliates['affiliatesstatus'] === "0") {
        if (USER_INLINE_KEYBOARD) {
            Editmessagetext($from_id, $message_id, $textbotlang['users']['affiliates']['offaffiliates'], $backuser);
        } else {
            sendmessage($from_id, $textbotlang['users']['affiliates']['offaffiliates'], $keyboard, 'HTML');
        }
        return;
    }
    $my_code = htmlspecialchars($user['ref_code']);
    $percentage = ($affiliates['status_commission'] === "oncommission") ? intval($affiliates['affiliatespercentage']) : 0;
    $text_affiliates = sprintf($textbotlang['users']['affiliates']['infotext'], intval($user['affiliatescount']), $percentage);
    $usernameBotSafe = htmlspecialchars($usernamebot);
    $ref_link = "https://t.me/{$usernameBotSafe}?start={$my_code}";
    if (USER_INLINE_KEYBOARD) {
        Editmessagetext($from_id, $message_id, "$text_affiliates\n\n$ref_link", $backuser);
    } else {
        sendmessage($from_id, "$text_affiliates\n\n$ref_link", $keyboard, 'HTML');
    }
}

if ($datain == "closelist") {
    deletemessage($from_id, $message_id);
    sendmessage($from_id, $textbotlang['users']['back'], $keyboard, 'HTML');
}

require_once 'admin.php';
$connect->close();