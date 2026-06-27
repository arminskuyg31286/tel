<?php

if (!in_array($from_id, $admin_ids)) {
    return;
}

$textadmin = ["panel", "/panel", $textbotlang['Admin']['commendadmin']];
if (in_array($text, $textadmin) || $datain == "PANEL") {
    if (USER_INLINE_KEYBOARD) {
        Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Back-Admin'], $keyboardadmin);
    } else {
        sendmessage($from_id, $textbotlang['Admin']['Back-Admin'], $keyboardadmin, 'HTML');
    }
    return;
}

if ($text == $textbotlang['Admin']['Back-Adminment'] || $datain == "back_admin") {
    if (USER_INLINE_KEYBOARD) {
        Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Back-Admin'], $keyboardadmin);
    } else {
        sendmessage($from_id, $textbotlang['Admin']['Back-Admin'], $keyboardadmin, 'HTML');
    }
    step('home', $from_id);
    return;
}

if ($text == $textbotlang['Admin']['Addedadmin'] || $datain == "addadmin") {
    if (USER_INLINE_KEYBOARD) {
        Editmessagetext($from_id, $message_id, $textbotlang['Admin']['manageadmin']['getid'], $backadmin);
    } else {
        sendmessage($from_id, $textbotlang['Admin']['manageadmin']['getid'], $backadmin, 'HTML');
    }
    step('addadmin', $from_id);
} elseif ($user['step'] == "addadmin") {
    if (!is_numeric($text) || strlen($text) < 5) {
        sendmessage($from_id, "❌ شناسه کاربری (ID) وارد شده معتبر نیست.", $backadmin, 'HTML');
        return;
    }
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user WHERE id = ?");
    $stmt->execute([$text]);
    $userExists = $stmt->fetchColumn();
    if ($userExists == 0) {
        sendmessage($from_id, "💢 این کاربر در ربات عضو نمی‌ باشد!", $backadmin, 'HTML');
        return;
    }
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin WHERE id_admin = ?");
    $stmt->execute([$text]);
    $exists = $stmt->fetchColumn();
    if ($exists > 0) {
        sendmessage($from_id, "⚠️ این کاربر قبلاً در لیست ادمین‌ها وجود دارد.", $backadmin, 'HTML');
        step('home', $from_id);
        return;
    }
    $stmt = $pdo->prepare("INSERT INTO admin (id_admin) VALUES (?)");
    $stmt->bindParam(1, $text, PDO::PARAM_INT);
    $stmt->execute();
    sendmessage($text, "🎉 شما به لیست ادمین‌های ربات اضافه شدید", $keyboardadmin, 'HTML');
    sendmessage($from_id, $textbotlang['Admin']['manageadmin']['addadminset'], $admin_section_panel, 'HTML');
    step('home', $from_id);
} elseif ($text == $textbotlang['Admin']['Removeedadmin'] || $datain == "removeadmin") {
    if (USER_INLINE_KEYBOARD) {
        Editmessagetext($from_id, $message_id, $textbotlang['Admin']['manageadmin']['getid'], $backadmin);
    } else {
        sendmessage($from_id, $textbotlang['Admin']['manageadmin']['getid'], $backadmin, 'HTML');
    }
    step('deleteadmin', $from_id);
} elseif ($user['step'] == "deleteadmin") {
    if (!is_numeric($text) || strlen($text) < 5) {
        sendmessage($from_id, "❌ شناسه کاربری (ID) وارد شده معتبر نیست.", $backadmin, 'HTML');
        return;
    }
    if (intval($text) == intval($from_id)) {
        sendmessage($from_id, "⚠️ شما نمی‌ توانید خودتان را از لیست ادمین‌ ها حذف کنید!", $backadmin, 'HTML');
        return;
    }
    if (intval($text) == $adminnumber) {
        sendmessage($from_id, $textbotlang['Admin']['manageadmin']['InfoAdd'], $backadmin, 'HTML');
        return;
    }
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin WHERE id_admin = ?");
    $stmt->execute([$text]);
    $exists = $stmt->fetchColumn();
    if ($exists == 0) {
        sendmessage($from_id, "⚠️ چنین ادمینی در لیست وجود ندارد.", $backadmin, 'HTML');
        step('home', $from_id);
        return;
    }
    $stmt = $pdo->prepare("DELETE FROM admin WHERE id_admin = ?");
    $stmt->execute([$text]);
    sendmessage($from_id, $textbotlang['Admin']['manageadmin']['removedadmin'], $admin_section_panel, 'HTML');
    step('home', $from_id);
} elseif ($text == $textbotlang['Admin']['manageadmin']['showlistbtn'] || $datain == "showlist") {
    deletemessage($from_id, $message_id);
    $stmt = $pdo->query("SELECT id_admin FROM admin ORDER BY id_admin ASC");
    $admins = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (empty($admins)) {
        sendmessage($from_id, "⚠️ هنوز هیچ ادمینی ثبت نشده است.", $backadmin, 'HTML');
        return;
    }
    $inline_buttons = [];
    $inline_buttons[] = [
        ['text' => "❗️ حزف شخص", 'callback_data' => "salam"],
        ['text' => "👤 شناسه کاربر", 'callback_data' => "salam"]
    ];
    foreach ($admins as $admin_id) {
        $inline_buttons[] = [
            ['text' => "حذف", 'callback_data' => "deladmin_$admin_id"],
            ['text' => "$admin_id", 'callback_data' => "admininfo_$admin_id"]
        ];
    }
    $inline_buttons[] = [
        ['text' => "🔙 بازگشت", 'callback_data' => "back_admin"]
    ];
    $keyboard_admin_list = json_encode(['inline_keyboard' => $inline_buttons], JSON_UNESCAPED_UNICODE);
    sendmessage($from_id, "🧾 لیست ادمین‌ های فعلی:", $keyboard_admin_list, 'HTML');
} elseif (strpos($datain, "deladmin_") === 0) {
    deletemessage($from_id, $message_id);
    $admin_id_to_delete = str_replace("deladmin_", "", $datain);
    if (intval($admin_id_to_delete) == intval($from_id)) {
        sendmessage($from_id, "⚠️ شما نمی‌ توانید خودتان را از لیست ادمین‌ ها حذف کنید!", $admin_section_panel, 'HTML');
        return;
    }
    if (intval($admin_id_to_delete) == $adminnumber) {
        sendmessage($from_id, $textbotlang['Admin']['manageadmin']['InfoAdd'], $admin_section_panel, 'HTML');
        return;
    }
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin WHERE id_admin = ?");
    $stmt->execute([$admin_id_to_delete]);
    $exists = $stmt->fetchColumn();
    if ($exists == 0) {
        sendmessage($from_id, "❌ این ادمین دیگر وجود ندارد.", $admin_section_panel, 'HTML');
        return;
    }
    $stmt = $pdo->prepare("DELETE FROM admin WHERE id_admin = ?");
    $stmt->execute([$admin_id_to_delete]);
    sendmessage($from_id, $textbotlang['Admin']['manageadmin']['removedadmin'], $admin_section_panel, 'HTML');
}

if ($text == $textbotlang['Admin']['channel']['changechannelbtn'] || $datain == "changechannelbtn") {
    $msg = $textbotlang['Admin']['channel']['changechannel'] . $channels['link'];
    if (USER_INLINE_KEYBOARD) {
        Editmessagetext($from_id, $message_id, $msg, $backadmin);
    } else {
        sendmessage($from_id, $msg, $backadmin, 'HTML');
    }
    step('addchannel', $from_id);
    return;
} elseif ($user['step'] == "addchannel") {
    sendmessage($from_id, $textbotlang['Admin']['channel']['setchannel'], $keyboardadmin, 'HTML');
    step('home', $from_id);
    $Check_field = $connect->query("SHOW COLUMNS FROM channels LIKE 'Channel_lock'");
    if ($Check_field && $Check_field->num_rows == 1) {
        $connect->query("ALTER TABLE channels DROP COLUMN Channel_lock;");
    }
    $channels_ch = select("channels", "link", null, null, "count");
    if ($channels_ch == 0) {
        $stmt = $pdo->prepare("INSERT INTO channels (link) VALUES (?)");
        $stmt->bindParam(1, $text, PDO::PARAM_STR);
        $stmt->execute();
    } else {
        update("channels", "link", $text);
    }
}

if (preg_match('/limitusertest_(.*)/', $datain, $dataget)) {
    $id_user = $dataget[1];
    sendmessage($from_id, $textbotlang['Admin']['getlimitusertest']['getid'], $backadmin, 'HTML');
    update("user", "Processing_value", $id_user, "id", $from_id);
    step('get_number_limit', $from_id);
} elseif ($user['step'] == "get_number_limit") {
    sendmessage($from_id, $textbotlang['Admin']['getlimitusertest']['setlimit'], $keyboardadmin, 'HTML');
    $id_user_set = $text;
    update("user", "limit_usertest", $text, "id", $user['Processing_value']);
    step('home', $from_id);
} elseif ($text == $textbotlang['Admin']['getlimitusertest']['setlimitallbtn'] || $datain == "setlimitall") {
    sendmessage($from_id, $textbotlang['Admin']['getlimitusertest']['limitall'], $backadmin, 'HTML');
    step('limit_usertest_allusers', $from_id);
} elseif ($user['step'] == "limit_usertest_allusers") {
    sendmessage($from_id, $textbotlang['Admin']['getlimitusertest']['setlimitall'], $keyboard_usertest, 'HTML');
    update("setting", "limit_usertest_all", $text);
    update("user", "limit_usertest", $text);
    step('home', $from_id);
}

if ($text == $textbotlang['Admin']['keyboardadmin']['bot_statistics'] || $datain == "bot_statistics") {
    $current_time = time();
    $today_start = strtotime("today");
    $yesterday_start = strtotime("yesterday");
    $week_start = strtotime("-7 days");
    $month_start = strtotime("-30 days");
    $Balanceall = select("user", "SUM(Balance)", null, null, "select");
    $statistics = select("user", "*", null, null, "count");
    $sumpanel = select("marzban_panel", "*", null, null, "count");
    $sqlinvoice = "SELECT * FROM invoice WHERE Status IN ('active','end_of_time','end_of_volume','sendedwarn') AND name_product != 'usertest'";
    $stmt = $pdo->prepare($sqlinvoice);
    $stmt->execute();
    $invoice_count = $stmt->rowCount();
    $sql = "SELECT SUM(price_product) AS total_price FROM invoice WHERE Status IN ('active','end_of_time','end_of_volume','sendedwarn') AND name_product != 'usertest'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $invoicesum = $stmt->fetch(PDO::FETCH_ASSOC)['total_price'] ?? 0;
    $sql = "SELECT COUNT(*) AS paid_invoices FROM Payment_report WHERE payment_Status = 'paid'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $paidInvoices = $stmt->fetch(PDO::FETCH_ASSOC)['paid_invoices'] ?? 0;
    $sql = "SELECT SUM(price_product) AS total_price FROM invoice WHERE time_sell >= :time_start AND name_product != 'usertest'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':time_start' => $today_start]);
    $today_sales = $stmt->fetch(PDO::FETCH_ASSOC)['total_price'] ?? 0;
    $sql = "SELECT SUM(price_product) AS total_price FROM invoice WHERE time_sell >= :yesterday_start AND time_sell < :today_start AND name_product != 'usertest'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':yesterday_start' => $yesterday_start, ':today_start' => $today_start]);
    $yesterday_sales = $stmt->fetch(PDO::FETCH_ASSOC)['total_price'] ?? 0;
    $sql = "SELECT SUM(price_product) AS total_price FROM invoice WHERE time_sell >= :week_start AND name_product != 'usertest'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':week_start' => $week_start]);
    $week_sales = $stmt->fetch(PDO::FETCH_ASSOC)['total_price'] ?? 0;
    $sql = "SELECT SUM(price_product) AS total_price FROM invoice WHERE time_sell >= :month_start AND name_product != 'usertest'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':month_start' => $month_start]);
    $month_sales = $stmt->fetch(PDO::FETCH_ASSOC)['total_price'] ?? 0;
    include_once("jdf.php");
    $datetime = jdate('l j F Y ، ساعت H:i:s', $current_time);
    $statisticsall = sprintf($textbotlang['Admin']['Statistics']['info'], $datetime, $statistics, $paidInvoices, $invoice_count, $sumpanel, number_format($Balanceall['SUM(Balance)'] ?? 0), number_format($invoicesum), number_format($today_sales), number_format($yesterday_sales), number_format($week_sales), number_format($month_sales));
    if (USER_INLINE_KEYBOARD) {
        Editmessagetext($from_id, $message_id, $statisticsall, $backadmin);
    } else {
        sendmessage($from_id, $statisticsall, null, 'HTML');
    }
}

if ($text == $textbotlang['Admin']['keyboardadmin']['add_panel'] || $datain == "add_panel") {
    if (USER_INLINE_KEYBOARD) {
        Editmessagetext($from_id, $message_id, $textbotlang['Admin']['managepanel']['selecttypepanel'], $typepanel);
    } else {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['selecttypepanel'], $typepanel, 'HTML');
    }  
} elseif (preg_match('/typepanel%(.*)/', $datain, $dataget)) {
    $type = $dataget[1];
    savedata("clear", "type", $type);
    deletemessage($from_id, $message_id);
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['addpanelname'], $backadmin, 'HTML');
    step('add_name_panel', $from_id);
} elseif ($user['step'] == "add_name_panel") {
    if (in_array($text, $marzban_list)) {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['Repeatpanel'], $backadmin, 'HTML');
        return;
    }
    savedata("save", "name", $text);
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['addpanelurl'], $backadmin, 'HTML');
    step('add_link_panel', $from_id);
} elseif ($user['step'] == "add_link_panel") {
    if (!filter_var($text, FILTER_VALIDATE_URL)) {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['Invalid-domain'], $backadmin, 'HTML');
        return;
    }
    savedata("save", "url_panel", $text);
    $userdata = json_decode($user['Processing_value'], true);
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['usernameset'], $backadmin, 'HTML');
    step('add_username_panel', $from_id);
} elseif ($user['step'] == "add_username_panel") {
    savedata("save", "username_panel", $text);
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['getpassword'], $backadmin, 'HTML');
    step('add_password_panel', $from_id);
} elseif ($user['step'] == "add_password_panel") {
    $userdata = json_decode($user['Processing_value'], true);
    savedata("save", "password_panel", $text);
    $server_info = sprintf($textbotlang['Admin']['managepanel']['infopanel'], $userdata['url_panel'], $userdata['username_panel'], $text);
    sendmessage($from_id, $server_info, null, 'HTML');
    $sent = sendmessage($from_id, "⌛️ درحال اتصال به سرور...");
    $sent_message_id = $sent['result']['message_id'];
    // $delay = rand(1, 3);
    // sleep($delay);
    // deletemessage($from_id, $sent_message_id);
    $isConnected = false;
    $check = null;
    if ($userdata['type'] == "marzban") {
        $check = token_panel_direct($userdata['url_panel'], $userdata['username_panel'], $text);
        if (isset($check['access_token'])) $isConnected = true;
    } elseif ($userdata['type'] == "marzneshin") {
        $check = token_panelm($userdata['url_panel'], $userdata['username_panel'], $text);
        if (isset($check['success']) && $check['success'] === true) $isConnected = true;
    } elseif ($userdata['type'] == "x-ui") {
        $check = login($userdata['url_panel'], false, $userdata['username_panel'], $text);
        if (isset($check['success']) && $check['success'] === true) $isConnected = true;
    } elseif ($userdata['type'] == "alireza") {
        $check = loginalireza($userdata['url_panel'], $userdata['username_panel'], $text);
        if (isset($check['success']) && $check['success'] === true) $isConnected = true;
    }
    if (!$isConnected) {
        deletemessage($from_id, $sent_message_id);
        sendmessage($from_id, "error: " . json_encode($check), null, 'HTML');
        sendmessage($from_id, "❌ اتصال به سرور برقرار نشد. لطفا آدرس یا نام کاربری یا پسورد را بررسی کنید.", $keyboardadmin, 'HTML');
        step('home', $from_id);
        return;
    }
    $defaults = [
        'inboundid'        => "0",
        'sublink'          => "onsublink",
        'config'           => "offconfig",
        'method_username'  => $textbotlang['users']['customidAndRandom'],
        'status_test'      => "offtestshowpanel",
        'status'           => "activepanel",
        'onholdstatus'     => "offonhold",
    ];
    $stmt = $pdo->prepare("INSERT INTO marzban_panel (name_panel, url_panel, username_panel, password_panel, type, inboundid, sublink, configManual, MethodUsername, statusTest, status, onholdstatus) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$userdata['name'], $userdata['url_panel'], $userdata['username_panel'], $text, $userdata['type'], $defaults['inboundid'], $defaults['sublink'], $defaults['config'], $defaults['method_username'], $defaults['status_test'], $defaults['status'], $defaults['onholdstatus']]);
    deletemessage($from_id, $sent_message_id);
    sendmessage($from_id, "✅ سرور شما با موفقیت به لیست سرور ها افزوده شد", $keyboardadmin, 'HTML');
    step('home', $from_id);
}

if ($text == $textbotlang['Admin']['keyboardadmin']['send_message'] || $datain == "send_message") {
    if (USER_INLINE_KEYBOARD) {
        Editmessagetext($from_id, $message_id, $textbotlang['users']['selectoption'], $admin_manage_systemsms);
    } else {
        sendmessage($from_id, $textbotlang['users']['selectoption'], $admin_manage_systemsms, 'HTML');
    }
} elseif ($text == $textbotlang['Admin']['systemsms']['sendbulkbtn'] || $datain == "sendbulk") {
    sendmessage($from_id, $textbotlang['Admin']['ManageUser']['GetText'], $backadmin, 'HTML');
    step('getconfirmsendall', $from_id);
} elseif ($user['step'] == "getconfirmsendall") {
    if (!$text) {
        sendmessage($from_id, $textbotlang['Admin']['systemsms']['allowsendtext'], $backadmin, 'HTML');
        return;
    }
    savedata("clear", "text", $text);
    savedata("save", "id_admin", $from_id);
    sendmessage($from_id, $textbotlang['Admin']['systemsms']['acceptsend'], $backadmin, 'HTML');
    step("gettextforsendall", $from_id);
} elseif ($user['step'] == "gettextforsendall") {
    $userdata = json_decode($user['Processing_value'], true);
    if ($text == $textbotlang['Admin']['accept']) {
        step('home', $from_id);
        $result = select("user", "id", "User_Status", "Active", "fetchAll");
        $Respuseronse = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => $textbotlang['Admin']['systemsms']['cancelsend'], 'callback_data' => 'cancel_sendmessage'],
                ],
            ]
        ]);
        file_put_contents('cron/users.json', json_encode($result));
        file_put_contents('cron/info', $user['Processing_value']);
        sendmessage($from_id, $textbotlang['Admin']['systemsms']['sendingmessage'], $Respuseronse, 'HTML');
    }
} elseif ($datain == "cancel_sendmessage") {
    unlink('cron/users.json');
    unlink('cron/info');
    deletemessage($from_id, $message_id);
    sendmessage($from_id, $textbotlang['Admin']['systemsms']['canceledmessage'], null, 'HTML');
} elseif ($text == $textbotlang['Admin']['systemsms']['forwardbulkbtn'] || $datain == "forwardbulk") {
    sendmessage($from_id, $textbotlang['Admin']['ManageUser']['ForwardGetext'], $backadmin, 'HTML');
    step('gettextforwardMessage', $from_id);
} elseif ($user['step'] == "gettextforwardMessage") {
    sendmessage($from_id, $textbotlang['Admin']['systemsms']['sendingforward'], $keyboardadmin, 'HTML');
    step('home', $from_id);
    $filename = 'user.txt';
    $stmt = $pdo->prepare("SELECT id FROM user");
    $stmt->execute();
    if ($result) {
        $ids = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $ids[] = $row['id'];
        }
        $idsText = implode("\n", $ids);
        file_put_contents($filename, $idsText);
    }
    $file = fopen($filename, 'r');
    if ($file) {
        while (($line = fgets($file)) !== false) {
            $line = trim($line);
            forwardMessage($from_id, $message_id, $line);
            usleep(2000000);
        }
        sendmessage($from_id, $textbotlang['Admin']['systemsms']['sendforwardtousers'], $keyboardadmin, 'HTML');
        fclose($file);
    }
    unlink($filename);
}

