<?php
ini_set('error_log', 'error_log');
require_once 'vendor/autoload.php';

use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;

function telegram($method, $params = []) {
    global $APIKEY;
    $url = "https://api.telegram.org/bot$APIKEY/$method";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    $response = json_decode(curl_exec($ch), true);
    if (!$response['ok'] ?? false) {
        error_log(json_encode($response));
    }
    if (curl_error($ch)) {
        error_log(curl_error($ch));
    }
    curl_close($ch);
    return $response;
}

function sendmessage($chat_id, $text, $keyboard = null, $parse_mode = null) {
    return telegram('sendMessage', [
        'chat_id' => $chat_id,
        'text' => $text,
        'disable_web_page_preview' => true,
        'reply_markup' => $keyboard,
        'parse_mode' => $parse_mode,
    ]);
}

function deletemessage($chat_id, $message_id) {
    return telegram('deleteMessage', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
    ]);
}

function Editmessagetext($chat_id, $message_id, $text, $keyboard = null, $parse_mode = "HTML") {
    return telegram('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $text,
        'reply_markup' => $keyboard,
        'parse_mode' => $parse_mode,
    ]);
}

function forwardMessage($chat_id, $message_id, $to_chat_id) {
    return telegram('forwardMessage', [
        'from_chat_id' => $chat_id,
        'message_id' => $message_id,
        'chat_id' => $to_chat_id,
    ]);
}

function sendphoto($chat_id, $photo_id, $caption = '', $parse_mode = "HTML") {
    return telegram('sendPhoto', [
        'chat_id' => $chat_id,
        'photo' => $photo_id,
        'caption' => $caption,
        'parse_mode' => $parse_mode,
    ]);
}

function sendvideo($chat_id, $video_id, $caption = '') {
    return telegram('sendVideo', [
        'chat_id' => $chat_id,
        'video' => $video_id,
        'caption' => $caption,
    ]);
}

function sendDocument($chat_id, $documentPath, $caption = null) {
    return telegram('sendDocument', [
        'chat_id' => $chat_id,
        'document' => new CURLFile($documentPath),
        'caption' => $caption,
    ]);
}

function alert($txt, $type = false, $callid = null) {
    global $callback_query_id;
    $callid = $callid ?? $callback_query_id;
    if (!$callid) return false;
    return telegram('answerCallbackQuery', [
        'callback_query_id' => $callid,
        'text' => $txt,
        'show_alert' => $type
    ]);
}

function step($step, $from_id) {
    global $pdo;
    $stmt = $pdo->prepare('UPDATE user SET step = ? WHERE id = ?');
    $stmt->execute([$step, $from_id]);
}

function update($table, $field, $newValue, $whereField = null, $whereValue = null) {
    global $pdo, $user;
    $tables = [
        "user",
        "help",
        "setting",
        "admin",
        "channels",
        "marzban_panel",
        "product",
        "invoice",
        "Payment_report",
        "Discount",
        "Giftcodeconsumed",
        "textbot",
        "PaySetting",
        "DiscountSell",
        "affiliates",
        "category"
    ];
    if (!in_array($table, $tables)) return;
    if ($whereField !== null) {
        $sql = "UPDATE $table SET $field = ? WHERE $whereField = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$newValue, $whereValue]);
    } else {
        $sql = "UPDATE $table SET $field = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$newValue]);
    }
}