if ($text == $textbotlang['Admin']['keyboardadmin']['customkey'] || $datain == "customkey") {
    if (USER_INLINE_KEYBOARD) {
        Editmessagetext($from_id, $message_id, "لطفا یکی از بخش های زیر را انتخاب کنید:", $CustomKeys);
    } else {
        sendmessage($from_id, "لطفا یکی از بخش های زیر را انتخاب کنید:", $CustomKeys, 'HTML');
    }
} elseif ($text == $textbotlang['Admin']['keyboardadmin']['bot_text_settings'] || $datain == "bot_text_settings") {
    $inline_keyboard = [
        [
            ['text' => "ویرایش نام", 'callback_data' => 'not_set'], 
            ['text' => "⭐ نام", 'callback_data' => 'not_set']
        ],
        [
            ['text' => "✏️", 'callback_data' => 'text_support'],
            ['text' => $datatextbot['text_support'], 'callback_data' => 'not_set']
        ],
        [
            ['text' => "✏️", 'callback_data' => 'text_sell'],
            ['text' => $datatextbot['text_sell'], 'callback_data' => 'not_set']
        ],
        [
            ['text' => "✏️", 'callback_data' => 'text_Purchased_services'],
            ['text' => $datatextbot['text_Purchased_services'], 'callback_data' => 'not_set']
        ],
        [
            ['text' => "✏️", 'callback_data' => 'text_usertest'],
            ['text' => $datatextbot['text_usertest'], 'callback_data' => 'not_set']
        ],
        [
            ['text' => "✏️", 'callback_data' => 'text_help'],
            ['text' => $datatextbot['text_help'], 'callback_data' => 'not_set']
        ],
        [
            ['text' => "✏️", 'callback_data' => 'text_account'],
            ['text' => $datatextbot['text_account'], 'callback_data' => 'not_set']
        ],
        [
            ['text' => "✏️", 'callback_data' => 'text_Add_Balance'],
            ['text' => $datatextbot['text_Add_Balance'], 'callback_data' => 'not_set']
        ],
        [
            ['text' => "✏️", 'callback_data' => 'text_myid'],
            ['text' => $datatextbot['text_myid'], 'callback_data' => 'not_set']
        ],
        [
            ['text' => "✏️", 'callback_data' => 'text_affiliates'],
            ['text' => $datatextbot['text_affiliates'], 'callback_data' => 'not_set']
        ],
        [
            ['text' => "✏️", 'callback_data' => 'text_linkapp'],
            ['text' => $datatextbot['text_linkapp'], 'callback_data' => 'not_set']
        ]
    ];
    if ($setting['inline_keyboard'] === 'on') {
        $inline_keyboard[] = [
            ['text' => $textbotlang['Admin']['Back-Adminment'], 'callback_data' => 'back_admin']
        ];
    }
    $bot_text_settings = json_encode(['inline_keyboard' => $inline_keyboard], JSON_UNESCAPED_UNICODE);
    if (USER_INLINE_KEYBOARD) {
        Editmessagetext($from_id, $message_id, "👇 لطفا از طریق دکمه های زیر نام دکمه ها را مشخص کنید.", $bot_text_settings);
    } else {
        sendmessage($from_id, "👇 لطفا از طریق دکمه های زیر نام دکمه ها را مشخص کنید.", $bot_text_settings, 'HTML');
    }
} elseif ($text == $textbotlang['Admin']['keyboardadmin']['bot_text_matn'] || $datain == "bot_text_matn") {
    $inline_keyboard = [
        [
            ['text' => "ویرایش نام", 'callback_data' => 'not_set'],
            ['text' => "⭐ نام", 'callback_data' => 'not_set']
        ],
        [
            ['text' => "✏️", 'callback_data' => 'text_start'],
            ['text' => $textbotlang['Admin']['changetext']['textstart'], 'callback_data' => 'not_set']
        ],
        [
            ['text' => "✏️", 'callback_data' => 'text_support_qs'],
            ['text' => $textbotlang['Admin']['changetext']['text_support'], 'callback_data' => 'not_set']
        ],
        [
            ['text' => "✏️", 'callback_data' => 'text_channel'],
            ['text' => $textbotlang['Admin']['changetext']['text_channel'], 'callback_data' => 'not_set']
        ],
        [
            ['text' => "✏️", 'callback_data' => 'text_bot_off'],
            ['text' => $textbotlang['Admin']['changetext']['text_bot_off'], 'callback_data' => 'not_set']
        ],
        [
            ['text' => "✏️", 'callback_data' => 'text_info_test'],
            ['text' => $textbotlang['Admin']['changetext']['text_info_test'], 'callback_data' => 'not_set']
        ]
    ];
    if ($setting['inline_keyboard'] === 'on') {
        $inline_keyboard[] = [
            ['text' => $textbotlang['Admin']['Back-Adminment'], 'callback_data' => 'back_admin']
        ];
    }
    $bot_text_matn = json_encode(['inline_keyboard' => $inline_keyboard], JSON_UNESCAPED_UNICODE);
    if (USER_INLINE_KEYBOARD) {
        Editmessagetext($from_id, $message_id, "👇 جهت تغییر متن بخش موردنظر، روی آن کلیک کنید", $bot_text_matn);
    } else {
        sendmessage($from_id, "👇 جهت تغییر متن بخش موردنظر، روی آن کلیک کنید", $bot_text_matn, 'HTML');
    }
} 

elseif ($text == $textbotlang['Admin']['keyboardadmin']['bot_text_matnkey'] || $datain == "bot_text_matnkey") {
    $inline_keyboard = [
        [
            ['text' => "ویرایش نام", 'callback_data' => 'not_set'],
            ['text' => "⭐ نام", 'callback_data' => 'not_set']
        ],
        [
            ['text' => "✏️", 'callback_data' => 'text_inline_key'],
            ['text' => $datatextbot['text_inline_key'], 'callback_data' => 'not_set']
        ],
        [
            ['text' => "✏️", 'callback_data' => 'text_Discount'],
            ['text' => $datatextbot['text_Discount'], 'callback_data' => 'not_set']
        ],
        [
            ['text' => "✏️", 'callback_data' => 'text_BalanceBuy'],
            ['text' => $datatextbot['text_BalanceBuy'], 'callback_data' => 'not_set']
        ],
        [
            ['text' => "✏️", 'callback_data' => 'text_discountcode'],
            ['text' => $datatextbot['text_discountcode'], 'callback_data' => 'not_set']
        ],
        [
            ['text' => "✏️", 'callback_data' => 'text_freetest'],
            ['text' => $datatextbot['text_freetest'], 'callback_data' => 'not_set']
        ],
    ];
    if ($setting['inline_keyboard'] === 'on') {
        $inline_keyboard[] = [
            ['text' => $textbotlang['Admin']['Back-Adminment'], 'callback_data' => 'back_admin']
        ];
    }
    $bot_text_matnkey = json_encode(['inline_keyboard' => $inline_keyboard], JSON_UNESCAPED_UNICODE);
    if (USER_INLINE_KEYBOARD) {
        Editmessagetext($from_id, $message_id, "👇 جهت تغییر متن بخش موردنظر، روی آن کلیک کنید", $bot_text_matnkey);
    } else {
        sendmessage($from_id, "👇 جهت تغییر متن بخش موردنظر، روی آن کلیک کنید", $bot_text_matnkey, 'HTML');
    }
}

$map = [
    'text_support' => 'text_support',
    'text_sell' => 'text_sell',
    'text_Purchased_services' => 'text_Purchased_services',
    'text_usertest' => 'text_usertest',
    'text_help' => 'text_help',
    'text_account' => 'text_account',
    'text_myid' => 'text_myid',
    'text_affiliates' => 'text_affiliates',
    'text_linkapp' => 'text_linkapp',
    'text_Add_Balance' => 'text_Add_Balance',
    'text_start' => 'text_start',
    'text_support_qs' => 'text_support_qs',
    'text_bot_off' => 'text_bot_off',
    'text_info_test' => 'text_info_test',
    'text_channel' => 'text_channel',
    'text_inline_key' => 'text_inline_key',
    'text_Discount' => 'text_Discount',
    'text_BalanceBuy' => 'text_BalanceBuy',
    'text_discountcode' => 'text_discountcode',
    'text_freetest' => 'text_freetest',
];

if (isset($map[$datain])) {
    sendmessage($from_id, "لطفا متن مورد نظر خود را ارسال کنید:", $backadmin, 'HTML');
    step($datain, $from_id);
}

foreach ($map as $key => $id_text) {
    if ($user['step'] == $key) {
        if (!$text) {
            sendmessage($from_id, "متن نمی‌تواند خالی باشد!", $backadmin, 'HTML');
            return;
        }
        update("textbot", "text", $text, "id_text", $id_text);
        sendmessage($from_id, "با موفقیت تنظیم شد", $CustomKeys, 'HTML');
        step('home', $from_id);
        return;
    }
}

if ($text == $textbotlang['Admin']['keyboardadmin']['settings'] || $datain == "settings") {
    if (USER_INLINE_KEYBOARD) {
        Editmessagetext($from_id, $message_id, $textbotlang['users']['selectoption'], $setting_panel);
    } else {
        sendmessage($from_id, $textbotlang['users']['selectoption'], $setting_panel, 'HTML');;
    }
} elseif ($text == $textbotlang['Admin']['keyboardadmin']['shop_section'] || $datain == "shop_section") {
    if (USER_INLINE_KEYBOARD) {
        Editmessagetext($from_id, $message_id, $textbotlang['users']['selectoption'], $shopkeyboard);
    } else {
        sendmessage($from_id, $textbotlang['users']['selectoption'], $shopkeyboard, 'HTML');
    }
} elseif ($text == $textbotlang['Admin']['keyboardadmin']['manage_server'] || $datain == "manage_server") {
    if (USER_INLINE_KEYBOARD) {
        Editmessagetext($from_id, $message_id, $textbotlang['users']['selectoption'], $admin_manage_server);
    } else {
        sendmessage($from_id, $textbotlang['users']['selectoption'], $admin_manage_server, 'HTML');;
    }
} elseif ($text == $textbotlang['Admin']['keyboardadmin']['manage_user'] || $datain == "manage_user") {
    if (USER_INLINE_KEYBOARD) {
        Editmessagetext($from_id, $message_id, $textbotlang['users']['selectoption'], $admin_manage_user);
    } else {
        sendmessage($from_id, $textbotlang['users']['selectoption'], $admin_manage_user, 'HTML');;
    }
} elseif ($text == $textbotlang['Admin']['keyboardadmin']['all_section'] || $datain == "all_section") {
    if (USER_INLINE_KEYBOARD) {
        Editmessagetext($from_id, $message_id, $textbotlang['users']['selectoption'], $admin_manage_systemsms);
    } else {
        sendmessage($from_id, $textbotlang['users']['selectoption'], $admin_manage_systemsms, 'HTML');
    }
}  elseif ($text == $textbotlang['Admin']['keyboardadmin']['admin_section'] || $datain == "admin_section") {
    if (USER_INLINE_KEYBOARD) {
        Editmessagetext($from_id, $message_id, $textbotlang['users']['selectoption'], $admin_section_panel);
    } else {
        sendmessage($from_id, $textbotlang['users']['selectoption'], $admin_section_panel, 'HTML');;
    }
} elseif ($text == $textbotlang['Admin']['keyboardadmin']['discount_section'] || $datain == "discount_section") {
    if (USER_INLINE_KEYBOARD) {
        Editmessagetext($from_id, $message_id, $textbotlang['users']['selectoption'], $admin_section_discount);
    } else {
        sendmessage($from_id, $textbotlang['users']['selectoption'], $admin_section_discount, 'HTML');;
    }
} elseif ($text == $textbotlang['Admin']['keyboardadmin']['test_account_settings'] || $datain == "test_account_settings") {
    if (USER_INLINE_KEYBOARD) {
        Editmessagetext($from_id, $message_id, $textbotlang['users']['selectoption'], $keyboard_usertest);
    } else {
        sendmessage($from_id, $textbotlang['users']['selectoption'], $keyboard_usertest, 'HTML');
    }
} 

if ($text == $textbotlang['Admin']['keyboardadmin']['export_phone'] || $datain == "export_phone") {
    $sent = sendmessage($from_id, "⌛️");
    $sent_message_id = $sent['result']['message_id'];
    sleep(rand(1, 3));
    deletemessage($from_id, $sent_message_id);
    $stmt = $pdo->prepare("SELECT id, number FROM user WHERE number IS NOT NULL AND number != 'none'");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $jsonData = json_encode($users, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    $file = 'phone.json';
    file_put_contents($file, $jsonData);
    senddocument($from_id, $file);
    unlink($file);
}

if ($text == $textbotlang['Admin']['ManageUser']['discountnumber'] || $datain == "discountnumber") {
    if (USER_INLINE_KEYBOARD) {
        Editmessagetext($from_id, $message_id, $textbotlang['Admin']['ManageUser']['searchuser'], $backadmin);
    } else {
        sendmessage($from_id, $textbotlang['Admin']['ManageUser']['searchuser'], $backadmin, 'HTML');
    }
    step('set_discount_userid', $from_id);
} elseif ($user['step'] == "set_discount_userid") {
    $target_id = intval($text);
    $target_user = select("user", "*", "id", $target_id, "select");
    if (!$target_user) {
        sendmessage($from_id, $textbotlang['Admin']['ManageUser']['usernotfound'], $admin_section_discount, 'HTML');
        step('home', $from_id);
        return;
    }
    update("user", "Processing_value", $target_id, "id", $from_id);
    sendmessage($from_id, $textbotlang['Admin']['ManageUser']['enterdiscount'], $backadmin, 'HTML');
    step('set_discount_value', $from_id);
} elseif ($user['step'] == "set_discount_value") {
    $discount = intval($text);
    if ($discount < 0) {
        $discount = 0;
    }
    if ($discount > 100) {
        sendmessage($from_id, $textbotlang['Admin']['ManageUser']['maxdiscount'], $backadmin, 'HTML');
        return;
    }
    $target_id = $user['Processing_value'];
    update("user", "discount_number", $discount, "id", $target_id);
    sendmessage($from_id, $textbotlang['Admin']['ManageUser']['setdiscountok'], $admin_section_discount, 'HTML');
    step('home', $from_id);
} elseif ($text == $textbotlang['Admin']['ManageUser']['listdiscount'] || $datain == "listdiscount") {
    $stmt = $pdo->prepare("SELECT id, username, discount_number FROM user WHERE discount_number > 0 ORDER BY discount_number DESC");
    $stmt->execute();
    $users_with_discount = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($users_with_discount) === 0) {
        sendmessage($from_id, $textbotlang['Admin']['ManageUser']['userdistnotfound'], null, 'HTML');
        return;
    }
    $message = "📋 لیست کاربران نماینده:\nبرای تغییر تخفیف هر کاربر روی ⚙️ کلیک کنید.\n\n";
    $inline_keyboard = [];
    foreach ($users_with_discount as $user) {
        $message .= "ID: <code>" . $user['id'] . "</code> | Username: @" . $user['username'] . " | Discount: " . $user['discount_number'] . "%\n\n";
        $inline_keyboard[] = [
            ['text' => "⚡️ ویرایش کاربر", 'callback_data' => 'not_set'],
            ['text' => "🎖 تخفیف", 'callback_data' => 'not_set'],
            ['text' => "👤 شناسه کاربر", 'callback_data' => 'not_set']
        ];
        $inline_keyboard[] = [
            ['text' => "⚙️", 'callback_data' => "set_discount:" . $user['id']],
            ['text' => $user['discount_number'] . "%", 'callback_data' => 'user_set'],
            ['text' => $user['id'], 'callback_data' => 'user_set']
        ];
    }
    if (USER_INLINE_KEYBOARD) {
        $inline_keyboard[] = [
            ['text' => $textbotlang['Admin']['Back-Adminment'], 'callback_data' => 'back_admin']
        ];
    }
    $keyboard_json = json_encode(['inline_keyboard' => $inline_keyboard], JSON_UNESCAPED_UNICODE);
    if (USER_INLINE_KEYBOARD) {
        Editmessagetext($from_id, $message_id, $message, $keyboard_json);
    } else {
        sendmessage($from_id, $message, $keyboard_json, 'HTML');
    }
} elseif (isset($update['callback_query'])) {
    $callback = $update['callback_query'];
    $from_id = $callback['from']['id'];
    $data = $callback['data'];
    if (strpos($data, "set_discount:") === 0) {
        $target_id = intval(explode(":", $data)[1]);
        update("user", "Processing_value", $target_id, "id", $from_id);
        sendmessage($from_id, $textbotlang['Admin']['ManageUser']['enterdiscount'], $backadmin, 'HTML');
        step('set_discount_value', $from_id);
    }
}

if ($text == $textbotlang['Admin']['systemsms']['sendmessageauser'] || $datain == "sendmessageauser") {
    if (USER_INLINE_KEYBOARD) {
        Editmessagetext($from_id, $message_id, $textbotlang['Admin']['ManageUser']['GetText'], $backadmin);
    } else {
        sendmessage($from_id, $textbotlang['Admin']['ManageUser']['GetText'], $backadmin, 'HTML');
    }
    step('sendmessagetext', $from_id);
} elseif ($user['step'] == "sendmessagetext") {
    update("user", "Processing_value", $text, "id", $from_id);
    sendmessage($from_id, $textbotlang['Admin']['ManageUser']['GetIDMessage'], $backadmin, 'HTML');
    step('sendmessagetid', $from_id);
} elseif ($user['step'] == "sendmessagetid") {
    if (!in_array($text, $users_ids)) {
        sendmessage($from_id, $textbotlang['Admin']['not-user'], $backadmin, 'HTML');
        return;
    }
    $textsendadmin = sprintf($textbotlang['Admin']['systemsms']['sendedmessagetouser'], $user['Processing_value']);
    sendmessage($text, $textsendadmin, null, 'HTML');
    sendmessage($from_id, $textbotlang['Admin']['ManageUser']['MessageSent'], $keyboardadmin, 'HTML');
    step('home', $from_id);
}

if ($text == $textbotlang['Admin']['keyboardadmin']['tutorial_section'] || $datain == "tutorial_section") {
    if (USER_INLINE_KEYBOARD) {
        Editmessagetext($from_id, $message_id, $textbotlang['users']['selectoption'], $keyboardhelpadmin);
    } else {
        sendmessage($from_id, $textbotlang['users']['selectoption'], $keyboardhelpadmin, 'HTML');
    }
} elseif ($text == $textbotlang['Admin']['Help']['addhelp'] || $datain == "addhelp") {
    sendmessage($from_id, $textbotlang['Admin']['Help']['GetAddNameHelp'], $backadmin, 'HTML');
    step('add_name_help', $from_id);
} elseif ($user['step'] == "add_name_help") {
    $stmt = $pdo->prepare("INSERT IGNORE INTO help (name_os) VALUES (?)");
    $stmt->bindParam(1, $text, PDO::PARAM_STR);
    $stmt->execute();
    sendmessage($from_id, $textbotlang['Admin']['Help']['GetAddDecHelp'], $backadmin, 'HTML');
    step('add_dec', $from_id);
    update("user", "Processing_value", $text, "id", $from_id);
} elseif ($user['step'] == "add_dec") {
    if ($photo) {
        update("help", "Media_os", $photoid, "name_os", $user['Processing_value']);
        update("help", "Description_os", $caption, "name_os", $user['Processing_value']);
        update("help", "type_Media_os", "photo", "name_os", $user['Processing_value']);
    } elseif ($text) {
        update("help", "Description_os", $text, "name_os", $user['Processing_value']);
    } elseif ($video) {
        update("help", "Media_os", $videoid, "name_os", $user['Processing_value']);
        update("help", "Description_os", $caption, "name_os", $user['Processing_value']);
        update("help", "type_Media_os", "video", "name_os", $user['Processing_value']);
    }
    sendmessage($from_id, $textbotlang['Admin']['Help']['SaveHelp'], $keyboardadmin, 'HTML');
    step('home', $from_id);
} elseif ($text == $textbotlang['Admin']['Help']['removehelpbtn'] || $datain == "removehelpbtn") {
    sendmessage($from_id, $textbotlang['Admin']['Help']['SelectName'], $json_list_help, 'HTML');
    step('remove_help', $from_id);
} elseif ($user['step'] == "remove_help") {
    $stmt = $pdo->prepare("DELETE FROM help WHERE name_os = ?");
    $stmt->execute([$text]);
    sendmessage($from_id, $textbotlang['Admin']['Help']['RemoveHelp'], $keyboardhelpadmin, 'HTML');
    step('home', $from_id);
}

if (preg_match('/Response_(\w+)/', $datain, $dataget)) {
    $iduser = $dataget[1];
    update("user", "Processing_value", $iduser, "id", $from_id);
    step('getmessageAsAdmin', $from_id);
    sendmessage($from_id, $textbotlang['Admin']['ManageUser']['GetTextResponse'], $backadmin, 'HTML');
} elseif ($user['step'] == "getmessageAsAdmin") {
    sendmessage($from_id, $textbotlang['Admin']['ManageUser']['SendMessageuser'], null, 'HTML');
    if ($text) {
        $textSendAdminToUser = sprintf($textbotlang['Admin']['systemsms']['sendedmessagetouser'], $text);
        sendmessage($user['Processing_value'], $textSendAdminToUser, null, 'HTML');
    }
    if ($photo) {
        $textSendAdminToUser = sprintf($textbotlang['Admin']['systemsms']['sendedmessagetouser'], $caption);
        telegram('sendphoto', [
            'chat_id' => $user['Processing_value'],
            'photo' => $photoid,
            'reply_markup' => $Response,
            'caption' => $textSendAdminToUser,
            'parse_mode' => "HTML",
        ]);
    }
    step('home', $from_id);
}

if (strpos($datain, "toggle_status:") === 0) {
    $panel_id = str_replace("toggle_status:", "", $datain);
    if (togglePanelStatus($pdo, $panel_id, 'status')) {
        $json_list_panel = buildListPanelKeyboard($pdo, $textbotlang);
        Editmessagetext($from_id, $message_id, "👇 لیست سرور ها به شرح زیر می باشد", $json_list_panel, 'HTML');
    } else {
        sendmessage($from_id, "❌ پنل یافت نشد.", null, 'HTML');
    }
}

if (strpos($datain, "toggle_test:") === 0) {
    $panel_id = str_replace("toggle_test:", "", $datain);
    if (togglePanelStatus($pdo, $panel_id, 'test')) {
        $json_list_panel = buildListPanelKeyboard($pdo, $textbotlang);
        Editmessagetext($from_id, $message_id, "👇 لیست سرور ها به شرح زیر می باشد", $json_list_panel, 'HTML');
    } else {
        sendmessage($from_id, "❌ پنل یافت نشد.", null, 'HTML');
    }
}

elseif (preg_match('/banuserlist_(\w+)/', $datain, $dataget)) {
    $iduser = $dataget[1];
    $userblock = select("user", "*", "id", $iduser, "select");
    if ($userblock['User_Status'] == "block") {
        sendmessage($from_id, $textbotlang['Admin']['ManageUser']['BlockedUser'], $backadmin, 'HTML');
        return;
    }
    update("user", "Processing_value", $iduser, "id", $from_id);
    update("user", "User_Status", "block", "id", $iduser);
    sendmessage($from_id, $textbotlang['Admin']['ManageUser']['BlockUser'], $backadmin, 'HTML');
    step('adddecriptionblock', $from_id);
} elseif ($user['step'] == "adddecriptionblock") {
    update("user", "description_blocking", $text, "id", $user['Processing_value']);
    sendmessage($from_id, $textbotlang['Admin']['ManageUser']['DescriptionBlock'], $keyboardadmin, 'HTML');
    step('home', $from_id);
} elseif (preg_match('/unbanuserr_(\w+)/', $datain, $dataget)) {
    $iduser = $dataget[1];
    $userunblock = select("user", "*", "id", $iduser, "select");
    if ($userunblock['User_Status'] == "Active") {
        sendmessage($from_id, $textbotlang['Admin']['ManageUser']['UserNotBlock'], $backadmin, 'HTML');
        return;
    }
    update("user", "User_Status", "Active", "id", $iduser);
    update("user", "description_blocking", "", "id", $iduser);
    sendmessage($from_id, $textbotlang['Admin']['ManageUser']['UserUnblocked'], $keyboardadmin, 'HTML');
    step('home', $from_id);
} elseif (preg_match('/confirmnumber_(\w+)/', $datain, $dataget)) {
    $iduser = $dataget[1];
    update("user", "number", "confrim number by admin", "id", $iduser);
    step('home', $iduser);
    sendmessage($from_id, $textbotlang['Admin']['phone']['active'], $admin_manage_user, 'HTML');
}
if ($text == $textbotlang['Admin']['channel']['channelreport'] || $datain == "channelreport") {
    if (USER_INLINE_KEYBOARD) {
        Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Channel']['ReportChannel'] . $setting['Channel_Report'], $backadmin);
    } else {
        sendmessage($from_id, $textbotlang['Admin']['Channel']['ReportChannel'] . $setting['Channel_Report'], $backadmin, 'HTML');
    }
    step('addchannelid', $from_id);
} elseif ($user['step'] == "addchannelid") {
    sendmessage($from_id, $textbotlang['Admin']['Channel']['SetChannelReport'], $keyboardadmin, 'HTML');
    update("setting", "Channel_Report", $text);
    step('home', $from_id);
    sendmessage($setting['Channel_Report'], $textbotlang['Admin']['Channel']['TestChannel'], null, 'HTML');
}

if ($text == $textbotlang['Admin']['Product']['addproduct'] || $datain == "add_product") {
    deletemessage($from_id, $message_id);
    $locationproduct = select("marzban_panel", "*", null, null, "count");
    if ($locationproduct == 0) {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['nullpaneladmin'], null, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['Product']['AddProductStepOne'], $backadmin, 'HTML');
    step('get_limit', $from_id);
} elseif ($user['step'] == "get_limit") {
    $randomString = bin2hex(random_bytes(2));
    $stmt = $pdo->prepare("INSERT IGNORE INTO product (name_product, code_product) VALUES (?, ?)");
    $stmt->bindParam(1, $text);
    $stmt->bindParam(2, $randomString);
    $stmt->execute();
    update("user", "Processing_value", $randomString, "id", $from_id);
    sendmessage($from_id, $textbotlang['Admin']['Product']['Service_location'], $json_list_pelan, 'HTML');
    step('get_location', $from_id);
} elseif ($user['step'] == "get_location") {
    deletemessage($from_id, $message_id);
    $panel_name = isset($datain) && !empty($datain) ? $datain : $text;
    update("product", "Location", $panel_name, "code_product", $user['Processing_value']);
    sendmessage($from_id, $textbotlang['Admin']['Product']['Getcategory'], KeyboardCategory(), 'HTML');
    step('get_category', $from_id);
} elseif ($user['step'] == "get_category") {
    deletemessage($from_id, $message_id);
    if (!empty($datain) && str_starts_with($datain, 'category_')) {
        $category_id = str_replace('category_', '', $datain);
        $category = select("category", "*", "id", $category_id, "select");
        if ($category) {
            update("product", "category", $category['id'], "code_product", $user['Processing_value']);
            sendmessage($from_id, $textbotlang['Admin']['Product']['GetLimit'], $backadmin, 'HTML');
            step('get_time', $from_id);
        } else {
            sendmessage($from_id, $textbotlang['Admin']['Product']['invalidcategory'], $backadmin, 'HTML');
        }
    }
} elseif ($user['step'] == "get_time") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['Product']['Invalidvolume'], $backadmin, 'HTML');
        return;
    }
    update("product", "Volume_constraint", $text, "code_product", $user['Processing_value']);
    sendmessage($from_id, $textbotlang['Admin']['Product']['GettIime'], $backadmin, 'HTML');
    step('get_price', $from_id);
} elseif ($user['step'] == "get_price") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['Product']['InvalidTime'], $backadmin, 'HTML');
        return;
    }
    update("product", "Service_time", $text, "code_product", $user['Processing_value']);
    sendmessage($from_id, $textbotlang['Admin']['Product']['GetPrice'], $backadmin, 'HTML');
    step('endstep', $from_id);
} elseif ($user['step'] == "endstep") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['Product']['InvalidPrice'], $backadmin, 'HTML');
        return;
    }
    update("product", "price_product", $text, "code_product", $user['Processing_value']);
    sendmessage($from_id, $textbotlang['Admin']['Product']['SaveProduct'], $shopkeyboard, 'HTML');
    step('home', $from_id);
}

if (preg_match('/Confirm_pay_(\w+)/', $datain, $dataget)) {
    $order_id = $dataget[1];
    $Payment_report = select("Payment_report", "*", "id_order", $order_id, "select");
    $Balance_id = select("user", "*", "id", $Payment_report['id_user'], "select");
    if ($Payment_report['payment_Status'] == "paid" || $Payment_report['payment_Status'] == "reject") {
        telegram(
            'answerCallbackQuery',
            array(
                'callback_query_id' => $callback_query_id,
                'text' => $textbotlang['Admin']['Payment']['reviewedpayment'],
                'show_alert' => true,
                'cache_time' => 5,
            )
        );
        return;
    }
    DirectPayment($order_id);
    $keyboard_accept = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['moeny']['paymentaccepted'], 'callback_data' => "none"],
            ],
        ]
    ]);
    telegram('editMessageCaption',[
        'chat_id' => $from_id,
        'message_id' => $message_id,
        'caption' => $caption,
        'reply_markup' => $keyboard_accept
    ]);
    update("user", "Processing_value", "0", "id", $Balance_id['id']);
    update("user", "Processing_value_one", "0", "id", $Balance_id['id']);
    update("user", "Processing_value_tow", "0", "id", $Balance_id['id']);
    update("Payment_report", "payment_Status", "paid", "id_order", $order_id);
    if (isset($setting['Channel_Report']) && strlen($setting['Channel_Report']) > 0) {
        sendmessage($setting['Channel_Report'], sprintf($textbotlang['Admin']['Report']['acceptcartresid'], $from_id, $Payment_report['price']), null, 'HTML');
    }
}

if (preg_match('/reject_pay_(\w+)/', $datain, $datagetr)) {
    $id_order = $datagetr[1];
    $Payment_report = select("Payment_report", "*", "id_order", $id_order, "select");
    update("user", "Processing_value", $Payment_report['id_user'], "id", $from_id);
    update("user", "Processing_value_one", $id_order, "id", $from_id);
    if ($Payment_report['payment_Status'] == "reject" || $Payment_report['payment_Status'] == "paid") {
        telegram(
            'answerCallbackQuery',
            array(
                'callback_query_id' => $callback_query_id,
                'text' => $textbotlang['Admin']['Payment']['reviewedpayment'],
                'show_alert' => true,
                'cache_time' => 5,
            )
        );
        return;
    }
    update("Payment_report", "payment_Status", "reject", "id_order", $id_order);
    sendmessage($from_id, $textbotlang['Admin']['Payment']['Reasonrejecting'], $backadmin, 'HTML');
    step('reject-dec', $from_id);
    Editmessagetext($from_id, $message_id, $text_callback, null);
} elseif ($user['step'] == "reject-dec") {
    update("Payment_report", "dec_not_confirmed", $text, "id_order", $user['Processing_value_one']);
    sendmessage($from_id, $textbotlang['Admin']['Payment']['Rejected'], $keyboardadmin, 'HTML');
    sendmessage($user['Processing_value'], sprintf($textbotlang['users']['moeny']['rejectresid'], $text, $user['Processing_value_one']), null, 'HTML');
    step('home', $from_id);
}