function select($table, $field, $whereField = null, $whereValue = null, $type = "select") {
    global $pdo;
    $query = "SELECT $field FROM $table";
    if ($whereField !== null) {
        $query .= " WHERE $whereField = :whereValue";
    }
    try {
        $stmt = $pdo->prepare($query);
        if ($whereField !== null) {
            $stmt->bindParam(':whereValue', $whereValue, PDO::PARAM_STR);
        }
        $stmt->execute();
        if ($type == "count") {
            return $stmt->rowCount();
        } elseif ($type == "FETCH_COLUMN") {
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } elseif ($type == "fetchAll") {
            return $stmt->fetchAll();
        } else {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        die("Query failed: " . $e->getMessage());
    }
}

function savedata($type, $namefiled, $valuefiled) {
    global $from_id;
    if ($type == "clear") {
        $datauser = [];
        $datauser[$namefiled] = $valuefiled;
        $data = json_encode($datauser);
        update("user", "Processing_value", $data, "id", $from_id);
    } elseif ($type == "save") {
        $userdata = select("user", "*", "id", $from_id, "select");
        $dataperevieos = json_decode($userdata['Processing_value'], true);
        $dataperevieos[$namefiled] = $valuefiled;
        update("user", "Processing_value", json_encode($dataperevieos), "id", $from_id);
    }
}

function addFieldToTable($tableName, $fieldName, $defaultValue = null, $datatype = "VARCHAR(500)") {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_name = :tableName");
    $stmt->bindParam(':tableName', $tableName);
    $stmt->execute();
    $tableExists = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($tableExists['count'] == 0)
        return;
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $stmt->execute([$pdo->query("SELECT DATABASE()")->fetchColumn(), $tableName, $fieldName]);
    $filedExists = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($filedExists['count'] != 0)
        return;
    $query = "ALTER TABLE $tableName ADD $fieldName $datatype";
    $statement = $pdo->prepare($query);
    $statement->execute();
    if ($defaultValue != null) {
        $stmt = $pdo->prepare("UPDATE $tableName SET $fieldName= ?");
        $stmt->bindParam(1, $defaultValue);
        $stmt->execute();
    }
    echo "The $fieldName field was added ✅";
}

function tronratee() {
    $tronrate = [];
    $requeststron = json_decode(file_get_contents('https://api.diadata.org/v1/assetQuotation/Tron/0x0000000000000000000000000000000000000000'), true);
    $requestsusd = json_decode(file_get_contents('https://api.wallex.ir/v1/markets'), true);
    $tronrate['result']['USD'] = intval($requestsusd['result']['symbols']['USDTTMN']['stats']['lastPrice']);
    $tronrate['result']['TRX'] = intval($requeststron['Price'] * $tronrate['result']['USD']);
    return $tronrate;
}

function nowPayments($payment, $price_amount, $order_id, $order_description) {
    global $domainhosts;
    $apinowpayments = select("PaySetting", "ValuePay", "NamePay", 'apinowpayment', "select")['ValuePay'];
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.nowpayments.io/v1/' . $payment,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT_MS => 4500,
        CURLOPT_ENCODING => '',
        CURLOPT_SSL_VERIFYPEER => 1,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => array(
            'x-api-key:' . $apinowpayments,
            'Content-Type: application/json'
        ),
    ));
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode([
        'price_amount' => $price_amount,
        'price_currency' => 'usd',
        'pay_currency' => 'trx',
        'order_id' => $order_id,
        'order_description' => $order_description,
        'ipn_callback_url' => "https://" . $domainhosts . "/payment/nowpayments/back.php"
    ]));
    $response = curl_exec($curl);
    curl_close($curl);
    return json_decode($response, true);
}

function StatusPayment($paymentid) {
    $apinowpayments = select("PaySetting", "ValuePay", "NamePay", 'apinowpayment', "select")['ValuePay'];
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.nowpayments.io/v1/payment/' . $paymentid,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'x-api-key:' . $apinowpayments
        ),
    ));
    $response = curl_exec($curl);
    $response = json_decode($response, true);
    curl_close($curl);
    return $response;
}

function outputlink($text) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $text);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';
    curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
    $response = curl_exec($ch);
    if ($response === false) {
        $error = curl_error($ch);
        return "";
    } else {
        return $response;
    }
    curl_close($ch);
}