if ($text == $textbotlang['Admin']['Product']['titlebtnremove'] || $datain == "remove_product") {
    deletemessage($from_id, $message_id);
    sendmessage($from_id, $textbotlang['Admin']['Product']['Rmove_location'], $json_list_pelan, 'HTML');
    step('selectloc', $from_id);
} elseif ($user['step'] == "selectloc") {
    deletemessage($from_id, $message_id);
    $panel_name = isset($datain) && !empty($datain) ? $datain : $text;
    update("user", "Processing_value", $panel_name, "id", $from_id);
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'product'");
    $stmt->execute();
    $result = $stmt->fetchAll();
    $table_exists = count($result) > 0;
    if ($table_exists) {
        $stmt = $pdo->prepare("SELECT * FROM product WHERE Location = :Location OR Location = '/all'");
        $stmt->bindParam(':Location', $panel_name, PDO::PARAM_STR);
        $stmt->execute();
        $product = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $product[] = $row['name_product'];
        }
        $inline_keyboard = [];
        foreach ($product as $p) {
            $inline_keyboard[] = [
                ['text' => $p, 'callback_data' => $p]
            ];
        }
        $inline_keyboard[] = [
            ['text' => $textbotlang['Admin']['Back-Adminment'], 'callback_data' => 'back_admin']
        ];
        $list_products = json_encode(['inline_keyboard' => $inline_keyboard], JSON_UNESCAPED_UNICODE);
    }
    sendmessage($from_id, $textbotlang['Admin']['Product']['selectRemoveProduct'], $list_products, 'HTML');
    step('remove-product', $from_id);
} elseif ($user['step'] == "remove-product") {
    deletemessage($from_id, $message_id);
    $selected_product = isset($datain) && !empty($datain) ? $datain : $text;
    if (!in_array($selected_product, $name_product)) {
        sendmessage($from_id, $textbotlang['users']['sell']['error-product'], $backadmin, 'HTML');
        return;
    }
    $ydf = '/all';
    $stmt = $pdo->prepare("DELETE FROM product WHERE name_product = ? AND (Location = ? OR Location = ?)");
    $stmt->execute([$selected_product, $user['Processing_value'], $ydf]);
    sendmessage($from_id, $textbotlang['Admin']['Product']['RemoveedProduct'], $shopkeyboard, 'HTML');
    step('home', $from_id);
}

if ($text == $textbotlang['Admin']['Product']['titlebtnedit'] || $datain == "edit_product") {
    if (USER_INLINE_KEYBOARD) {
        Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Product']['Rmove_location'], $json_list_pelan);
    } else {
        sendmessage($from_id, $textbotlang['Admin']['Product']['Rmove_location'], $json_list_pelan, 'HTML');
    }
    step('selectlocedite', $from_id);
} elseif ($user['step'] == "selectlocedite") {
    deletemessage($from_id, $message_id);
    $panel_name = isset($datain) && !empty($datain) ? $datain : $text;
    update("user", "Processing_value_one", $panel_name, "id", $from_id);
    $stmt = $pdo->prepare("SELECT * FROM product WHERE Location = :Location OR Location = '/all'");
    $stmt->bindParam(':Location', $panel_name, PDO::PARAM_STR);
    $stmt->execute();
    $inline_keyboard = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $inline_keyboard[] = [
            ['text' => $row['name_product'], 'callback_data' => $row['name_product']]
        ];
    }
    $inline_keyboard[] = [
        ['text' => $textbotlang['Admin']['Back-Adminment'], 'callback_data' => 'back_admin']
    ];
    $json_list_product = json_encode(['inline_keyboard' => $inline_keyboard], JSON_UNESCAPED_UNICODE);
    sendmessage($from_id, $textbotlang['Admin']['Product']['selectEditProduct'], $json_list_product, 'HTML');
    step('change_filde', $from_id);
} elseif ($user['step'] == "change_filde") {
    deletemessage($from_id, $message_id);
    $panel_name = $user['Processing_value_one'];
    $stmt = $pdo->prepare("SELECT * FROM product WHERE Location = :Location OR Location = '/all'");
    $stmt->bindParam(':Location', $panel_name, PDO::PARAM_STR);
    $stmt->execute();
    $product = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $product[] = $row['name_product'];
    }
    $selected_product = isset($datain) && !empty($datain) ? $datain : $text;
    if (!in_array($selected_product, $product)) {
        sendmessage($from_id, $textbotlang['users']['sell']['error-product'], null, 'HTML');
        return;
    }
    $stmt = $pdo->prepare("SELECT * FROM product WHERE name_product = :name LIMIT 1");
    $stmt->bindParam(':name', $selected_product, PDO::PARAM_STR);
    $stmt->execute();
    $info = $stmt->fetch(PDO::FETCH_ASSOC);
    update("user", "Processing_value", $selected_product, "id", $from_id);
    if ($info) {
        $text_send = sprintf($textbotlang['Admin']['Product']['selectfieldProduct'], $info['name_product'], $info['price_product'], $info['Volume_constraint'], $info['Service_time'], $info['Location']);
    } else {
        $text_send = "❌ اطلاعات محصول یافت نشد.";
    }
    sendmessage($from_id, $text_send, $change_product, 'HTML');
    step('home', $from_id);
}

if ($text == $textbotlang['Admin']['Product']['editprice'] || $datain == "editprice") {
    deletemessage($from_id, $message_id);
    sendmessage($from_id, $textbotlang['Admin']['Product']['sendnewprice'], $backadmin, 'HTML');
    step('change_price', $from_id);
} elseif ($user['step'] == "change_price") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['Product']['InvalidPrice'], $backadmin, 'HTML');
        return;
    }
    $location = '/all';
    $stmtFirst = $pdo->prepare("UPDATE product SET price_product = ? WHERE name_product = ? AND (Location = ? OR Location = ?)");
    $stmtFirst->execute([$text, $user['Processing_value'], $user['Processing_value_one'], $location]);
    $stmtSecond = $pdo->prepare("UPDATE invoice SET price_product = ? WHERE name_product = ? AND Service_location = ?");
    $stmtSecond->execute([$text, $user['Processing_value'], $user['Processing_value_one']]);
    sendmessage($from_id, $textbotlang['Admin']['Product']['updatedprice'], $shopkeyboard, 'HTML');
    step('home', $from_id);
}

if ($text == $textbotlang['Admin']['Product']['editcategory'] || $datain == "editcategory") {
    deletemessage($from_id, $message_id);
    sendmessage($from_id, $textbotlang['Admin']['Product']['sendnewcategory'], KeyboardCategory(), 'HTML');
    step('change_category', $from_id);
} elseif ($user['step'] == "change_category") {
    deletemessage($from_id, $message_id);
    if ($datain) {
        if (str_starts_with($datain, 'category_')) {
            $category_id = str_replace('category_', '', $datain);
            $category = select("category", "*", "id", $category_id, "select");
            if ($category) {
                $stmtFirst = $pdo->prepare("UPDATE product SET category = ? WHERE name_product = ? AND (Location = ? OR Location = ?)");
                $stmtFirst->execute([$category['id'], $user['Processing_value'], $user['Processing_value_one'], "/all"]);
                sendmessage($from_id, $textbotlang['Admin']['Product']['updatedcategory'], $shopkeyboard, 'HTML');
                step('home', $from_id);
            } else {
                sendmessage($from_id, $textbotlang['Admin']['Product']['invalidcategory'], $backadmin, 'HTML');
            }
        }
    }
}

if ($text == $textbotlang['Admin']['Product']['editname'] || $datain == "editname") {
    deletemessage($from_id, $message_id);
    sendmessage($from_id, $textbotlang['Admin']['Product']['sendnewname'], $backadmin, 'HTML');
    step('change_name', $from_id);
} elseif ($user['step'] == "change_name") {
    $value = "/all";
    $stmtFirst = $pdo->prepare("UPDATE product SET name_product = ? WHERE name_product = ? AND (Location = ? OR Location = ?)");
    $stmtFirst->execute([$text, $user['Processing_value'], $user['Processing_value_one'], $value]);
    $sqlSecond = "UPDATE invoice SET name_product = ? WHERE name_product = ? AND Service_location = ?";
    $stmtSecond = $pdo->prepare($sqlSecond);
    $stmtSecond->execute([$text, $user['Processing_value'], $user['Processing_value_one']]);
    sendmessage($from_id, $textbotlang['Admin']['Product']['updatedname'], $shopkeyboard, 'HTML');
    step('home', $from_id);
}

if ($text == $textbotlang['Admin']['Product']['editvolume'] || $datain == "editvolume") {
    deletemessage($from_id, $message_id);
    sendmessage($from_id, $textbotlang['Admin']['Product']['sendnewvolume'], $backadmin, 'HTML');
    step('change_val', $from_id);
} elseif ($user['step'] == "change_val") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['Product']['Invalidvolume'], $backadmin, 'HTML');
        return;
    }
    $sqlInvoice = "UPDATE invoice SET Volume = ? WHERE name_product = ? AND Service_location = ?";
    $stmtInvoice = $pdo->prepare($sqlInvoice);
    $stmtInvoice->execute([$text, $user['Processing_value'], $user['Processing_value_one']]);
    $sqlProduct = "UPDATE product SET Volume_constraint = ? WHERE name_product = ? AND Location = ?";
    $stmtProduct = $pdo->prepare($sqlProduct);
    $stmtProduct->execute([$text, $user['Processing_value'], $user['Processing_value_one']]);
    sendmessage($from_id, $textbotlang['Admin']['Product']['updatedvolume'], $shopkeyboard, 'HTML');
    step('home', $from_id);
}

if ($text == $textbotlang['Admin']['Product']['edittime'] || $datain == "edittime") {
    deletemessage($from_id, $message_id);
    sendmessage($from_id, $textbotlang['Admin']['Product']['NewTime'], $backadmin, 'HTML');
    step('change_time', $from_id);
} elseif ($user['step'] == "change_time") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['Product']['InvalidTime'], $backadmin, 'HTML');
        return;
    }
    $stmtInvoice = $pdo->prepare("UPDATE invoice SET Service_time = ? WHERE name_product = ? AND Service_location = ?");
    $stmtInvoice->bindParam(1, $text);
    $stmtInvoice->bindParam(2, $user['Processing_value']);
    $stmtInvoice->bindParam(3, $user['Processing_value_one']);
    $stmtInvoice->execute();
    $stmtProduct = $pdo->prepare("UPDATE product SET Service_time = ? WHERE name_product = ? AND Location = ?");
    $stmtProduct->bindParam(1, $text);
    $stmtProduct->bindParam(2, $user['Processing_value']);
    $stmtProduct->bindParam(3, $user['Processing_value_one']);
    $stmtProduct->execute();
    sendmessage($from_id, $textbotlang['Admin']['Product']['TimeUpdated'], $shopkeyboard, 'HTML');
    step('home', $from_id);
}

if ($text == $textbotlang['Admin']['Usertest']['settimeusertest'] || $datain == "settimeusertest") {
    sendmessage($from_id, sprintf($textbotlang['Admin']['Usertest']['sendtimeusertest'], $setting['time_usertest']), $backadmin, 'HTML');
    step('updatetime', $from_id);
} elseif ($user['step'] == "updatetime") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['Product']['InvalidTime'], $backadmin, 'HTML');
        return;
    }
    update("setting", "time_usertest", $text);
    sendmessage($from_id, $textbotlang['Admin']['Usertest']['TimeUpdated'], $keyboard_usertest, 'HTML');
    step('home', $from_id);
}

if ($text == $textbotlang['Admin']['Usertest']['setvolumeusertest'] || $datain == "setvolumeusertest") {
    sendmessage($from_id, sprintf($textbotlang['Admin']['Usertest']['sendvoluemusertest'], $setting['val_usertest']), $backadmin, 'HTML');
    step('val_usertest', $from_id);
} elseif ($user['step'] == "val_usertest") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['Product']['Invalidvolume'], $backadmin, 'HTML');
        return;
    }
    update("setting", "val_usertest", $text);
    sendmessage($from_id, $textbotlang['Admin']['Usertest']['VolumeUpdated'], $keyboard_usertest, 'HTML');
    step('home', $from_id);
} elseif (preg_match('/addbalanceuser_(\w+)/', $datain, $dataget)) {
    $iduser = $dataget[1];
    update("user","Processing_value",$iduser, "id",$from_id);
    sendmessage($from_id, $textbotlang['Admin']['Balance']['PriceBalance'], $backadmin, 'HTML');
    step('get_price_add', $from_id);
} elseif ($user['step'] == "get_price_add") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['Balance']['Invalidprice'], $backadmin, 'HTML');
        return;
    }
    if (intval($text) > 10000000){
        sendmessage($from_id, $textbotlang['Admin']['Balance']['maxpricebalance'], $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['Balance']['AddBalanceUser'], $backadmin, 'HTML');
    $Balance_user = select("user", "*", "id", $user['Processing_value'], "select");
    $Balance_add_user = $Balance_user['Balance'] + $text;
    update("user", "Balance", $Balance_add_user, "id", $user['Processing_value']);
    $text = number_format($text);
    sendmessage($user['Processing_value'], sprintf($textbotlang['Admin']['Balance']['AddedBalance'] ,$text), null, 'HTML');
    step('home', $from_id);
    if (isset($setting['Channel_Report']) &&strlen($setting['Channel_Report']) > 0) {
        sendmessage($setting['Channel_Report'], sprintf($textbotlang['Admin']['Report']['addedbalance'],$from_id, $user['Processing_value'],$text), null, 'HTML');
    }
} elseif (preg_match('/lowbalanceuser_(\w+)/', $datain, $dataget)) {
    $iduser = $dataget[1];
    update("user","Processing_value",$iduser, "id",$from_id);
    sendmessage($from_id, $textbotlang['Admin']['Balance']['PriceBalancek'], $backadmin, 'HTML');
    step('get_price_Negative', $from_id);
} elseif ($user['step'] == "get_price_Negative") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['Balance']['Invalidprice'], $backadmin, 'HTML');
        return;
    }
    if (intval($text) > 100000000){
        sendmessage($from_id, $textbotlang['Admin']['Balance']['maxpricebalance'], $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['Balance']['NegativeBalanceUser'], $backadmin, 'HTML');
    $Balance_user = select("user", "*", "id", $user['Processing_value'], "select");
    $Balance_Low_user = $Balance_user['Balance'] - $text;
    update("user", "Balance", $Balance_Low_user, "id", $user['Processing_value']);
    $text = number_format($text);
    sendmessage($user['Processing_value'], sprintf($textbotlang['Admin']['Balance']['ReduceBalance'], $text), null, 'HTML');
    step('home', $from_id);
    if (isset($setting['Channel_Report']) &&strlen($setting['Channel_Report']) > 0) {
        sendmessage($setting['Channel_Report'], sprintf($textbotlang['Admin']['Report']['removebalance'],$from_id,$user['Processing_value'],$text), null, 'HTML');
    }
}

if ($text == $textbotlang['Admin']['Discount']['titlebtn']) {
    sendmessage($from_id, $textbotlang['Admin']['Discount']['GetCode'], $backadmin, 'HTML');
    step('get_code', $from_id);
} elseif ($user['step'] == "get_code") {
    if (!preg_match('/^[A-Za-z]+$/', $text)) {
        sendmessage($from_id, $textbotlang['Admin']['Discount']['ErrorCode'], null, 'HTML');
        return;
    }
    $stmt = $pdo->prepare("INSERT INTO Discount (code) VALUES (?)");
    $stmt->bindParam(1, $text);
    $stmt->execute();
    sendmessage($from_id, $textbotlang['Admin']['Discount']['PriceCode'], null, 'HTML');
    step('get_price_code', $from_id);
    update("user", "Processing_value", $text, "id", $from_id);
} elseif ($user['step'] == "get_price_code") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['Balance']['Invalidprice'], $backadmin, 'HTML');
        return;
    }
    update("Discount", "price", $text, "code", $user['Processing_value']);
    sendmessage($from_id, $textbotlang['Admin']['Discount']['SaveCode'], $keyboardadmin, 'HTML');
    step('home', $from_id);
}

if ($datain == "sublinkstatus") {
    $panel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    if ($panel['sublink'] == null) {
        update("marzban_panel", "sublink", "onsublink", "name_panel", $user['Processing_value']);
    }
    $panel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    $sublinkkeyboard = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $panel['sublink'], 'callback_data' => $panel['sublink']],
            ],
        ]
    ]);
    if ($panel['configManual'] == "onconfig") {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['checkoffconfig'], null, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['Status']['subTitle'], $sublinkkeyboard, 'HTML');
}

if ($datain == "onsublink") {
    update("marzban_panel", "sublink", "offsublink", "name_panel", $user['Processing_value']);
    $panel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    $sublinkkeyboard = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $panel['sublink'], 'callback_data' => $panel['sublink']],
            ],
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Status']['subStatusOff'], $sublinkkeyboard);
} elseif ($datain == "offsublink") {
    update("marzban_panel", "sublink", "onsublink", "name_panel", $user['Processing_value']);
    $panel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    $sublinkkeyboard = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $panel['sublink'], 'callback_data' => $panel['sublink']],
            ],
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Status']['subStatuson'], $sublinkkeyboard);
}

if ($datain == "configstatus") {
    $panel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    if ($panel['configManual'] == null) {
        update("marzban_panel", "configManual", "offconfig", "name_panel", $user['Processing_value']);
    }
    $panel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    $configkeyboard = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $panel['configManual'], 'callback_data' => $panel['configManual']],
            ],
        ]
    ]);
    if ($panel['sublink'] == "onsublink") {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['notoffsublink'], null, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['Status']['configTitle'], $configkeyboard, 'HTML');
}

if ($datain == "onconfig") {
    update("marzban_panel", "configManual", "offconfig", "name_panel", $user['Processing_value']);
    $panel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    $configkeyboard = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $panel['configManual'], 'callback_data' => $panel['configManual']],
            ],
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Status']['configStatusOff'], $configkeyboard);
} elseif ($datain == "offconfig") {
    update("marzban_panel", "configManual", "onconfig", "name_panel", $user['Processing_value']);
    $panel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    $configkeyboard = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $panel['configManual'], 'callback_data' => $panel['configManual']],
            ],
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Status']['configStatuson'], $configkeyboard);
} elseif (preg_match('/vieworderall_(\w+)/', $datain, $dataget)) {
    $iduser = $dataget[1];
    $OrderUsers = select("invoice", "*", "id_user", $iduser, "fetchAll");
    foreach ($OrderUsers as $OrderUser) {
        $timeacc = jdate('Y/m/d H:i:s', $OrderUser['time_sell']);
        sendmessage($from_id, sprintf($textbotlang['Admin']['ManageUser']['Datails'], $OrderUser['id_invoice'], $OrderUser['Status'], $OrderUser['id_user'], $OrderUser['username'], $OrderUser['Service_location'], $OrderUser['name_product'], $OrderUser['price_product'], $OrderUser['Volume'], $OrderUser['Service_time'], $timeacc), null, 'HTML');
    }
    sendmessage($from_id, $textbotlang['Admin']['ManageUser']['SendOrder'], $keyboardadmin, 'HTML');
    step('home', $from_id);
}

if ($text == $textbotlang['Admin']['Discount']['titlebtnremove']) {
    sendmessage($from_id, $textbotlang['Admin']['Discount']['RemoveCode'], $json_list_Discount_list_admin, 'HTML');
    step('remove-Discount', $from_id);
} elseif ($user['step'] == "remove-Discount") {
    if (!in_array($text, $code_Discount)) {
        sendmessage($from_id, $textbotlang['Admin']['Discount']['NotCode'], null, 'HTML');
        return;
    }
    $stmt = $pdo->prepare("DELETE FROM Discount WHERE code = ?");
    $stmt->bindParam(1, $text);
    $stmt->execute();
    sendmessage($from_id, $textbotlang['Admin']['Discount']['RemovedCode'], $shopkeyboard, 'HTML');
}

if ($text == $textbotlang['Admin']['ManageUser']['removeorderbtn'] || $datain == "removeorderbtn") {
    if (USER_INLINE_KEYBOARD) {
        Editmessagetext($from_id, $message_id, $textbotlang['Admin']['ManageUser']['RemoveService'], $backadmin);
    } else {
        sendmessage($from_id, $textbotlang['Admin']['ManageUser']['RemoveService'], $backadmin, 'HTML');
    }
    step('removeservice', $from_id);
} elseif ($user['step'] == "removeservice") {
    $info_product = select("invoice", "*", "username", $text, "select");
    if (!$info_product || empty($info_product['username'])) {
        sendmessage($from_id, "❌ کاربر مورد نظر یافت نشد.", $keyboardadmin, 'HTML');
        step('home', $from_id);
        return;
    }
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $info_product['Service_location'], "select");
    $DataUserOut = $ManagePanel->DataUser($marzban_list_get['name_panel'], $text);
    if (isset($DataUserOut['status'])) {
        $ManagePanel->RemoveUser($marzban_list_get['name_panel'], $text);
    }
    $stmt = $pdo->prepare("UPDATE invoice SET Status = 'removed' WHERE username = ?");
    $stmt->bindParam(1, $text);
    $stmt->execute();
    sendmessage($from_id, $textbotlang['Admin']['ManageUser']['RemovedService'], $keyboardadmin, 'HTML');
    step('home', $from_id);
}

if ($text == $textbotlang['Admin']['managepanel']['methodusername'] || $datain == "methodusername") {
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['decmthodusername'], $MethodUsername, 'HTML');
    step('updatemethodusername', $from_id);
} elseif ($user['step'] == "updatemethodusername") {
    update("marzban_panel", "MethodUsername", $text, "name_panel", $user['Processing_value']);
    sendmessage($from_id, $textbotlang['Admin']['AlgortimeUsername']['SaveData'], $keyboardadmin, 'HTML');
    if ($text == $textbotlang['users']['customtextandrandom']) {
        step('getnamecustom', $from_id);
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['customnamesend'], $backuser, 'HTML');
        return;
    }
    step('home', $from_id);
} elseif ($user['step'] == "getnamecustom") {
    if (!preg_match('/^\w{3,32}$/', $text)) {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['invalidname'], $backadmin, 'html');
        return;
    }
    update("setting", "namecustome", $text);
    step('home', $from_id);
    $listpanel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    update("user", "Processing_value", $text, "id", $from_id);
    outtypepanel($listpanel['type'], $textbotlang['Admin']['managepanel']['savedname']);
}

if ($text == $textbotlang['Admin']['keyboardadmin']['finance'] || $datain == "finance") {
    $sqlstatus_cart = select("PaySetting", "ValuePay", "NamePay", "Cartstatus", "select")['ValuePay'];
    $sqlstatus_nowpayment = select("PaySetting", "ValuePay", "NamePay", "nowpaymentstatus", "select")['ValuePay'];
    $sqlstatus_aqayepardakht = select("PaySetting", "ValuePay", "NamePay", "statusaqayepardakht", "select")['ValuePay'];
    $status_cart = [
        'oncard' => $textbotlang['Admin']['turnon'],
        'offcard' => $textbotlang['Admin']['turnoff'],
    ][$sqlstatus_cart];
    $status_nowpayment = [
        'onnowpayment' => $textbotlang['Admin']['turnon'],
        'offnowpayment' => $textbotlang['Admin']['turnoff'],
    ][$sqlstatus_nowpayment];
    $status_qayepardakht = [
        'onaqayepardakht' => $textbotlang['Admin']['turnon'],
        'offaqayepardakht' => $textbotlang['Admin']['turnoff'],
    ][$sqlstatus_aqayepardakht];
    $keyboardmoeny = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['moeny']['setting'], 'callback_data' => "settingcart"],
                ['text' => $status_cart, 'callback_data' => "editpay-cart-" . $sqlstatus_cart],
                ['text' => $textbotlang['users']['moeny']['cart_to_Cart_btn'], 'callback_data' => "none"],
            ],
            [
                ['text' => $textbotlang['users']['moeny']['setting'], 'callback_data' => "SettingnowPayment"],
                ['text' => $status_nowpayment, 'callback_data' => "editpay-nowpayment-" . $sqlstatus_nowpayment],
                ['text' => $textbotlang['users']['moeny']['nowpayment_gateway_status'], 'callback_data' => "none"],
            ],
            [
                ['text' => $textbotlang['users']['moeny']['setting'], 'callback_data' => "Settingaqayepardakht"],
                ['text' => $status_qayepardakht, 'callback_data' => "editpay-aqayepardakht-" . $sqlstatus_aqayepardakht],
                ['text' => $textbotlang['users']['moeny']['mr_payment_gateway'], 'callback_data' => "none"],
            ],
        ]
    ]);
    sendmessage($from_id, $textbotlang['users']['moeny']['settingpay'], $keyboardmoeny, 'HTML');
} elseif (preg_match('/^editpay-(.*)-(.*)/', $datain, $dataget)) {
    $methodpay = $dataget[1];
    $status = $dataget[2];
    if ($methodpay == "cart") {
        if ($status == "oncard") {
            $value = "offcard";
        } else {
            $value = "oncard";
        }
        update("PaySetting", "ValuePay", $value, "NamePay", "Cartstatus");
    } elseif ($methodpay == "nowpayment") {
        if ($status == "onnowpayment") {
            $value = "offnowpayment";
        } else {
            $value = "onnowpayment";
        }
        update("PaySetting", "ValuePay", $value, "NamePay", "nowpaymentstatus");
    } elseif ($methodpay == "aqayepardakht") {
        if ($status == "onaqayepardakht") {
            $value = "offaqayepardakht";
        } else {
            $value = "onaqayepardakht";
        }
        update("PaySetting", "ValuePay", $value, "NamePay", "statusaqayepardakht");
    }
    $sqlstatus_cart = select("PaySetting", "ValuePay", "NamePay", "Cartstatus", "select")['ValuePay'];
    $sqlstatus_nowpayment = select("PaySetting", "ValuePay", "NamePay", "nowpaymentstatus", "select")['ValuePay'];
    $sqlstatus_aqayepardakht = select("PaySetting", "ValuePay", "NamePay", "statusaqayepardakht", "select")['ValuePay'];
    $status_cart = [
        'oncard' => $textbotlang['Admin']['turnon'],
        'offcard' => $textbotlang['Admin']['turnoff'],
    ][$sqlstatus_cart];
    $status_nowpayment = [
        'onnowpayment' => $textbotlang['Admin']['turnon'],
        'offnowpayment' => $textbotlang['Admin']['turnoff'],
    ][$sqlstatus_nowpayment];
    $status_qayepardakht = [
        'onaqayepardakht' => $textbotlang['Admin']['turnon'],
        'offaqayepardakht' => $textbotlang['Admin']['turnoff'],
    ][$sqlstatus_aqayepardakht];
    $keyboardmoeny = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['users']['moeny']['setting'], 'callback_data' => "settingcart"],
                ['text' => $status_cart, 'callback_data' => "editpay-cart-" . $sqlstatus_cart],
                ['text' => $textbotlang['users']['moeny']['cart_to_Cart_btn'], 'callback_data' => "none"],
            ],
            [
                ['text' => $textbotlang['users']['moeny']['setting'], 'callback_data' => "SettingnowPayment"],
                ['text' => $status_nowpayment, 'callback_data' => "editpay-nowpayment-" . $sqlstatus_nowpayment],
                ['text' => $textbotlang['users']['moeny']['nowpayment_gateway_status'], 'callback_data' => "none"],
            ],
            [
                ['text' => $textbotlang['users']['moeny']['setting'], 'callback_data' => "Settingaqayepardakht"],
                ['text' => $status_qayepardakht, 'callback_data' => "editpay-aqayepardakht-" . $sqlstatus_aqayepardakht],
                ['text' => $textbotlang['users']['moeny']['mr_payment_gateway'], 'callback_data' => "none"],
            ],
        ]
    ]);
    Editmessagetext($from_id,$message_id,$textbotlang['users']['moeny']['settingpay'], $keyboardmoeny);
} elseif ($datain == "settingcart") {
    sendmessage($from_id, $textbotlang['users']['selectoption'], $CartManage, 'HTML');
}

if ($text == $textbotlang['users']['moeny']['card_number_settings']) {
    $PaySetting = select("PaySetting", "ValuePay", "NamePay", "CartDescription", "select");
    sendmessage($from_id, sprintf($textbotlang['users']['moeny']['sendcart'], $PaySetting['ValuePay']), $backadmin, 'HTML');
    step('changecard', $from_id);
} elseif ($user['step'] == "changecard") {
    sendmessage($from_id, $textbotlang['Admin']['SettingPayment']['Savacard'], $CartManage, 'HTML');
    update("PaySetting", "ValuePay", $text, "NamePay", "CartDescription");
    step('home', $from_id);
}

if ($datain == "SettingnowPayment") {
    sendmessage($from_id, $textbotlang['users']['selectoption'], $NowPaymentsManage, 'HTML');
}

if ($text == $textbotlang['users']['moeny']['nowpayment_api']) {
    $PaySetting = select("PaySetting", "ValuePay", "NamePay", "apinowpayment", "select")['ValuePay'];
    sendmessage($from_id, sprintf($textbotlang['users']['moeny']['getapinowpayment'], $PaySetting), $backadmin, 'HTML');
    step('apinowpayment', $from_id);
} elseif ($user['step'] == "apinowpayment") {
    sendmessage($from_id, $textbotlang['Admin']['SettingnowPayment']['Savaapi'], $NowPaymentsManage, 'HTML');
    update("PaySetting", "ValuePay", $text, "NamePay", "apinowpayment");
    step('home', $from_id);
}

if ($datain == "Settingaqayepardakht") {
    sendmessage($from_id, $textbotlang['users']['selectoption'], $aqayepardakht, 'HTML');
}

if ($text == $textbotlang['users']['moeny']['mr_payment_merchant_settings']) {
    $PaySetting = select("PaySetting", "ValuePay", "NamePay", "merchant_id_aqayepardakht", "select");
    sendmessage($from_id, sprintf($textbotlang['users']['moeny']['getmarchent'], $PaySetting['ValuePay']), $backadmin, 'HTML');
    step('merchant_id_aqayepardakht', $from_id);
} elseif ($user['step'] == "merchant_id_aqayepardakht") {
    sendmessage($from_id, $textbotlang['Admin']['SettingnowPayment']['Savaapi'], $aqayepardakht, 'HTML');
    update("PaySetting", "ValuePay", $text, "NamePay", "merchant_id_aqayepardakht");
    step('home', $from_id);
}

if ($text == $textbotlang['Admin']['keyboardadmin']['manage_panel'] || $datain == "manage_panel") {
    if (USER_INLINE_KEYBOARD) {
        Editmessagetext($from_id, $message_id, "👇 لیست سرور ها به شرح زیر می باشد", $json_list_panel);
    } else {
        sendmessage($from_id, "👇 لیست سرور ها به شرح زیر می باشد", $json_list_panel, 'HTML');
    }
} elseif (strpos($datain, "edit_panel:") === 0) {
    deletemessage($from_id, $message_id);
    $panel_id = str_replace("edit_panel:", "", $datain);
    $listpanel = select("marzban_panel", "*", "id", $panel_id, "select");
    if ($listpanel !== false) {
        update("user", "Processing_value", $listpanel['name_panel'], "id", $from_id);
        $server_info = sprintf( $textbotlang['Admin']['managepanel']['serverpanel'], $listpanel['id'], $listpanel['name_panel'], $listpanel['username_panel'], $listpanel['password_panel'], $listpanel['url_panel']);
        outtypepanel($listpanel['type'], $server_info);
    } else {
        sendmessage($from_id, "❌ پنل مورد نظر پیدا نشد.", $backadmin, 'HTML');
    }
    step('home', $from_id);
}

if ($datain == "namepanel") {
    deletemessage($from_id, $message_id);
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['GetNameNew'], $backadmin, 'HTML');
    step('GetNameNew', $from_id);
} elseif ($user['step'] == "GetNameNew") {
    $typepanel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    outtypepanel($typepanel['type'], $textbotlang['Admin']['managepanel']['ChangedNmaePanel']);
    update("marzban_panel", "name_panel", $text, "name_panel", $user['Processing_value']);
    update("invoice", "Service_location", $text, "Service_location", $user['Processing_value']);
    update("product", "Location", $text, "Location", $user['Processing_value']);
    update("user", "Processing_value", $text, "id", $from_id);
    step('home', $from_id);
} 

if ($datain == "restartcore") {
    deletemessage($from_id, $message_id);
    $panel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    $sent = sendmessage($from_id, "⌛️");
    $sent_message_id = $sent['result']['message_id'];
    sleep(1);
    deletemessage($from_id, $sent_message_id);
    if ($panel !== false) {
        $response = Restart_XrayCore($panel['name_panel']);
        sendmessage($from_id, $response['message'], $json_list_panel, 'HTML');
    } else {
        sendmessage($from_id, "❌ پنل فعلی یافت نشد!", $json_list_panel, 'HTML');
    }
}

if ($datain == "editurl") {
    deletemessage($from_id, $message_id);
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['geturlnew'], $backadmin, 'HTML');
    step('GeturlNew', $from_id);
} elseif ($user['step'] == "GeturlNew") {
    if (!filter_var($text, FILTER_VALIDATE_URL)) {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['Invalid-domain'], $backadmin, 'HTML');
        return;
    }
    $typepanel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    outtypepanel($typepanel['type'], $textbotlang['Admin']['managepanel']['ChangedurlPanel']);
    update("marzban_panel", "url_panel", $text, "name_panel", $user['Processing_value']);
    step('home', $from_id);
} 

if ($datain == "editusername") {
    deletemessage($from_id, $message_id);
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['getusernamenew'], $backadmin, 'HTML');
    step('GetusernameNew', $from_id);
} elseif ($user['step'] == "GetusernameNew") {
    $typepanel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    outtypepanel($typepanel['type'], $textbotlang['Admin']['managepanel']['ChangedusernamePanel']);
    update("marzban_panel", "username_panel", $text, "name_panel", $user['Processing_value']);
    step('home', $from_id);
} 