function DirectPayment($order_id) {
    global $pdo, $ManagePanel, $textbotlang, $keyboard, $from_id, $message_id, $callback_query_id;
    $setting = select("setting", "*");
    $admin_ids = select("admin", "id_admin", null, null, "FETCH_COLUMN");
    $Payment_report = select("Payment_report", "*", "id_order", $order_id, "select");
    $format_price_cart = number_format($Payment_report['price']);
    $Balance_id = select("user", "*", "id", $Payment_report['id_user'], "select");
    $steppay = explode("|", $Payment_report['invoice']);
    if ($steppay[0] == "getconfigafterpay") {
        $stmt = $pdo->prepare("SELECT * FROM invoice WHERE username = :username AND Status = 'unpaid' LIMIT 1");
        $stmt->bindParam(':username', $steppay[1], PDO::PARAM_STR);
        $stmt->execute();
        $get_invoice = $stmt->fetch(PDO::FETCH_ASSOC);
        $username_ac = $get_invoice['username'];
        $randomString = bin2hex(random_bytes(2));
        $marzban_list_get = select("marzban_panel", "*", "name_panel", $get_invoice['Service_location'], "select");
        $date = strtotime("+" . $get_invoice['Service_time'] . "days");
        if (intval($get_invoice['Service_time']) == 0) {
            $timestamp = 0;
        } else {
            $timestamp = strtotime(date("Y-m-d H:i:s", $date));
        }
        $datac = array(
            'expire' => $timestamp,
            'data_limit' => $get_invoice['Volume'] * pow(1024, 3),
        );
        $dataoutput = $ManagePanel->createUser($marzban_list_get['name_panel'], $username_ac, $datac);
        if ($dataoutput['username'] == null) {
            $dataoutput['msg'] = json_encode($dataoutput['msg']);
            sendmessage($Balance_id['id'], $textbotlang['users']['sell']['ErrorConfig'], $keyboard, 'HTML');
            $texterros = sprintf($textbotlang['users']['buy']['errorInCreate'], $dataoutput['msg'], $Balance_id['id'], $Balance_id['username']);
            foreach ($admin_ids as $admin) {
                sendmessage($admin, $texterros, null, 'HTML');
                step('home', $admin);
            }
            return;
        }
        $output_config_link = "";
        $config = "";
        $Shoppinginfo = [
            'inline_keyboard' => [
                [
                    ['text' => $textbotlang['users']['help']['btninlinebuy'], 'callback_data' => "helpbtn"],
                ]
            ]
        ];
        if ($marzban_list_get['sublink'] == "onsublink") {
            $output_config_link = $dataoutput['subscription_url'];
        }
        $configqr = "";
        if ($marzban_list_get['configManual'] == "onconfig") {
            if (isset($dataoutput['configs']) and count($dataoutput['configs']) != 0) {
                foreach ($dataoutput['configs'] as $configs) {
                    $config .= "\n" . $configs;
                    $configqr .= $configs;
                }
            } else {
                $config .= "";
                $configqr .= "";
            }
        }
        $Shoppinginfo = json_encode($Shoppinginfo);
        if ($marzban_list_get['type'] == "marzban") {
            $textcreatuser = sprintf($textbotlang['users']['buy']['createservice'], $dataoutput['username'], $get_invoice['name_product'], $marzban_list_get['name_panel'], $get_invoice['Service_time'], $get_invoice['Volume'], $config, $output_config_link);
        } else {
            $textcreatuser = sprintf($textbotlang['users']['buy']['createservice'], $dataoutput['username'], $get_invoice['name_product'], $marzban_list_get['name_panel'], $get_invoice['Service_time'], $get_invoice['Volume'], $config, $output_config_link);
        }
        if ($marzban_list_get['configManual'] == "onconfig") {
            if (count($dataoutput['configs']) == 1) {
                $urlimage = "{$get_invoice['id_user']}$randomString.png";
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
                    'chat_id' => $get_invoice['id_user'],
                    'photo' => new CURLFile($urlimage),
                    'reply_markup' => $Shoppinginfo,
                    'caption' => $textcreatuser,
                    'parse_mode' => "HTML",
                ]);
                unlink($urlimage);
            } else {
                sendmessage($get_invoice['id_user'], $textcreatuser, $Shoppinginfo, 'HTML');
            }
        } elseif ($marzban_list_get['sublink'] == "onsublink") {
            $urlimage = "{$get_invoice['id_user']}$randomString.png";
            $writer = new PngWriter();
            $qrCode = QrCode::create($output_config_link)
                ->setEncoding(new Encoding('UTF-8'))
                ->setErrorCorrectionLevel(ErrorCorrectionLevel::Low)
                ->setSize(400)
                ->setMargin(0)
                ->setRoundBlockSizeMode(RoundBlockSizeMode::Margin);
            $result = $writer->write($qrCode, null, null);
            $result->saveToFile($urlimage);
            telegram('sendphoto', [
                'chat_id' => $get_invoice['id_user'],
                'photo' => new CURLFile($urlimage),
                'reply_markup' => $Shoppinginfo,
                'caption' => $textcreatuser,
                'parse_mode' => "HTML",
            ]);
            unlink($urlimage);
        }
        $partsdic = explode("_", $Balance_id['Processing_value_four']);
        if ($partsdic[0] == "dis") {
            $SellDiscountlimit = select("DiscountSell", "*", "codeDiscount", $partsdic[1], "select");
            $value = intval($SellDiscountlimit['usedDiscount']) + 1;
            update("DiscountSell", "usedDiscount", $value, "codeDiscount", $partsdic[1]);
            $stmt = $pdo->prepare("INSERT INTO Giftcodeconsumed (id_user,code) VALUES (:id_user,:code)");
            $stmt->bindParam(':id_user', $Balance_id['id']);
            $stmt->bindParam(':code', $partsdic[1]);
            $stmt->execute();
            $result = ($SellDiscountlimit['price'] / 100) * $get_invoice['price_product'];
            $pricediscount = $get_invoice['price_product'] - $result;
            $text_report = sprintf($textbotlang['users']['Report']['discountused'], $Balance_id['username'], $Balance_id['id'], $partsdic[1]);
            if (strlen($setting['Channel_Report']) > 0) {
                telegram('sendmessage', [
                    'chat_id' => $setting['Channel_Report'],
                    'text' => $text_report,
                ]);
            }
        } else {
            $pricediscount = null;
        }
        $affiliatescommission = select("affiliates", "*", null, null, "select");
        if ($affiliatescommission['status_commission'] == "oncommission" && ($Balance_id['affiliates'] !== null || $Balance_id['affiliates'] != 0)) {
            if ($pricediscount == null) {
                $result = ($get_invoice['price_product'] * $affiliatescommission['affiliatespercentage']) / 100;
            } else {
                $result = ($pricediscount * $affiliatescommission['affiliatespercentage']) / 100;
            }
            $user_Balance = select("user", "*", "id", $Balance_id['affiliates'], "select");
            if (isset($user_Balance)) {
                $Balance_prim = $user_Balance['Balance'] + $result;
                update("user", "Balance", $Balance_prim, "id", $Balance_id['affiliates']);
                $result = number_format($result);
                $textadd = sprintf($textbotlang['users']['affiliates']['porsantuser'], $result);
                sendmessage($Balance_id['affiliates'], $textadd, null, 'HTML');
            }
        }
        $Balance_prims = $Balance_id['Balance'] - $get_invoice['price_product'];
        if ($Balance_prims <= 0)
            $Balance_prims = 0;
        update("user", "Balance", $Balance_prims, "id", $Balance_id['id']);
        $Balance_id['Balance'] = select("user", "Balance", "id", $get_invoice['id_user'], "select")['Balance'];
        $balanceformatsell = number_format($Balance_id['Balance'], 0);
        $text_report = sprintf($textbotlang['users']['Report']['reportbuyafterpay'], $get_invoice['username'], $get_invoice['price_product'], $get_invoice['Volume'], $get_invoice['id_user'], $Balance_id['number'], $get_invoice['Service_location'], $balanceformatsell, $Balance_id['username']);
        if (strlen($setting['Channel_Report']) > 0) {
            telegram('sendmessage', [
                'chat_id' => $setting['Channel_Report'],
                'text' => $text_report,
                'parse_mode' => "HTML"
            ]);
        }
        update("invoice", "status", "active", "username", $get_invoice['username']);
        if ($Payment_report['Payment_Method'] == "cart to cart") {
            update("invoice", "Status", "active", "id_invoice", $get_invoice['id_invoice']);
        }
    } else {
        $Balance_confrim = intval($Balance_id['Balance']) + intval($Payment_report['price']);
        update("user", "Balance", $Balance_confrim, "id", $Payment_report['id_user']);
        update("Payment_report", "payment_Status", "paid", "id_order", $Payment_report['id_order']);
        $Payment_report['price'] = number_format($Payment_report['price'], 0);
        $format_price_cart = $Payment_report['price'];
        if ($Payment_report['Payment_Method'] == "cart to cart") {
            telegram(
                'answerCallbackQuery',
                array(
                    'callback_query_id' => $callback_query_id,
                    'text' => $textbotlang['users']['moeny']['acceptedcart'],
                    'show_alert' => true,
                    'cache_time' => 5,
                )
            );
        }
        $textpay = sprintf($textbotlang['users']['moeny']['Charged.'], $Payment_report['price'], $Payment_report['id_order']);
        sendmessage($Payment_report['id_user'], $textpay, null, 'HTML');
    }
}