if ($datain == "editpassword") {
    deletemessage($from_id, $message_id);
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['getpasswordnew'], $backadmin, 'HTML');
    step('GetpaawordNew', $from_id);
} elseif ($user['step'] == "GetpaawordNew") {
    $typepanel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    outtypepanel($typepanel['type'], $textbotlang['Admin']['managepanel']['ChangedpasswordPanel']);
    update("marzban_panel", "password_panel", $text, "name_panel", $user['Processing_value']);
    step('home', $from_id);
} 

if ($datain == "setinbound" || $text == $textbotlang['Admin']['managepanel']['setgroup']) {
        deletemessage($from_id, $message_id);
    if ($text == $textbotlang['Admin']['managepanel']['setgroup']) {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['keyboardpanel']['getgroup'], $backadmin, 'HTML');
    } else {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['keyboardpanel']['getidinbound'], $backadmin, 'HTML');
    }
    step('getinboundiid', $from_id);
} elseif ($user['step'] == "getinboundiid") {
    $typepanel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    outtypepanel($typepanel['type'], $textbotlang['Admin']['managepanel']['keyboardpanel']['setinbound']);
    update("marzban_panel", "inboundid", $text, "name_panel", $user['Processing_value']);
    step('home', $from_id);
} 

elseif ($text == $textbotlang['Admin']['managepanel']['keyboardpanel']['linksub']) {
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['geturlnew'], $backadmin, 'HTML');
    step('GeturlNewx', $from_id);
} elseif ($user['step'] == "GeturlNewx") {
    if (!filter_var($text, FILTER_VALIDATE_URL)) {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['Invalid-domain'], $backadmin, 'HTML');
        return;
    }
    $panel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    if ($panel['type'] == "x-ui") {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $text);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode != 200) {
            sendmessage($from_id, $textbotlang['Admin']['managepanel']['subinvalidDomain'], null, 'HTML');
            return;
        }
        if (curl_error($ch)) {
            sendmessage($from_id, $textbotlang['Admin']['managepanel']['subinvalidDomain'], null, 'HTML');
            return;
        }
        $protocol = ['vmess', 'vless', 'trojan', 'ss'];
        if (isBase64($response)) {
            $response = base64_decode($response);
        }
        $sub_check = explode('://', $response)[0];
        if (!in_array($sub_check, $protocol)) {
            sendmessage($from_id, $textbotlang['Admin']['managepanel']['subinvalid'], null, 'HTML');
            return;
        }
        $text = dirname($text);
    }
    outtypepanel($panel['type'], $textbotlang['Admin']['managepanel']['ChangedurlPanel']);
    update("marzban_panel", "linksubx", $text, "name_panel", $user['Processing_value']);
    step('home', $from_id);
} elseif ($user['step'] == "GetpaawordNew") {
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['ChangedpasswordPanel'], $optionMarzban, 'HTML');
    update("marzban_panel", "password_panel", $text, "name_panel", $user['Processing_value']);
    step('home', $from_id);
}

if (strpos($datain, "delete_panel:") === 0) {
    deletemessage($from_id, $message_id);
    $panel_id = str_replace("delete_panel:", "", $datain);
    $panel = select("marzban_panel", "*", "id", $panel_id, "select");
    if ($panel !== false) {
        $confirm_keyboard = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => "بله", 'callback_data' => "confirm_delete:{$panel['id']}"],
                    ['text' => "خیر", 'callback_data' => "cancel_delete"]
                ]
            ]
        ], JSON_UNESCAPED_UNICODE);
        sendmessage($from_id, "‼️ آیا از حذف سرور «{$panel['name_panel']}» اطمینان دارید؟", $confirm_keyboard, 'HTML');
    } else {
        sendmessage($from_id, "❌ سرور یافت نشد.", $backadmin, 'HTML');
    }
} elseif (strpos($datain, "confirm_delete:") === 0) {
    $panel_id = str_replace("confirm_delete:", "", $datain);
    $panel = select("marzban_panel", "*", "id", $panel_id, "select");
    deletemessage($from_id, $message_id);
    $sent = sendmessage($from_id, "⌛️");
    $sent_message_id = $sent['result']['message_id'];
    sleep(2);
    if ($panel !== false) {
        $stmt = $pdo->prepare("DELETE FROM marzban_panel WHERE id = ?");
        $stmt->execute([$panel_id]);
        sleep(1);
        deletemessage($from_id, $sent_message_id);
        sendmessage($from_id, "✅ سرور «{$panel['name_panel']}» با موفقیت حذف شد.", $backadmin, 'HTML');
    } else {
        sendmessage($from_id, "❌ خطا در حذف سرور، لطفاً مجدداً تلاش کنید.", $backadmin, 'HTML');
    }
} elseif ($datain == "cancel_delete") {
    deletemessage($from_id, $message_id);
    sendmessage($from_id, "👇 لیست سرور ها به شرح زیر می باشد", $json_list_panel, 'HTML');
}

if ($text == $textbotlang['Admin']['Balance']['SendBalanceAll'] || $datain == "SendBalanceAll") {
    if (USER_INLINE_KEYBOARD) {
        Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Balance']['addallbalance'], $backadmin);
    } else {
        sendmessage($from_id, $textbotlang['Admin']['Balance']['addallbalance'], $backadmin, 'HTML');
    }
    step('add_Balance_all', $from_id);
} elseif ($user['step'] == "add_Balance_all") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['Balance']['Invalidprice'], $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['Balance']['AddBalanceUsers'], $admin_manage_user, 'HTML');
    $stmt = $pdo->prepare("UPDATE user SET Balance = Balance + :balance");
    $stmt->bindParam(':balance', $text, PDO::PARAM_INT);
    $stmt->execute();
    step('home', $from_id);
}

if ($text == $textbotlang['Admin']['Discountsell']['create'] || $datain == "create_discount_sell") {
    deletemessage($from_id, $message_id);
    sendmessage($from_id, $textbotlang['Admin']['Discountsell']['GetCode'], $backadmin, 'HTML');
    step('get_codesell', $from_id);
} elseif ($user['step'] == "get_codesell") {
    $stmt = $pdo->prepare("SELECT codeDiscount FROM DiscountSell");
    $stmt->execute();
    $SellDiscount = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (in_array($text, $SellDiscount)) {
        sendmessage($from_id, $textbotlang['Admin']['Discount']['Discountused'], $backadmin, 'HTML');
        return;
    }
    if (!preg_match('/^[A-Za-z\d]+$/', $text)) {
        sendmessage($from_id, $textbotlang['Admin']['Discount']['ErrorCode'], $backadmin, 'HTML');
        return;
    }
    $stmt = $pdo->prepare("INSERT INTO DiscountSell (codeDiscount, usedDiscount, price, limitDiscount, usefirst) VALUES (?, 0, 0, 0, 0)");
    $stmt->execute([$text]);
    update("user", "Processing_value", $text, "id", $from_id);
    sendmessage($from_id, $textbotlang['Admin']['Discount']['PriceCodesell'], $backadmin, 'HTML');
    step('get_price_codesell', $from_id);
} elseif ($user['step'] == "get_price_codesell") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['Balance']['Invalidprice'], $backadmin, 'HTML');
        return;
    }
    update("DiscountSell", "price", $text, "codeDiscount", $user['Processing_value']);
    sendmessage($from_id, $textbotlang['Admin']['Discountsell']['getlimit'], $backadmin, 'HTML');
    step('getlimitcode', $from_id);
} elseif ($user['step'] == "getlimitcode") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, "❌ لطفاً یک عدد معتبر برای محدودیت وارد کنید.", $backadmin, 'HTML');
        return;
    }
    update("DiscountSell", "limitDiscount", $text, "codeDiscount", $user['Processing_value']);
    sendmessage($from_id, $textbotlang['Admin']['Discount']['typediscount'], $backadmin, 'HTML');
    step('getusefirst', $from_id);
} elseif ($user['step'] == "getusefirst") {
    update("DiscountSell", "usefirst", $text, "codeDiscount", $user['Processing_value']);
    sendmessage($from_id, $textbotlang['Admin']['Discount']['SaveCode'], $keyboardadmin, 'HTML');
    step('home', $from_id);
}

if ($text == $textbotlang['Admin']['Discountsell']['remove'] || $datain == "remove_discount_sell") {
    deletemessage($from_id, $message_id);
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'DiscountSell'");
    $stmt->execute();
    $table_exists = count($stmt->fetchAll()) > 0;
    $SellDiscount = [];
    if ($table_exists) {
        $stmt = $pdo->prepare("SELECT * FROM DiscountSell");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($rows) === 0) {
            sendmessage($from_id, "❌ هیچ کد تخفیفی موجود نیست.", $backadmin, 'HTML');
            step('home', $from_id);
            return;
        }
        $inline_keyboard = [];
        foreach ($rows as $row) {
            $SellDiscount[] = $row['codeDiscount'];
            $inline_keyboard[] = [
                ['text' => $row['codeDiscount'], 'callback_data' => 'discount_' . $row['codeDiscount']]
            ];
        }
        $inline_keyboard[] = [
            ['text' => $textbotlang['Admin']['Back-Adminment'], 'callback_data' => 'back_admin']
        ];
        $json_list_Discount = json_encode(['inline_keyboard' => $inline_keyboard], JSON_UNESCAPED_UNICODE);
    }
    sendmessage($from_id, $textbotlang['Admin']['Discount']['RemoveCode'], $json_list_Discount, 'HTML');
    step('remove-Discountsell', $from_id);
} elseif ($user['step'] == "remove-Discountsell") {
    deletemessage($from_id, $message_id);
    $selected_discount = isset($datain) && !empty($datain) ? str_replace('discount_', '', $datain) : $text;
    $stmt = $pdo->prepare("SELECT codeDiscount FROM DiscountSell WHERE codeDiscount = ?");
    $stmt->execute([$selected_discount]);
    $discount_exists = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$discount_exists) {
        sendmessage($from_id, $textbotlang['Admin']['Discount']['NotCode'], $backadmin, 'HTML');
        return;
    }
    $stmt = $pdo->prepare("DELETE FROM DiscountSell WHERE codeDiscount = ?");
    $stmt->execute([$selected_discount]);
    sendmessage($from_id, $textbotlang['Admin']['Discount']['RemovedCode'], $shopkeyboard, 'HTML');
    step('home', $from_id);
}

if ($text == $textbotlang['Admin']['keyboardadmin']['affiliate_settings'] || $datain == "affiliate_settings") {
    sendmessage($from_id, $textbotlang['users']['selectoption'], $affiliates, 'HTML');
} elseif ($datain == "1") {
    update("affiliates", "affiliatesstatus", "0");
    $affiliatesvalue = select("affiliates", "*", null, null, "select")['affiliatesstatus'];
    $keyboardaffiliates = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $affiliatesvalue, 'callback_data' => $affiliatesvalue],
            ],
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Status']['affiliatesStatusOff'], $keyboardaffiliates);
} elseif ($datain == "0") {
    update("affiliates", "affiliatesstatus", "1");
    $affiliatesvalue = select("affiliates", "*", null, null, "select")['affiliatesstatus'];
    $keyboardaffiliates = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $affiliatesvalue, 'callback_data' => $affiliatesvalue],
            ],
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Status']['affiliatesStatuson'], $keyboardaffiliates);
}


if ($text == $textbotlang['Admin']['affiliate']['Percentageset']) {
    sendmessage($from_id, $textbotlang['users']['affiliates']['setpercentage'], $backadmin, 'HTML');
    step('setpercentage', $from_id);
} elseif ($user['step'] == "setpercentage") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['invalidvalue'], null, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['users']['affiliates']['changedpercentage'], $affiliates, 'HTML');
    update("affiliates", "affiliatespercentage", $text);
    step('home', $from_id);

} elseif ($text == $textbotlang['Admin']['affiliate']['porsantafterbuy']) {
    $marzbancommission = select("affiliates", "*", null, null, "select");
    $keyboardcommission = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $marzbancommission['status_commission'], 'callback_data' => $marzbancommission['status_commission']],
            ],
        ]
    ]);
    sendmessage($from_id, $textbotlang['Admin']['Status']['commission'], $keyboardcommission, 'HTML');
} elseif ($datain == "oncommission") {
    update("affiliates", "status_commission", "offcommission");
    $marzbancommission = select("affiliates", "*", null, null, "select");
    $keyboardcommission = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $marzbancommission['status_commission'], 'callback_data' => $marzbancommission['status_commission']],
            ],
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Status']['commissionStatusOff'], $keyboardcommission);
} elseif ($datain == "offcommission") {
    update("affiliates", "status_commission", "oncommission");
    $marzbancommission = select("affiliates", "*", null, null, "select");
    $keyboardcommission = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $marzbancommission['status_commission'], 'callback_data' => $marzbancommission['status_commission']],
            ],
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Status']['commissionStatuson'], $keyboardcommission);
} elseif ($datain == "onDiscountaffiliates") {
    update("affiliates", "Discount", "offDiscountaffiliates");
    $marzbanDiscountaffiliates = select("affiliates", "*", null, null, "select");
    $keyboardDiscountaffiliates = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $marzbanDiscountaffiliates['Discount'], 'callback_data' => $marzbanDiscountaffiliates['Discount']],
            ],
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Status']['DiscountaffiliatesStatusOff'], $keyboardDiscountaffiliates);
} elseif ($datain == "offDiscountaffiliates") {
    update("affiliates", "Discount", "onDiscountaffiliates");
    $marzbanDiscountaffiliates = select("affiliates", "*", null, null, "select");
    $keyboardDiscountaffiliates = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $marzbanDiscountaffiliates['Discount'], 'callback_data' => $marzbanDiscountaffiliates['Discount']],
            ],
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Status']['DiscountaffiliatesStatuson'], $keyboardDiscountaffiliates);
}

if ($text == $textbotlang['Admin']['affiliate']['giftstart']) {
    sendmessage($from_id, $textbotlang['users']['affiliates']['priceDiscount'], $backadmin, 'HTML');
    step('getdiscont', $from_id);
} elseif ($user['step'] == "getdiscont") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['invalidvalue'], null, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['users']['affiliates']['changedpriceDiscount'], $affiliates, 'HTML');
    update("affiliates", "price_Discount", $text);
    step('home', $from_id);
}

if ($datain == "on_hold_status") {
    $panel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    if ($panel['onholdstatus'] == null) {
        update("marzban_panel", "onholdstatus", "offonhold", "name_panel", $user['Processing_value']);
    }
    $panel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    $onhold_Status = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $panel['onholdstatus'], 'callback_data' => $panel['onholdstatus']],
            ],
        ]
    ]);
    sendmessage($from_id, $textbotlang['Admin']['Status']['onhold'], $onhold_Status, 'HTML');
}

if ($datain == "ononhold") {
    update("marzban_panel", "onholdstatus", "offonhold", "name_panel", $user['Processing_value']);
    $panel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    $onhold_Status = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $panel['onholdstatus'], 'callback_data' => $panel['onholdstatus']],
            ],
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Status']['offstatus'], $onhold_Status);
} elseif ($datain == "offonhold") {
    update("marzban_panel", "onholdstatus", "ononhold", "name_panel", $user['Processing_value']);
    $panel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    $onhold_Status = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $panel['onholdstatus'], 'callback_data' => $panel['onholdstatus']],
            ],
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Status']['onstatus'], $onhold_Status);
}

if ($text == $textbotlang['Admin']['keyboardadmin']['settingscron'] || $datain == "settingscron") {
    if (!(function_exists('shell_exec') && is_callable('shell_exec'))) {
        $crontest = "*/15 * * * * curl https://$domainhosts/cron/configtest.php";
        $cronvolume = "*/1 * * * *  curl https://$domainhosts/cron/cronvolume.php";
        $crontime = "*/1 * * * *  curl https://$domainhosts/cron/cronday.php";
        $cronremove = "*/1 * * * *  curl https://$domainhosts/cron/removeexpire.php";
        sendmessage($from_id, sprintf($textbotlang['Admin']['cron']['active_manual'], $crontest, $cronvolume, $crontime, $cronremove), null, 'HTML');
        return;
    }
    if (USER_INLINE_KEYBOARD) {
        Editmessagetext($from_id, $message_id, $textbotlang['users']['selectoption'], $keyboardcronjob);
    } else {
        sendmessage($from_id, $textbotlang['users']['selectoption'], $keyboardcronjob, 'HTML');
    }
}

if ($text == $textbotlang['Admin']['cron']['test']['active'] || $datain == "test_active") {
    sendmessage($from_id, $textbotlang['Admin']['cron']['test']['dec'], null, 'HTML');
    $phpFilePath = escapeshellarg("https://$domainhosts/cron/configtest.php");
    $cronCommand = "*/15 * * * * curl $phpFilePath";
    $existingCronCommands = shell_exec('crontab -l');
    if (strpos($existingCronCommands, $cronCommand) === false) {
        $command = "(crontab -l ; echo '$cronCommand') | crontab -";
        shell_exec($command);
    }
}