function deleteFolder($folderPath) {
    if (!is_dir($folderPath))
        return false;
    $files = array_diff(scandir($folderPath), ['.', '..']);
    foreach ($files as $file) {
        $filePath = $folderPath . DIRECTORY_SEPARATOR . $file;
        if (is_dir($filePath)) {
            deleteFolder($filePath);
        } else {
            unlink($filePath);
        }
    }
    return rmdir($folderPath);
}

function checktelegramip() {
    $telegram_ip_ranges = [
        ['lower' => '149.154.160.0', 'upper' => '149.154.175.255'],
        ['lower' => '91.108.4.0', 'upper' => '91.108.7.255']
    ];
    $ip_dec = (float) sprintf("%u", ip2long($_SERVER['REMOTE_ADDR']));
    $ok = false;
    foreach ($telegram_ip_ranges as $telegram_ip_range)
        if (!$ok) {
            $lower_dec = (float) sprintf("%u", ip2long($telegram_ip_range['lower']));
            $upper_dec = (float) sprintf("%u", ip2long($telegram_ip_range['upper']));
            if ($ip_dec >= $lower_dec and $ip_dec <= $upper_dec)
                $ok = true;
        }
    return $ok;
}

function channel($id_channel) {
    global $from_id, $APIKEY;
    $channel_link = array();
    if(!$id_channel)return [];
    $response = telegram('getChatMember', [
        "chat_id" => "@$id_channel",
        "user_id" => $from_id,
    ]);
    if ($response['ok']) {
        if (!in_array($response['result']['status'], ['member', 'creator', 'administrator'])) {
            $channel_link[] = $id_channel;
        }
    }
    if (count($channel_link) == 0) {
        return [];
    } else {
        return $channel_link;
    }
}