if ($text == $textbotlang['Admin']['cron']['test']['disable'] || $datain == "test_disable") {
    sendmessage($from_id, $textbotlang['Admin']['cron']['test']['disabled'], null, 'HTML');
    $currentCronJobs = shell_exec("crontab -l");
    $url = escapeshellarg("https://$domainhosts/cron/configtest.php");
    $jobToRemove = "*/15 * * * * curl $url";
    $newCronJobs = preg_replace('/' . preg_quote($jobToRemove, '/') . '/', '', $currentCronJobs);
    file_put_contents('/tmp/crontab.txt', $newCronJobs);
    shell_exec('crontab /tmp/crontab.txt');
    unlink('/tmp/crontab.txt');
}

if ($text == $textbotlang['Admin']['cron']['volume']['active'] || $datain == "volume_active") {
    sendmessage($from_id, $textbotlang['Admin']['cron']['volume']['dec'], null, 'HTML');
    $phpFilePath = escapeshellarg("https://$domainhosts/cron/cronvolume.php");
    $cronCommand = "*/1 * * * * curl $phpFilePath";
    $existingCronCommands = shell_exec('crontab -l');
    if (strpos($existingCronCommands, $cronCommand) === false) {
        $command = "(crontab -l ; echo '$cronCommand') | crontab -";
        shell_exec($command);
    }
}

if ($text == $textbotlang['Admin']['cron']['volume']['disable'] || $datain == "volume_disable") {
    sendmessage($from_id, $textbotlang['Admin']['cron']['test']['disabled'], null, 'HTML');
    $currentCronJobs = shell_exec("crontab -l");
    $jobToRemove = "*/1 * * * * curl https://$domainhosts/cron/cronvolume.php";
    $newCronJobs = preg_replace('/' . preg_quote($jobToRemove, '/') . '/', '', $currentCronJobs);
    file_put_contents('/tmp/crontab.txt', $newCronJobs);
    shell_exec('crontab /tmp/crontab.txt');
    unlink('/tmp/crontab.txt');
}

if ($text == $textbotlang['Admin']['cron']['time']['active'] || $datain == "time_active") {
    sendmessage($from_id, $textbotlang['Admin']['cron']['time']['dec'], null, 'HTML');
    $phpFilePath = escapeshellarg("https://$domainhosts/cron/cronday.php");
    $cronCommand = "*/1 * * * * curl $phpFilePath";
    $existingCronCommands = shell_exec('crontab -l');
    if (strpos($existingCronCommands, $cronCommand) === false) {
        $command = "(crontab -l ; echo '$cronCommand') | crontab -";
        shell_exec($command);
    }
}

if ($text == $textbotlang['Admin']['cron']['time']['disable'] || $datain == "time_disable") {
    sendmessage($from_id, $textbotlang['Admin']['cron']['test']['disabled'], null, 'HTML');
    $currentCronJobs = shell_exec("crontab -l");
    $jobToRemove = "*/1 * * * * curl https://$domainhosts/cron/cronday.php";
    $newCronJobs = preg_replace('/' . preg_quote($jobToRemove, '/') . '/', '', $currentCronJobs);
    file_put_contents('/tmp/crontab.txt', $newCronJobs);
    shell_exec('crontab /tmp/crontab.txt');
    unlink('/tmp/crontab.txt');
}

if ($text == $textbotlang['Admin']['cron']['remove']['active'] || $datain == "remove_active") {
    sendmessage($from_id, $textbotlang['Admin']['cron']['remove']['dec'], null, 'HTML');
    $phpFilePath = "https://$domainhosts/cron/removeexpire.php";
    $cronCommand = "*/1 * * * * curl $phpFilePath";
    $existingCronCommands = shell_exec('crontab -l');
    if (strpos($existingCronCommands, $cronCommand) === false) {
        $command = "(crontab -l ; echo '$cronCommand') | crontab -";
        shell_exec($command);
    }
}

if ($text == $textbotlang['Admin']['cron']['remove']['disable'] || $datain == "remove_disable") {
    sendmessage($from_id, $textbotlang['Admin']['cron']['test']['disabled'], null, 'HTML');
    $currentCronJobs = shell_exec("crontab -l");
    $jobToRemove = "*/1 * * * * curl https://$domainhosts/cron/removeexpire.php";
    $newCronJobs = preg_replace('/' . preg_quote($jobToRemove, '/') . '/', '', $currentCronJobs);
    file_put_contents('/tmp/crontab.txt', $newCronJobs);
    shell_exec('crontab /tmp/crontab.txt');
    unlink('/tmp/crontab.txt');
}

if ($text == $textbotlang['Admin']['keyboardadmin']['user_search'] || $datain == "user_search") {
    if (USER_INLINE_KEYBOARD) {
        Editmessagetext($from_id, $message_id, $textbotlang['Admin']['ManageUser']['BlockUserId'], $backadmin);
    } else {
        sendmessage($from_id, $textbotlang['Admin']['ManageUser']['BlockUserId'], $backadmin, 'HTML');
    }
    step('show_infos', $from_id);
} elseif ($user['step'] == "show_infos") {
    if (!in_array($text, $users_ids)) {
        sendmessage($from_id, $textbotlang['Admin']['not-user'], $backadmin, 'HTML');
        return;
    }
    $date = date("Y-m-d");
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM invoice WHERE (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn') AND id_user = :id_user");
    $stmt->bindParam(':id_user', $text);
    $stmt->execute();
    $dayListSell = $stmt->rowCount();
    $stmt = $pdo->prepare("SELECT SUM(price) FROM Payment_report WHERE payment_Status = 'paid' AND id_user = :id_user");
    $stmt->bindParam(':id_user', $text);
    $stmt->execute();
    $balanceall = $stmt->fetch(PDO::FETCH_ASSOC)['SUM(price)'];
    $stmt = $pdo->prepare("SELECT SUM(price_product) FROM invoice WHERE (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn') AND id_user = :id_user");
    $stmt->bindParam(':id_user', $text);
    $stmt->execute();
    $subbuyuser = $stmt->fetch(PDO::FETCH_ASSOC)['SUM(price_product)'];
    $user = select("user", "*", "id", $text, "select");
    if ($subbuyuser == null)
        $subbuyuser = 0;
    $keyboardmanage = [
        'inline_keyboard' => [
            [['text' => $textbotlang['Admin']['ManageUser']['addbalanceuser'], 'callback_data' => "addbalanceuser_" . $text], ['text' => $textbotlang['Admin']['ManageUser']['lowbalanceuser'], 'callback_data' => "lowbalanceuser_" . $text],],
            [['text' => $textbotlang['Admin']['ManageUser']['banuserlist'], 'callback_data' => "banuserlist_" . $text], ['text' => $textbotlang['Admin']['ManageUser']['unbanuserlist'], 'callback_data' => "unbanuserr_" . $text]],
            [['text' => $textbotlang['Admin']['ManageUser']['confirmnumber'], 'callback_data' => "confirmnumber_" . $text], ['text' => $textbotlang['Admin']['getlimitusertest']['setlimitbtn'], 'callback_data' => "limitusertest_" . $text]],
            [['text' => $textbotlang['Admin']['ManageUser']['verify'], 'callback_data' => "verify_" . $text], ['text' => $textbotlang['Admin']['ManageUser']['removeverify'], 'callback_data' => "verifyun_" . $text]],
            [['text' => $textbotlang['Admin']['ManageUser']['vieworderuser'], 'callback_data' => "vieworderall_" . $text], ['text' => $textbotlang['Admin']['ManageUser']['addorder'], 'callback_data' => "addordermanualـ" . $text]],
            [['text' => $textbotlang['Admin']['Back-Adminment'], 'callback_data' => 'back_admin']]
        ]
    ];
    $keyboardmanage = json_encode($keyboardmanage);
    $user['Balance'] = number_format($user['Balance']);
    $lastmessage = jdate('Y/m/d H:i:s', $user['last_message_time']);
    sendmessage($from_id, sprintf($textbotlang['Admin']['ManageUser']['infouser'], $user['User_Status'], $user['username'], $text, $text, $lastmessage, $user['limit_usertest'], $user['number'], $user['Balance'], $dayListSell, number_format($balanceall), number_format($subbuyuser), $user['affiliatescount'], $user['affiliates'], $user['verify']), $keyboardmanage, 'HTML');
    step('home', $from_id);
}

if ($text == $textbotlang['Admin']['cron']['remove']['timeset'] || $datain == "timeset") {
    sendmessage($from_id, $textbotlang['Admin']['cron']['remove']['dectime'], $backadmin, 'HTML');
    step("gettimeremove", $from_id);
} elseif ($user['step'] == "gettimeremove") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['cron']['remove']['invalidtime'], $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['cron']['remove']['timeseted'], $keyboardcronjob, 'HTML');
    step("home", $from_id);
    update("setting", "removedayc", $text, null, null);
}

if ($text == $textbotlang['users']['stateus']['manageService']) {
    sendmessage($from_id, $textbotlang['users']['stateus']['manageServicedec'], $backadmin, 'HTML');
    step('getservceid',$from_id);
} elseif ($user['step'] == "getservceid") {
    $userdata = getuserm($text, $user['Processing_value']);
    if (isset($userdata['detail']) and $userdata['detail'] == "User not found") {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['keyboardpanel']['usernotfount'], null, 'HTML');
        return;
    }
    update("marzban_panel", "proxies", json_encode($userdata['service_ids']), "name_panel", $user['Processing_value']);
    step("home", $from_id);
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['setsetting'], $optionMarzneshin, 'HTML');
} elseif ($text == $textbotlang['Admin']['Help']['edithelp'] || $datain == "edithelp") {
    sendmessage($from_id, $textbotlang['Admin']['Help']['selecthelpforedit'], $json_list_help, 'HTML');
    step("getnameforedite", $from_id);
} elseif ($user['step'] == "getnameforedite") {
    sendmessage($from_id, $textbotlang['users']['selectoption'], $helpedit, 'HTML');
    update("user", "Processing_value", $text, "id", $from_id);
    step("home", $from_id);
} elseif ($datain == "changehelpname") {
    step("changenamehelp", $from_id);
    sendmessage($from_id, $textbotlang['Admin']['Help']['change']['sendnewname'], $backadmin, 'HTML');
} elseif ($datain == "changehelpdec") {
    step("changedeshelp", $from_id);
    sendmessage($from_id, $textbotlang['Admin']['Help']['change']['newdec'], $backadmin, 'HTML');
} elseif ($datain == "changehelpmedia") {
    step("changemedia", $from_id);
    sendmessage($from_id, $textbotlang['Admin']['Help']['change']['editmedianew'], $backadmin, 'HTML');
} elseif ($text == $textbotlang['Admin']['Help']['change']['name']) {
    sendmessage($from_id, $textbotlang['Admin']['Help']['change']['sendnewname'], $backadmin, 'HTML');
    step('changenamehelp', $from_id);
} elseif ($user['step'] == "changenamehelp") {
    if (strlen($text) >= 150) {
        sendmessage($from_id, $textbotlang['Admin']['Help']['change']['namemax'], null, 'HTML');
        return;
    }
    update("help", "name_os", $text, "name_os", $user['Processing_value']);
    sendmessage($from_id, $textbotlang['Admin']['Help']['change']['updated'], $json_list_helpkey, 'HTML');
    step('home', $from_id);
} elseif ($text == $textbotlang['Admin']['Help']['change']['dec']) {
    sendmessage($from_id, $textbotlang['Admin']['Help']['change']['newdec'], $backadmin, 'HTML');
    step('changedeshelp', $from_id);
} elseif ($user['step'] == "changedeshelp") {
    update("help", "Description_os", $text, "name_os", $user['Processing_value']);
    sendmessage($from_id, $textbotlang['Admin']['Help']['change']['updated'], $helpedit, 'HTML');
    step('home', $from_id);
} elseif ($text == $textbotlang['Admin']['Help']['change']['editmedia']) {
    sendmessage($from_id, $textbotlang['Admin']['Help']['change']['editmedianew'], $backadmin, 'HTML');
    step('changemedia', $from_id);
} elseif ($user['step'] == "changemedia") {
    if ($photo) {
        if (isset($photoid))
            update("help", "Media_os", $photoid, "name_os", $user['Processing_value']);
        update("help", "type_Media_os", "photo", "name_os", $user['Processing_value']);
    } elseif ($video) {
        if (isset($videoid))
            update("help", "Media_os", $videoid, "name_os", $user['Processing_value']);
        update("help", "type_Media_os", "video", "name_os", $user['Processing_value']);
    }
    sendmessage($from_id, $textbotlang['Admin']['Help']['change']['updated'], $helpedit, 'HTML');
    step('home', $from_id);
} elseif ($text == $textbotlang['Admin']['managepanel']['setinbound']) {
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['setinbounddec'], $backadmin, 'HTML');
    step("setinboundandprotocol", $from_id);
} elseif ($user['step'] == "setinboundandprotocol") {
    $panel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    if ($panel['type'] == "marzban") {
        $DataUserOut = getuser($text, $user['Processing_value']);
        if ((isset($DataUserOut['msg']) && $DataUserOut['msg'] == "User not found") or !isset($DataUserOut['proxies'])) {
            sendmessage($from_id, $textbotlang['users']['status']['usernotfound'], null, 'html');
            return;
        }
        foreach ($DataUserOut['proxies'] as $key => &$value) {
            if ($key == "shadowsocks") {
                unset($DataUserOut['proxies'][$key]['password']);
            } elseif ($key == "trojan") {
                unset($DataUserOut['proxies'][$key]['password']);
            } else {
                unset($DataUserOut['proxies'][$key]['id']);
            }
            if (count($DataUserOut['proxies'][$key]) == 0) {
                $DataUserOut['proxies'][$key] = new stdClass();
            }
        }
        update("marzban_panel", "inbounds", json_encode($DataUserOut['inbounds']), "name_panel", $user['Processing_value']);
        update("marzban_panel", "proxies", json_encode($DataUserOut['proxies']), "name_panel", $user['Processing_value']);
    } else {
        $data = GetClientsS_UI($text, $panel['name_panel']);
        if (count($data) == 0) {
            sendmessage($from_id, $textbotlang['Admin']['managepanel']['keyboardpanel']['usernotfount'], $options_ui, 'HTML');
            return;
        }
        $servies = [];
        foreach ($data['inbounds'] as $service) {
            $servies[] = $service;
        }
        update("marzban_panel", "proxies", json_encode($servies, true), "name_panel", $user['Processing_value']);
    }
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['setedinbound'], $optionMarzban, 'HTML');
    step("home", $from_id);
} elseif ($text == $textbotlang['Admin']['keyboardadmin']['seetingstatus'] || $datain == "seetingstatus") {
    if (!(function_exists('shell_exec') && is_callable('shell_exec'))) {
        $cronstatus = 1;
        $cronCommand = "*/4 * * * * curl https://$domainhosts/cron/croncard.php";
        sendmessage($from_id, sprintf($textbotlang['Admin']['cron']['active_manual_card'], $cronCommand), null, 'HTML');
    } else {
        $cronCommand = "*/4 * * * * curl https://$domainhosts/cron/croncard.php";
        $existingCronCommands = shell_exec('crontab -l');
        if (strpos($existingCronCommands, $cronCommand) === false) {
            $cronstatus = 0;
        } else {
            $cronstatus = 1;
        }
    }
    $setting = select("setting", "*");
    $affiliates = select("affiliates", "*");
    $name_status = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['Bot_Status']];
    $NotUser_Status = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['NotUser']];
    $help_Status = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['help_Status']];
    $affiliate_Status = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$affiliates['affiliatesstatus']];
    $get_number_Status = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['get_number']];
    $statusv_verify = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['status_verify']];
    $statusv_category = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['statuscategory']];
    $status_Automatic_confirmation = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$cronstatus];
    $status_copy_cart = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['copy_cart']];
    $status_keyboard = [
        'on' => $textbotlang['Admin']['Status']['statuson'],
        'off' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['inline_keyboard']];
    $inline_keyboard = [
        [
            ['text' => $textbotlang['Admin']['Status']['statussubject'], 'callback_data' => "subjectde"],
            ['text' => $textbotlang['Admin']['Status']['subject'], 'callback_data' => "subject"],
        ],
        [
            ['text' => $name_status, 'callback_data' => "editstsuts-statusbot-{$setting['Bot_Status']}"],
            ['text' => $textbotlang['Admin']['Status']['stautsbot'], 'callback_data' => "statusbot"],
        ],
        [
            ['text' => $NotUser_Status, 'callback_data' => "editstsuts-NotUser-{$setting['NotUser']}"],
            ['text' => $textbotlang['users']['maangeuser'], 'callback_data' => "NotUser"],
        ],
        [
            ['text' => $help_Status, 'callback_data' => "editstsuts-help_Status-{$setting['help_Status']}"],
            ['text' => $textbotlang['Admin']['Help']['statushelp'], 'callback_data' => "help_Status"],
        ],
        [
            ['text' => $affiliate_Status, 'callback_data' => "editstsuts-affiliate_Status-{$affiliates['affiliatesstatus']}"],
            ['text' => $textbotlang['Admin']['affiliate']['status'], 'callback_data' => "affiliate_Status"],
        ],
        [
            ['text' => $get_number_Status, 'callback_data' => "editstsuts-get_number-{$setting['get_number']}"],
            ['text' => $textbotlang['Admin']['ManageUser']['verifynumber'], 'callback_data' => "get_number"],
        ],
        [
            ['text' => $statusv_verify, 'callback_data' => "editstsuts-verify-{$setting['status_verify']}"],
            ['text' => $textbotlang['Admin']['ManageUser']['verify'], 'callback_data' => "status_verify"],
        ],
        [
            ['text' => $statusv_category, 'callback_data' => "editstsuts-category-{$setting['statuscategory']}"],
            ['text' => $textbotlang['Admin']['category']['status'], 'callback_data' => "statuscategory"],
        ],
        [
            ['text' => $status_Automatic_confirmation, 'callback_data' => "editstsuts-Automatic_confirmation-$cronstatus"],
            ['text' => $textbotlang['Admin']['Automatic_confirmation']['title'], 'callback_data' => "Automatic_confirmation"],
        ],
        [
            ['text' => $status_copy_cart, 'callback_data' => "editstsuts-copycart-{$setting['copy_cart']}"],
            ['text' => $textbotlang['users']['moeny']['copy_cart_status'], 'callback_data' => "copycart"],
        ],
        [
            ['text' => $status_keyboard, 'callback_data' => "editstsuts-inline_keyboard-{$setting['inline_keyboard']}"],
            ['text' => "حالت شیشه ای", 'callback_data' => "inline_keyboard"],
        ]
    ];
    if ($setting['inline_keyboard'] === 'on') {
        $inline_keyboard[] = [
            ['text' => $textbotlang['Admin']['Back-Adminment'], 'callback_data' => 'back_admin']
        ];
    }
    $Bot_Status = json_encode(['inline_keyboard' => $inline_keyboard], JSON_UNESCAPED_UNICODE);
    if (USER_INLINE_KEYBOARD) {
        Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Status']['BotTitle'], $Bot_Status);
    } else {
        sendmessage($from_id, $textbotlang['Admin']['Status']['BotTitle'], $Bot_Status, 'HTML');
    }
} elseif (preg_match('/^editstsuts-(.*)-(.*)/', $datain, $dataget)) {
    $type = $dataget[1];
    $value = $dataget[2];
    if ($type == "statusbot") {
        if ($value == "1") {
            $valuenew = "0";
        } else {
            $valuenew = "1";
        }
        update("setting", "Bot_Status", $valuenew);
    } elseif ($type == "NotUser") {
        if ($value == "1") {
            $valuenew = "0";
        } else {
            $valuenew = "1";
        }
        update("setting", "NotUser", $valuenew);
    } elseif ($type == "help_Status") {
        if ($value == "1") {
            $valuenew = "0";
        } else {
            $valuenew = "1";
        }
        update("setting", "help_Status", $valuenew);
    } elseif ($type == "affiliate_Status") {
        if ($value == "1") {
            $valuenew = "0";
        } else {
            $valuenew = "1";
        }
        update("affiliates", "affiliatesstatus", $valuenew);
    } elseif ($type == "get_number") {
        if ($value == "1") {
            $valuenew = "0";
        } else {
            $valuenew = "1";
        }
        update("setting", "get_number", $valuenew);
    } elseif ($type == "verify") {
        if ($value == "1") {
            $valuenew = "0";
        } else {
            $valuenew = "1";
        }
        update("setting", "status_verify", $valuenew);
    } elseif ($type == "category") {
        if ($value == "1") {
            $valuenew = "0";
        } else {
            $valuenew = "1";
        }
        update("setting", "statuscategory", $valuenew);
    } elseif ($type == "copycart") {
        if ($value == "1") {
            $valuenew = "0";
        } else {
            $valuenew = "1";
        }
        update("setting", "copy_cart", $valuenew);
    } elseif ($type == "inline_keyboard") {
        if ($value == "on") {
            $valuenew = "off";
        } else {
            $valuenew = "on";
        }
        update("setting", "inline_keyboard", $valuenew);
        $keyboard_empty = json_encode(['remove_keyboard' => true]);
        sendmessage($from_id, "حالت کیبورد ربات تغییر کرد ربات را مجددا /start کنید", $keyboard_empty, 'HTML');
    } elseif ($type == "Automatic_confirmation") {
        if (!(function_exists('shell_exec') && is_callable('shell_exec'))) {
            $cronstatus = 1;
            $cronCommand = "*/4 * * * * curl https://$domainhosts/cron/croncard.php";
            sendmessage($from_id, sprintf($textbotlang['Admin']['cron']['active_manual_card'], $cronCommand), null, 'HTML');
        } else {
            if ($value == "1") {
                $currentCronJobs = shell_exec("crontab -l");
                $jobToRemove = "*/4 * * * * curl https://$domainhosts/cron/croncard.php";
                $newCronJobs = preg_replace('/' . preg_quote($jobToRemove, '/') . '/', '', $currentCronJobs);
                file_put_contents('/tmp/crontab.txt', $newCronJobs);
                shell_exec('crontab /tmp/crontab.txt');
                unlink('/tmp/crontab.txt');
            } else {
                $existingCronCommands = shell_exec('crontab -l');
                $phpFilePath = escapeshellarg("https://$domainhosts/cron/croncard.php");
                $cronCommand = "*/4 * * * * curl $phpFilePath";
                if (strpos($existingCronCommands, $cronCommand) === false) {
                    $command = "(crontab -l;  echo '$cronCommand') | crontab -";
                    error_log($command);
                    shell_exec($command);
                }
            }
        }
    }
    $cronCommand = "*/4 * * * * curl https://$domainhosts/cron/croncard.php";
    if (!(function_exists('shell_exec') && is_callable('shell_exec'))) {
        $cronstatus = 1;
    } else {
        $existingCronCommands = shell_exec('crontab -l');
        if (strpos($existingCronCommands, $cronCommand) === false) {
            $cronstatus = 0;
        } else {
            $cronstatus = 1;
        }
    }
    $setting = select("setting", "*");
    $affiliates = select("affiliates", "*");
    $name_status = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['Bot_Status']];
    $NotUser_Status = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['NotUser']];
    $help_Status = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['help_Status']];
    $affiliate_Status = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$affiliates['affiliatesstatus']];
    $get_number_Status = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['get_number']];
    $statusv_verify = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['status_verify']];
    $statusv_category = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['statuscategory']];
    $status_Automatic_confirmation = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$cronstatus];
    $status_copy_cart = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['copy_cart']];
    $status_keyboard = [
        'on' => $textbotlang['Admin']['Status']['statuson'],
        'off' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['inline_keyboard']];
    $inline_keyboard = [
        [
            ['text' => $textbotlang['Admin']['Status']['statussubject'], 'callback_data' => "subjectde"],
            ['text' => $textbotlang['Admin']['Status']['subject'], 'callback_data' => "subject"],
        ],
        [
            ['text' => $name_status, 'callback_data' => "editstsuts-statusbot-{$setting['Bot_Status']}"],
            ['text' => $textbotlang['Admin']['Status']['stautsbot'], 'callback_data' => "statusbot"],
        ],
        [
            ['text' => $NotUser_Status, 'callback_data' => "editstsuts-NotUser-{$setting['NotUser']}"],
            ['text' => $textbotlang['users']['maangeuser'], 'callback_data' => "NotUser"],
        ],
        [
            ['text' => $help_Status, 'callback_data' => "editstsuts-help_Status-{$setting['help_Status']}"],
            ['text' => $textbotlang['Admin']['Help']['statushelp'], 'callback_data' => "help_Status"],
        ],
        [
            ['text' => $affiliate_Status, 'callback_data' => "editstsuts-affiliate_Status-{$affiliates['affiliatesstatus']}"],
            ['text' => $textbotlang['Admin']['affiliate']['status'], 'callback_data' => "affiliate_Status"],
        ],
        [
            ['text' => $get_number_Status, 'callback_data' => "editstsuts-get_number-{$setting['get_number']}"],
            ['text' => $textbotlang['Admin']['ManageUser']['verifynumber'], 'callback_data' => "get_number"],
        ],
        [
            ['text' => $statusv_verify, 'callback_data' => "editstsuts-verify-{$setting['status_verify']}"],
            ['text' => $textbotlang['Admin']['ManageUser']['verify'], 'callback_data' => "status_verify"],
        ],
        [
            ['text' => $statusv_category, 'callback_data' => "editstsuts-category-{$setting['statuscategory']}"],
            ['text' => $textbotlang['Admin']['category']['status'], 'callback_data' => "statuscategory"],
        ],
        [
            ['text' => $status_Automatic_confirmation, 'callback_data' => "editstsuts-Automatic_confirmation-$cronstatus"],
            ['text' => $textbotlang['Admin']['Automatic_confirmation']['title'], 'callback_data' => "Automatic_confirmation"],
        ],
        [
            ['text' => $status_copy_cart, 'callback_data' => "editstsuts-copycart-{$setting['copy_cart']}"],
            ['text' => $textbotlang['users']['moeny']['copy_cart_status'], 'callback_data' => "copycart"],
        ],
        [
            ['text' => $status_keyboard, 'callback_data' => "editstsuts-inline_keyboard-{$setting['inline_keyboard']}"],
            ['text' => "حالت شیشه ای", 'callback_data' => "inline_keyboard"],
        ]
    ];
    if ($setting['inline_keyboard'] === 'on') {
        $inline_keyboard[] = [
            ['text' => $textbotlang['Admin']['Back-Adminment'], 'callback_data' => 'back_admin']
        ];
    }
    $Bot_Status = json_encode(['inline_keyboard' => $inline_keyboard], JSON_UNESCAPED_UNICODE);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Status']['BotTitle'], $Bot_Status);
} elseif (preg_match('/verify_(\w+)/', $datain, $dataget)) {
    $iduser = $dataget[1];
    $userunverify = select("user", "*", "id", $iduser, "select");
    if ($userunverify['verify'] == "1") {
        sendmessage($from_id, $textbotlang['Admin']['ManageUser']['verifyeduser'], $backadmin, 'HTML');
        return;
    }
    update("user", "verify", "1", "id", $iduser);
    sendmessage($from_id, $textbotlang['Admin']['ManageUser']['verifyeduser'], $keyboardadmin, 'HTML');
    step('home', $from_id);
} elseif (preg_match('/verifyun_(\w+)/', $datain, $dataget)) {
    $iduser = $dataget[1];
    $userunverify = select("user", "*", "id", $iduser, "select");
    if ($userunblock['verify'] == "0") {
        sendmessage($from_id, $textbotlang['Admin']['ManageUser']['verifyed'], $backadmin, 'HTML');
        return;
    }
    update("user", "verify", "0", "id", $iduser);
    sendmessage($from_id, $textbotlang['Admin']['ManageUser']['unverifyed'], $keyboardadmin, 'HTML');
    step('home', $from_id);
} 

if ($text == $textbotlang['Admin']['category']['add'] || $datain == "add_category") {
    deletemessage($from_id, $message_id);
    sendmessage($from_id, $textbotlang['Admin']['category']['getname'], $backadmin, 'HTML');
    step("getremarkcategory", $from_id);
} elseif ($user['step'] == "getremarkcategory") {
    $category_name = trim($text);
    if ($category_name == "") {
        sendmessage($from_id, "❌ لطفاً یک نام معتبر برای دسته وارد کنید.", $backadmin, 'HTML');
        return;
    }
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM category WHERE remark = ?");
    $stmt->execute([$category_name]);
    $exists = $stmt->fetchColumn();
    if ($exists > 0) {
        sendmessage($from_id, "⚠️ این نام از قبل وجود دارد.", $backadmin, 'HTML');
        return;
    }
    $stmt = $pdo->prepare("INSERT INTO category (remark) VALUES (?)");
    $stmt->execute([$category_name]);
    sendmessage($from_id, $textbotlang['Admin']['category']['addedcategry'], $shopkeyboard, 'HTML');
    step("home", $from_id);
}

if ($text == $textbotlang['Admin']['category']['remove'] || $datain == "remove_category") {
    deletemessage($from_id, $message_id);
    sendmessage($from_id, $textbotlang['Admin']['category']['getcatgory'], KeyboardCategory(), 'HTML');
    step("removecategory", $from_id);
} elseif ($user['step'] == "removecategory") {
    deletemessage($from_id, $message_id);
    if (isset($datain) && str_starts_with($datain, 'category_')) {
        $category_id = str_replace('category_', '', $datain);
        $stmt = $pdo->prepare("DELETE FROM category WHERE id = :id");
        $stmt->bindParam(':id', $category_id, PDO::PARAM_INT);
        $stmt->execute();
        sendmessage($from_id, $textbotlang['Admin']['category']['removedcategory'], $shopkeyboard, 'HTML');
        step("home", $from_id);
    }
}

if ($text == $textbotlang['Admin']['ManageUser']['searchorder'] || $datain == "searchorder") {
    if (USER_INLINE_KEYBOARD) {
        Editmessagetext($from_id, $message_id, $textbotlang['Admin']['ManageUser']['ViewOrder'], $backadmin);
    } else {
        sendmessage($from_id, $textbotlang['Admin']['ManageUser']['ViewOrder'], $backadmin, 'HTML');
    }
    step("getidfororder", $from_id);
} elseif ($user['step'] == "getidfororder") {
    $OrderUser = select("invoice", "*", "username", $text, "select");
    if (!$OrderUser) {
        sendmessage($from_id, $textbotlang['Admin']['ManageUser']['OrderNotFound'], null, 'HTML');
        return;
    }
    $timeacc = jdate('Y/m/d H:i:s', $OrderUser['time_sell']);
    sendmessage($from_id, sprintf($textbotlang['Admin']['ManageUser']['Datails'], $OrderUser['id_invoice'], $OrderUser['Status'], $OrderUser['id_user'], $OrderUser['username'], $OrderUser['Service_location'], $OrderUser['name_product'], $OrderUser['price_product'], $OrderUser['Volume'], $OrderUser['Service_time'], $timeacc), $admin_manage_user, 'HTML');
    step('home', $from_id);  
} elseif (preg_match('/addordermanualـ(\w+)/', $datain, $dataget)) {
    $iduser = $dataget[1];
    savedata("clear", "userid", $iduser);
    sendmessage($from_id, $textbotlang['Admin']['addorder']['onestep'], $backadmin, 'HTML');
    step('getusernameconfig', $from_id);
} elseif ($user['step'] == "getusernameconfig") {
    $text = strtolower($text);
    if (!preg_match('/^\w{3,32}$/', $text)) {
        sendmessage($from_id, $textbotlang['users']['status']['Invalidusername'], $backuser, 'html');
        return;
    }
    if (in_array($text, $usernameinvoice)) {
        sendmessage($from_id, $textbotlang['Admin']['addorder']['user_exits'], null, 'HTML');
        return;
    }
    savedata("save", "username", $text);
    sendmessage($from_id, $textbotlang['Admin']['addorder']['getname_panel'], $json_list_panel, 'HTML');
    step('getnamepanelconfig', $from_id);
} elseif ($user['step'] == "getnamepanelconfig") {
    savedata("save", "name_panel", $text);
    sendmessage($from_id, $textbotlang['Admin']['addorder']['get_product'], $json_list_product, 'HTML');
    step('stependforaddorder', $from_id);
} elseif ($user['step'] == "stependforaddorder") {
    $userdata = json_decode($user['Processing_value'], true);
    $sql = "SELECT * FROM product  WHERE name_product = :name_product AND (Location = :location OR Location = '/all') LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':name_product', $text, PDO::PARAM_STR);
    $stmt->bindParam(':location', $userdata['name_panel'], PDO::PARAM_STR);
    $stmt->execute();
    $info_product = $stmt->fetch(PDO::FETCH_ASSOC);
    $panel = select("marzban_panel", "*", "name_panel", $userdata['name_panel'], "select");
    $date = time();
    $randomString = bin2hex(random_bytes(2));
    $stmt = $pdo->prepare("INSERT IGNORE INTO invoice (id_user, id_invoice, username, time_sell, Service_location, name_product, price_product, Volume, Service_time, Status) VALUES (:id_user, :id_invoice, :username, :time_sell, :Service_location, :name_product, :price_product, :Volume, :Service_time, :Status)");
    $Status = "active";
    $stmt->bindParam(':id_user', $userdata['userid'], PDO::PARAM_STR);
    $stmt->bindParam(':id_invoice', $randomString, PDO::PARAM_STR);
    $stmt->bindParam(':username', $userdata['username'], PDO::PARAM_STR);
    $stmt->bindParam(':time_sell', $date, PDO::PARAM_STR);
    $stmt->bindParam(':Service_location', $userdata['name_panel'], PDO::PARAM_STR);
    $stmt->bindParam(':name_product', $info_product['name_product'], PDO::PARAM_STR);
    $stmt->bindParam(':price_product', $info_product['price_product'], PDO::PARAM_STR);
    $stmt->bindParam(':Volume', $info_product['Volume_constraint'], PDO::PARAM_STR);
    $stmt->bindParam(':Service_time', $info_product['Service_time'], PDO::PARAM_STR);
    $stmt->bindParam(':Status', $Status, PDO::PARAM_STR);
    $stmt->execute();
    sendmessage($from_id, $textbotlang['Admin']['addorder']['added_order'], $keyboardadmin, 'HTML');
    step('home', $from_id);
}