function isBase64($string) {
    if (base64_encode(base64_decode($string, true)) === $string) {
        return true;
    }
    return false;
}

function sanitizeUserName($string) {
    $forbiddenCharacters = ["'", "\"", "<", ">", "--", "#", ";", "\\", "%", "(", ")"];
    return str_replace($forbiddenCharacters, "", $string);
}

function generateAuthStr($length = 10) {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    return substr(str_shuffle(str_repeat($characters, ceil($length / strlen($characters)))), 0, $length);
}

function formatBytes($bytes, $precision = 2): string {
    global $textbotlang;
    $base = log($bytes, 1024);
    $power = $bytes > 0 ? floor($base) : 0;
    $suffixes = [$textbotlang['users']['format']['byte'], $textbotlang['users']['format']['kilobyte'], $textbotlang['users']['format']['MBbyte'], $textbotlang['users']['format']['GBbyte'], $textbotlang['users']['format']['TBbyte']];
    return round(pow(1024, $base - $power), $precision) . ' ' . $suffixes[$power];
}

function generateUUID() {
    $data = openssl_random_pseudo_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    return $uuid;
}

function generateUsername($from_id, $Metode, $username, $randomString) {
    global $connect, $textbotlang;
    $setting = select("setting", "*");
    if ($Metode == $textbotlang['users']['customidAndRandom']) {
        return $from_id."_".$randomString;
    } elseif ($Metode == $textbotlang['users']['customusernameandorder']) {
        return $username."_".$randomString;
    } elseif ($Metode == $textbotlang['users']['customusernameorder']) {
        $statistics = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(id_user)  FROM invoice WHERE id_user = '$from_id'"));
        $countInvoice = intval($statistics['COUNT(id_user)']) + 1 ;
        return $username."_".$countInvoice;
    } elseif ($Metode == $textbotlang['users']['customtextandrandom']) {
        $query = mysqli_query($connect, "SELECT COUNT(*) AS total_users FROM invoice");
        if (!$query) {
            return "error: db_query_failed";
        }
        $result = mysqli_fetch_assoc($query);
        $countInvoice = 100 + intval($result['total_users']) + 1;
        return strtolower($setting['namecustome']."_".$countInvoice);
    }
}

function outtypepanel($typepanel, $message) {
    global $from_id, $optionMarzban, $optionX_ui_single, $optionMarzneshin;
    if ($typepanel == "marzban") {
        sendmessage($from_id, $message, $optionMarzban, 'HTML');
    } elseif ($typepanel == "x-ui" || $typepanel == "alireza") {
        sendmessage($from_id, $message, $optionX_ui_single, 'HTML');
    } elseif ($typepanel == "marzneshin") {
        sendmessage($from_id, $message, $optionMarzneshin, 'HTML');
    }
}

function buildListPanelKeyboard($pdo, $textbotlang) {
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'marzban_panel'");
    $stmt->execute();
    if ($stmt->rowCount() === 0) return null;
    $stmt = $pdo->prepare("SELECT * FROM marzban_panel");
    $stmt->execute();
    $panels = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $inline_keyboard = [];
    $inline_keyboard[] = [
        ['text' => "وضعیت نمایش", 'callback_data' => "ignore"],
        ['text' => "وضعیت اکانت تست", 'callback_data' => "ignore"],
        ['text' => "حذف", 'callback_data' => "ignore"],
        ['text' => "ویرایش", 'callback_data' => "ignore"],
        ['text' => "نام", 'callback_data' => "ignore"]
    ];
    foreach ($panels as $p) {
        $status_emoji = $p['status'] == 'activepanel' ? "✅" : "❌";
        $status_test  = $p['statusTest'] == 'ontestshowpanel' ? "✅" : "❌";
        $inline_keyboard[] = [
            ['text' => $status_emoji, 'callback_data' => "toggle_status:{$p['id']}"],
            ['text' => $status_test,  'callback_data' => "toggle_test:{$p['id']}"],
            ['text' => "❌", 'callback_data' => "delete_panel:{$p['id']}"],
            ['text' => "✏️", 'callback_data' => "edit_panel:{$p['id']}"],
            ['text' => $p['name_panel'], 'callback_data' => "ignore"]
        ];
    }
    $inline_keyboard[] = [
        ['text' => $textbotlang['Admin']['Back-Adminment'], 'callback_data' => 'back_admin']
    ];
    return json_encode(['inline_keyboard' => $inline_keyboard], JSON_UNESCAPED_UNICODE);
}

function togglePanelStatus($pdo, $panel_id, $mode = 'status') {
    $stmt = $pdo->prepare("SELECT * FROM marzban_panel WHERE id = ?");
    $stmt->execute([$panel_id]);
    $panel = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$panel) return false;
    if ($mode === 'status') {
        $new_status = ($panel['status'] == 'activepanel') ? 'disablepanel' : 'activepanel';
        $stmt = $pdo->prepare("UPDATE marzban_panel SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $panel_id]);
    } elseif ($mode === 'test') {
        $new_test_status = ($panel['statusTest'] == 'ontestshowpanel') ? 'offtestshowpanel' : 'ontestshowpanel';
        $stmt = $pdo->prepare("UPDATE marzban_panel SET statusTest = ? WHERE id = ?");
        $stmt->execute([$new_test_status, $panel_id]);
    } else {
        return false;
    }
    return true;
}

if (!function_exists('generateProgressBar')) {
    function generateProgressBar($percent, $length = 10) {
        $percent = max(0, min(100, (float)$percent));
        $filled  = (int)round(($percent / 100) * $length);
        $empty   = $length - $filled;
        return str_repeat('🟩', $filled) . str_repeat('⬜️', $empty);
    }
}

if (!function_exists('respondView')) {
    function respondView($from_id, $message_id, $text, $keyboard, $parseMode = 'HTML') {
        if (defined('USER_INLINE_KEYBOARD') && USER_INLINE_KEYBOARD) {
            editmessagetext($from_id, $message_id, $text, $keyboard, $parseMode);
        } else {
            sendmessage($from_id, $text, $keyboard, $parseMode);
        }
    }
}

$update            = json_decode(file_get_contents("php://input"), true);
$from_id           = $update['message']['from']['id'] ?? $update['callback_query']['from']['id'] ?? 0;
$Chat_type         = $update['message']['chat']['type'] ?? $update['callback_query']['message']['chat']['type'] ?? '';
$text              = $update['message']['text'] ?? '';
$text_callback     = $update['callback_query']['message']['text'] ?? '';
$message_id        = $update['message']['message_id'] ?? $update['callback_query']['message']['message_id'] ?? 0;
$photo             = $update['message']['photo'] ?? [];
$photoid           = $photo ? end($photo)['file_id'] : '';
$caption           = $update['message']['caption'] ?? $update['callback_query']['message']['caption'] ?? '';
$video             = $update['message']['video'] ?? null;
$videoid           = $video['file_id'] ?? '';
$forward_from_id   = $update['message']['reply_to_message']['forward_from']['id'] ?? 0;
$datain            = $update['callback_query']['data'] ?? '';
$username          = $update['message']['from']['username'] ?? $update['callback_query']['from']['username'] ?? 'NOT_USERNAME';
$user_phone        = $update['message']['contact']['phone_number'] ?? '';
$contact_id        = $update['message']['contact']['user_id'] ?? '';
$first_name        = $update['message']['from']['first_name'] ?? $update['callback_query']['from']['first_name'] ?? '';
$callback_query_id = $update['callback_query']['id'] ?? 0;