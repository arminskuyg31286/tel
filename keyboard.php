<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'text.php';

$setting = select("setting", "*");
$admin_ids = select("admin", "id_admin", null, null, "FETCH_COLUMN");
$sql = "SHOW TABLES LIKE 'textbot'";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$result = $stmt->fetchAll();
$table_exists = count($result) > 0;
$datatextbot = array(
    'text_Add_Balance'        => '',
    'text_Discount'           => '',
    'text_Purchased_services' => '',
    'text_account'            => '',
    'text_bot_off'            => '',
    'text_channel'            => '',
    'text_help'               => '',
    'text_sell'               => '',
    'text_start'              => '',
    'text_support'            => '',
    'text_support_qs'         => '',
    'text_usertest'           => '',
    'text_myid'               => '',
    'text_affiliates'         => '',
    'text_linkapp'            => '',
    'text_inline_key'         => '',
    'text_BalanceBuy'         => '',
    'text_discountcode'       => '',
    'text_freetest'           => '',
    'text_info_test'          => '',
);

if ($table_exists) {
    $textdatabot = select("textbot", "*", null, null, "fetchAll");
    $data_text_bot = array();
    foreach ($textdatabot as $row) {
        $data_text_bot[] = array(
            'id_text' => $row['id_text'],
            'text'    => $row['text']
        );
    }
    foreach ($data_text_bot as $item) {
        if (isset($datatextbot[$item['id_text']])) {
            $datatextbot[$item['id_text']] = $item['text'];
        }
    }
}

// ============================================================
// کیبورد اصلی کاربر
// ============================================================
if ($setting['inline_keyboard'] === 'on') {
    $keyboard = [
        'inline_keyboard' => [
            [['text' => $datatextbot['text_sell'],               'callback_data' => 'buyserver']],
            [['text' => $datatextbot['text_usertest'],           'callback_data' => 'testserver'],
             ['text' => $datatextbot['text_Add_Balance'],        'callback_data' => 'wallet']],
            [['text' => $datatextbot['text_Purchased_services'], 'callback_data' => 'services'],
             ['text' => $datatextbot['text_account'],            'callback_data' => 'account']],
            [['text' => $datatextbot['text_myid'],               'callback_data' => 'myid'],
             ['text' => $datatextbot['text_affiliates'],         'callback_data' => 'affiliates']],
            [['text' => $datatextbot['text_linkapp'],            'callback_data' => 'linkapp']],
            [['text' => $datatextbot['text_help'],               'callback_data' => 'helpbtn'],
             ['text' => $datatextbot['text_support'],            'callback_data' => 'support']]
        ]
    ];
    if (in_array($from_id, $admin_ids)) {
        $keyboard['inline_keyboard'][] = [
            ['text' => $textbotlang['Admin']['commendadmin'], 'callback_data' => 'PANEL']
        ];
    }
    $keyboard = json_encode($keyboard, JSON_UNESCAPED_UNICODE);
} else {
    $keyboard = [
        'keyboard' => [
            [['text' => $datatextbot['text_sell']]],
            [['text' => $datatextbot['text_usertest']],           ['text' => $datatextbot['text_Add_Balance']]],
            [['text' => $datatextbot['text_Purchased_services']], ['text' => $datatextbot['text_account']]],
            [['text' => $datatextbot['text_myid']],               ['text' => $datatextbot['text_affiliates']]],
            [['text' => $datatextbot['text_linkapp']]],
            [['text' => $datatextbot['text_help']],               ['text' => $datatextbot['text_support']]],
        ],
        'resize_keyboard' => true
    ];
    if (in_array($from_id, $admin_ids)) {
        $keyboard['keyboard'][] = [
            ['text' => $textbotlang['Admin']['commendadmin']]
        ];
    }
    $keyboard = json_encode($keyboard, JSON_UNESCAPED_UNICODE);
}

// ============================================================
// کیبورد پنل کاربر (کد تخفیف)
// ============================================================
if ($setting['inline_keyboard'] === 'on') {
    $keyboardPanel = json_encode([
        'inline_keyboard' => [
            [['text' => $datatextbot['text_Discount'], 'callback_data' => "Discount"]],
            [['text' => $textbotlang['users']['backmenu'], 'callback_data' => "backuser"]]
        ]
    ], JSON_UNESCAPED_UNICODE);
} else {
    $keyboardPanel = json_encode([
        'inline_keyboard' => [
            [['text' => $datatextbot['text_Discount'], 'callback_data' => "Discount"]]
        ]
    ], JSON_UNESCAPED_UNICODE);
}

// ============================================================
// کیبورد ادمین اصلی
// ============================================================
if ($setting['inline_keyboard'] === 'on') {
    $keyboardadmin = json_encode([
        'inline_keyboard' => [
            [['text' => $textbotlang['Admin']['keyboardadmin']['bot_statistics'],   'callback_data' => 'bot_statistics']],
            [['text' => $textbotlang['Admin']['keyboardadmin']['seetingstatus'],     'callback_data' => 'seetingstatus'],
             ['text' => $textbotlang['Admin']['keyboardadmin']['settings'],          'callback_data' => 'settings']],
            [['text' => $textbotlang['Admin']['keyboardadmin']['customkey'],         'callback_data' => 'customkey']],
            [['text' => $textbotlang['Admin']['keyboardadmin']['manage_server'],     'callback_data' => 'manage_server'],
             ['text' => $textbotlang['Admin']['keyboardadmin']['shop_section'],      'callback_data' => 'shop_section']],
            [['text' => $textbotlang['Admin']['keyboardadmin']['manage_user'],       'callback_data' => 'manage_user'],
             ['text' => $textbotlang['Admin']['keyboardadmin']['admin_section'],     'callback_data' => 'admin_section']],
            [['text' => $textbotlang['Admin']['keyboardadmin']['discount_section'],  'callback_data' => 'discount_section'],
             ['text' => $textbotlang['Admin']['keyboardadmin']['all_section'],       'callback_data' => 'all_section']],
            [['text' => $textbotlang['Admin']['keyboardadmin']['test_account_settings'], 'callback_data' => 'test_account_settings'],
             ['text' => $textbotlang['Admin']['keyboardadmin']['finance'],           'callback_data' => 'finance']],
            [['text' => $textbotlang['Admin']['keyboardadmin']['affiliate_settings'],'callback_data' => 'affiliate_settings'],
             ['text' => $textbotlang['Admin']['keyboardadmin']['tutorial_section'],  'callback_data' => 'tutorial_section']],
            [['text' => $textbotlang['users']['backmenu'], 'callback_data' => 'backuser']]
        ]
    ], JSON_UNESCAPED_UNICODE);
} else {
    $keyboardadmin = json_encode([
        'keyboard' => [
            [['text' => $textbotlang['Admin']['keyboardadmin']['bot_statistics']]],
            [['text' => $textbotlang['Admin']['keyboardadmin']['seetingstatus']],
             ['text' => $textbotlang['Admin']['keyboardadmin']['settings']]],
            [['text' => $textbotlang['Admin']['keyboardadmin']['customkey']]],
            [['text' => $textbotlang['Admin']['keyboardadmin']['manage_server']],
             ['text' => $textbotlang['Admin']['keyboardadmin']['shop_section']]],
            [['text' => $textbotlang['Admin']['keyboardadmin']['manage_user']],
             ['text' => $textbotlang['Admin']['keyboardadmin']['admin_section']]],
            [['text' => $textbotlang['Admin']['keyboardadmin']['discount_section']],
             ['text' => $textbotlang['Admin']['keyboardadmin']['all_section']]],
            [['text' => $textbotlang['Admin']['keyboardadmin']['test_account_settings']],
             ['text' => $textbotlang['Admin']['keyboardadmin']['finance']]],
            [['text' => $textbotlang['Admin']['keyboardadmin']['affiliate_settings']],
             ['text' => $textbotlang['Admin']['keyboardadmin']['tutorial_section']]],
            [['text' => $textbotlang['users']['backmenu']]]
        ],
        'resize_keyboard' => true
    ], JSON_UNESCAPED_UNICODE);
}

// ============================================================
// کیبورد شخصی‌سازی (CustomKeys)
// ============================================================
if ($setting['inline_keyboard'] === 'on') {
    $CustomKeys = json_encode([
        'inline_keyboard' => [
            [['text' => $textbotlang['Admin']['keyboardadmin']['bot_text_matnkey'],  'callback_data' => 'bot_text_matnkey']],
            [['text' => $textbotlang['Admin']['keyboardadmin']['bot_text_settings'], 'callback_data' => 'bot_text_settings'],
             ['text' => $textbotlang['Admin']['keyboardadmin']['bot_text_matn'],     'callback_data' => 'bot_text_matn']],
            [['text' => $textbotlang['Admin']['Back-Adminment'], 'callback_data' => 'back_admin']]
        ]
    ], JSON_UNESCAPED_UNICODE);
} else {
    $CustomKeys = json_encode([
        'keyboard' => [
            [['text' => $textbotlang['Admin']['keyboardadmin']['bot_text_matnkey']]],
            [['text' => $textbotlang['Admin']['keyboardadmin']['bot_text_settings']],
             ['text' => $textbotlang['Admin']['keyboardadmin']['bot_text_matn']]],
            [['text' => $textbotlang['Admin']['Back-Adminment']]]
        ],
        'resize_keyboard' => true
    ], JSON_UNESCAPED_UNICODE);
}

// ============================================================
// کیبورد فروشگاه (shopkeyboard)
// ============================================================
if ($setting['inline_keyboard'] === 'on') {
    $shopkeyboard = json_encode([
        'inline_keyboard' => [
            [['text' => $textbotlang['Admin']['Product']['titlebtnedit'],     'callback_data' => 'edit_product']],
            [['text' => $textbotlang['Admin']['Product']['titlebtnremove'],   'callback_data' => 'remove_product'],
             ['text' => $textbotlang['Admin']['Product']['addproduct'],       'callback_data' => 'add_product']],
            [['text' => $textbotlang['Admin']['category']['remove'],          'callback_data' => 'remove_category'],
             ['text' => $textbotlang['Admin']['category']['add'],             'callback_data' => 'add_category']],
            [['text' => $textbotlang['Admin']['Discountsell']['remove'],      'callback_data' => 'remove_discount_sell'],
             ['text' => $textbotlang['Admin']['Discountsell']['create'],      'callback_data' => 'create_discount_sell']],
            [['text' => $textbotlang['Admin']['Discount']['titlebtnremove'],  'callback_data' => 'remove_discount'],
             ['text' => $textbotlang['Admin']['Discount']['titlebtn'],        'callback_data' => 'add_discount']],
            [['text' => $textbotlang['Admin']['Back-Adminment'], 'callback_data' => 'back_admin']]
        ]
    ], JSON_UNESCAPED_UNICODE);
} else {
    $shopkeyboard = json_encode([
        'keyboard' => [
            [['text' => $textbotlang['Admin']['Product']['titlebtnedit']]],
            [['text' => $textbotlang['Admin']['Product']['titlebtnremove']],
             ['text' => $textbotlang['Admin']['Product']['addproduct']]],
            [['text' => $textbotlang['Admin']['category']['remove']],
             ['text' => $textbotlang['Admin']['category']['add']]],
            [['text' => $textbotlang['Admin']['Discountsell']['remove']],
             ['text' => $textbotlang['Admin']['Discountsell']['create']]],
            [['text' => $textbotlang['Admin']['Discount']['titlebtnremove']],
             ['text' => $textbotlang['Admin']['Discount']['titlebtn']]],
            [['text' => $textbotlang['Admin']['Back-Adminment']]]
        ],
        'resize_keyboard' => true
    ], JSON_UNESCAPED_UNICODE);
}

// ============================================================
// کیبورد بخش ادمین‌ها
// ============================================================
if ($setting['inline_keyboard'] === 'on') {
    $admin_section_panel = json_encode([
        'inline_keyboard' => [
            [['text' => $textbotlang['Admin']['manageadmin']['showlistbtn'], 'callback_data' => 'showlist']],
            [['text' => $textbotlang['Admin']['Addedadmin'],   'callback_data' => 'addadmin'],
             ['text' => $textbotlang['Admin']['Removeedadmin'],'callback_data' => 'removeadmin']],
            [['text' => $textbotlang['Admin']['Back-Adminment'], 'callback_data' => 'back_admin']]
        ]
    ], JSON_UNESCAPED_UNICODE);
} else {
    $admin_section_panel = json_encode([
        'keyboard' => [
            [['text' => $textbotlang['Admin']['manageadmin']['showlistbtn']]],
            [['text' => $textbotlang['Admin']['Addedadmin']], ['text' => $textbotlang['Admin']['Removeedadmin']]],
            [['text' => $textbotlang['Admin']['Back-Adminment']]]
        ],
        'resize_keyboard' => true
    ], JSON_UNESCAPED_UNICODE);
}

// ============================================================
// کیبورد مدیریت سرور
// ============================================================
if ($setting['inline_keyboard'] === 'on') {
    $admin_manage_server = json_encode([
        'inline_keyboard' => [
            [['text' => $textbotlang['Admin']['keyboardadmin']['manage_panel'], 'callback_data' => 'manage_panel'],
             ['text' => $textbotlang['Admin']['keyboardadmin']['add_panel'],    'callback_data' => 'add_panel']],
            [['text' => $textbotlang['Admin']['Back-Adminment'], 'callback_data' => 'back_admin']]
        ]
    ], JSON_UNESCAPED_UNICODE);
} else {
    $admin_manage_server = json_encode([
        'keyboard' => [
            [['text' => $textbotlang['Admin']['keyboardadmin']['manage_panel']],
             ['text' => $textbotlang['Admin']['keyboardadmin']['add_panel']]],
            [['text' => $textbotlang['Admin']['Back-Adminment']]]
        ],
        'resize_keyboard' => true
    ], JSON_UNESCAPED_UNICODE);
}

// ============================================================
// کیبورد ارسال همگانی (فقط keyboard معمولی - دسترسی متنی)
// ============================================================
if ($setting['inline_keyboard'] === 'on') {
    $admin_manage_systemsms = json_encode([
        'inline_keyboard' => [
            [['text' => $textbotlang['Admin']['systemsms']['sendbulkbtn'],   'callback_data' => 'sendbulk'],
             ['text' => $textbotlang['Admin']['systemsms']['forwardbulkbtn'],'callback_data' => 'forwardbulk']],
            [['text' => $textbotlang['Admin']['Back-Adminment'], 'callback_data' => 'back_admin']]
        ]
    ], JSON_UNESCAPED_UNICODE);
} else {
    $admin_manage_systemsms = json_encode([
        'keyboard' => [
            [['text' => $textbotlang['Admin']['systemsms']['sendbulkbtn']],
             ['text' => $textbotlang['Admin']['systemsms']['forwardbulkbtn']]],
            [['text' => $textbotlang['Admin']['Back-Adminment']]]
        ],
        'resize_keyboard' => true
    ]);
}

// ============================================================
// کیبورد بخش همکار (discount_section)
// ============================================================
if ($setting['inline_keyboard'] === 'on') {
    $admin_section_discount = json_encode([
        'inline_keyboard' => [
            [['text' => $textbotlang['Admin']['ManageUser']['discountnumber'], 'callback_data' => 'discountnumber'],
             ['text' => $textbotlang['Admin']['ManageUser']['listdiscount'],   'callback_data' => 'listdiscount']],
            [['text' => $textbotlang['Admin']['Back-Adminment'], 'callback_data' => 'back_admin']]
        ]
    ], JSON_UNESCAPED_UNICODE);
} else {
    $admin_section_discount = json_encode([
        'keyboard' => [
            [['text' => $textbotlang['Admin']['ManageUser']['discountnumber']],
             ['text' => $textbotlang['Admin']['ManageUser']['listdiscount']]],
            [['text' => $textbotlang['Admin']['Back-Adminment']]]
        ],
        'resize_keyboard' => true
    ], JSON_UNESCAPED_UNICODE);
}

// ============================================================
// کیبورد مدیریت کاربران
// ============================================================
if ($setting['inline_keyboard'] === 'on') {
    $admin_manage_user = json_encode([
        'inline_keyboard' => [
            [['text' => $textbotlang['Admin']['keyboardadmin']['user_search'],    'callback_data' => 'user_search']],
            [['text' => $textbotlang['Admin']['Balance']['SendBalanceAll'],       'callback_data' => 'SendBalanceAll'],
             ['text' => $textbotlang['Admin']['systemsms']['sendmessageauser'],   'callback_data' => 'sendmessageauser']],
            [['text' => $textbotlang['Admin']['keyboardadmin']['export_phone'],   'callback_data' => 'export_phone']],
            [['text' => $textbotlang['Admin']['ManageUser']['removeorderbtn'],    'callback_data' => 'removeorderbtn'],
             ['text' => $textbotlang['Admin']['ManageUser']['searchorder'],       'callback_data' => 'searchorder']],
            [['text' => $textbotlang['Admin']['Back-Adminment'], 'callback_data' => 'back_admin']]
        ]
    ], JSON_UNESCAPED_UNICODE);
} else {
    $admin_manage_user = json_encode([
        'keyboard' => [
            [['text' => $textbotlang['Admin']['keyboardadmin']['user_search']]],
            [['text' => $textbotlang['Admin']['Balance']['SendBalanceAll']],
             ['text' => $textbotlang['Admin']['systemsms']['sendmessageauser']]],
            [['text' => $textbotlang['Admin']['keyboardadmin']['export_phone']]],
            [['text' => $textbotlang['Admin']['ManageUser']['removeorderbtn']],
             ['text' => $textbotlang['Admin']['ManageUser']['searchorder']]],
            [['text' => $textbotlang['Admin']['Back-Adminment']]]
        ],
        'resize_keyboard' => true
    ], JSON_UNESCAPED_UNICODE);
}

// ============================================================
// کیبوردهای پرداخت
// ============================================================
$CartManage = json_encode([
    'keyboard' => [
        [['text' => $textbotlang['users']['moeny']['card_number_settings']]],
        [['text' => $textbotlang['Admin']['Back-Adminment']]]
    ],
    'resize_keyboard' => true
]);

$aqayepardakht = json_encode([
    'keyboard' => [
        [['text' => $textbotlang['users']['moeny']['mr_payment_merchant_settings']]],
        [['text' => $textbotlang['Admin']['Back-Adminment']]]
    ],
    'resize_keyboard' => true
]);

$NowPaymentsManage = json_encode([
    'keyboard' => [
        [['text' => $textbotlang['users']['moeny']['nowpayment_api']]],
        [['text' => $textbotlang['Admin']['Back-Adminment']]]
    ],
    'resize_keyboard' => true
]);

// ============================================================
// کیبورد تنظیمات اکانت تست
// ============================================================
if ($setting['inline_keyboard'] === 'on') {
    $keyboard_usertest = json_encode([
        'inline_keyboard' => [
            [['text' => $textbotlang['Admin']['getlimitusertest']['setlimitallbtn'], 'callback_data' => 'setlimitall']],
            [['text' => $textbotlang['Admin']['Usertest']['settimeusertest'],        'callback_data' => 'settimeusertest'],
             ['text' => $textbotlang['Admin']['Usertest']['setvolumeusertest'],      'callback_data' => 'setvolumeusertest']],
            [['text' => $textbotlang['Admin']['Back-Adminment'], 'callback_data' => 'back_admin']]
        ]
    ], JSON_UNESCAPED_UNICODE);
} else {
    $keyboard_usertest = json_encode([
        'keyboard' => [
            [['text' => $textbotlang['Admin']['getlimitusertest']['setlimitallbtn']]],
            [['text' => $textbotlang['Admin']['Usertest']['settimeusertest']],
             ['text' => $textbotlang['Admin']['Usertest']['setvolumeusertest']]],
            [['text' => $textbotlang['Admin']['Back-Adminment']]]
        ],
        'resize_keyboard' => true
    ]);
}

// ============================================================
// کیبورد تنظیمات (settings_panel)
// ============================================================
if ($setting['inline_keyboard'] === 'on') {
    $setting_panel = json_encode([
        'inline_keyboard' => [
            [['text' => $textbotlang['Admin']['keyboardadmin']['settingscron'],       'callback_data' => 'settingscron']],
            [['text' => $textbotlang['Admin']['channel']['channelreport'],            'callback_data' => 'channelreport'],
             ['text' => $textbotlang['Admin']['channel']['changechannelbtn'],         'callback_data' => 'changechannelbtn']],
            [['text' => $textbotlang['Admin']['Back-Adminment'], 'callback_data' => 'back_admin']]
        ]
    ], JSON_UNESCAPED_UNICODE);
} else {
    $setting_panel = json_encode([
        'keyboard' => [
            [['text' => $textbotlang['Admin']['keyboardadmin']['settingscron']]],
            [['text' => $textbotlang['Admin']['channel']['channelreport']],
             ['text' => $textbotlang['Admin']['channel']['changechannelbtn']]],
            [['text' => $textbotlang['Admin']['Back-Adminment']]]
        ],
        'resize_keyboard' => true
    ], JSON_UNESCAPED_UNICODE);
}

// ============================================================
// درگاه‌های پرداخت کاربر (step_payment)
// ============================================================
$PaySettingcard         = select("PaySetting", "ValuePay", "NamePay", 'Cartstatus',         "select")['ValuePay'];
$PaySettingnow          = select("PaySetting", "ValuePay", "NamePay", 'nowpaymentstatus',   "select")['ValuePay'];
$PaySettingaqayepardakht= select("PaySetting", "ValuePay", "NamePay", 'statusaqayepardakht',"select")['ValuePay'];

$step_payment = ['inline_keyboard' => []];
if ($PaySettingcard === "oncard") {
    $step_payment['inline_keyboard'][] = [
        ['text' => $textbotlang['users']['moeny']['cart_to_Cart_btn'], 'callback_data' => "cart_to_offline"],
    ];
}
if ($PaySettingnow === "onnowpayment") {
    $step_payment['inline_keyboard'][] = [
        ['text' => $textbotlang['users']['moeny']['nowpaymentbtn'], 'callback_data' => "nowpayments"]
    ];
}
if ($PaySettingaqayepardakht === "onaqayepardakht") {
    $step_payment['inline_keyboard'][] = [
        ['text' => $textbotlang['users']['moeny']['mr_payment_gateway'], 'callback_data' => "aqayepardakht"]
    ];
}
$step_payment['inline_keyboard'][] = [
    ['text' => $textbotlang['users']['closelist'], 'callback_data' => "closelist"]
];
$step_payment = json_encode($step_payment);

// ============================================================
// کیبورد آموزش ادمین
// ============================================================
if ($setting['inline_keyboard'] === 'on') {
    $keyboardhelpadmin = json_encode([
        'inline_keyboard' => [
            [['text' => $textbotlang['Admin']['Help']['edithelp'],      'callback_data' => 'edithelp']],
            [['text' => $textbotlang['Admin']['Help']['addhelp'],       'callback_data' => 'addhelp'],
             ['text' => $textbotlang['Admin']['Help']['removehelpbtn'], 'callback_data' => 'removehelpbtn']],
            [['text' => $textbotlang['Admin']['Back-Adminment'], 'callback_data' => 'back_admin']]
        ]
    ], JSON_UNESCAPED_UNICODE);
} else {
    $keyboardhelpadmin = json_encode([
        'keyboard' => [
            [['text' => $textbotlang['Admin']['Help']['edithelp']]],
            [['text' => $textbotlang['Admin']['Help']['addhelp']],
             ['text' => $textbotlang['Admin']['Help']['removehelpbtn']]],
            [['text' => $textbotlang['Admin']['Back-Adminment']]]
        ],
        'resize_keyboard' => true
    ]);
}

// ============================================================
// کیبورد ارسال شماره
// ============================================================
$request_contact = json_encode([
    'keyboard' => [
        [['text' => $textbotlang['users']['sendnumber'], 'request_contact' => true]],
        [['text' => $textbotlang['users']['backtominmenu']]]
    ],
    'resize_keyboard' => true
]);

// ============================================================
// دکمه بازگشت کاربر
// ============================================================
if ($setting['inline_keyboard'] === 'on') {
    $backuser = json_encode([
        'inline_keyboard' => [
            [['text' => $textbotlang['users']['backmenu'], 'callback_data' => "backuser"]]
        ]
    ], JSON_UNESCAPED_UNICODE);
} else {
    $backuser = json_encode([
        'keyboard' => [
            [['text' => $textbotlang['users']['backmenu']]]
        ],
        'resize_keyboard' => true,
    ], JSON_UNESCAPED_UNICODE);
}

// ============================================================
// دکمه بازگشت ادمین
// ============================================================
if ($setting['inline_keyboard'] === 'on') {
    $backadmin = json_encode([
        'inline_keyboard' => [
            [['text' => $textbotlang['Admin']['Back-Adminment'], 'callback_data' => "back_admin"]]
        ]
    ], JSON_UNESCAPED_UNICODE);
} else {
    $backadmin = json_encode([
        'keyboard' => [
            [['text' => $textbotlang['Admin']['Back-Adminment']]]
        ],
        'resize_keyboard' => true,
    ], JSON_UNESCAPED_UNICODE);
}

// ============================================================
// لیست پنل‌ها
// ============================================================
$stmt = $pdo->prepare("SHOW TABLES LIKE 'marzban_panel'");
$stmt->execute();
$table_exists = $stmt->fetch();
if ($table_exists) {
    $json_list_panel = buildListPanelKeyboard($pdo, $textbotlang);
}

$stmt = $pdo->prepare("SHOW TABLES LIKE 'marzban_panel'");
$stmt->execute();
$result = $stmt->fetchAll();
$table_exists = count($result) > 0;
$namepanel = [];
if ($table_exists) {
    $stmt = $pdo->prepare("SELECT * FROM marzban_panel");
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $namepanel[] = $row['name_panel'];
    }
    $inline_keyboard = [];
    foreach ($namepanel as $panel_name) {
        $inline_keyboard[] = [
            ['text' => $panel_name, 'callback_data' => $panel_name]
        ];
    }
    $inline_keyboard[] = [
        ['text' => $textbotlang['Admin']['Back-Adminment'], 'callback_data' => 'back_admin']
    ];
    $json_list_pelan = json_encode(['inline_keyboard' => $inline_keyboard], JSON_UNESCAPED_UNICODE);
}

// ============================================================
// لیست محصولات
// ============================================================
$stmt = $pdo->prepare("SHOW TABLES LIKE 'product'");
$stmt->execute();
$result = $stmt->fetchAll();
$table_exists = count($result) > 0;
$product = [];
if ($table_exists) {
    $stmt = $pdo->prepare("SELECT * FROM product WHERE Location = :Location OR Location = '/all'");
    $stmt->bindParam(':Location', $text, PDO::PARAM_STR);
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $product[] = $row['name_product'];
    }
    $inline_keyboard = [];
    foreach ($product as $panel_name) {
        $inline_keyboard[] = [
            ['text' => $panel_name, 'callback_data' => $panel_name]
        ];
    }
    $inline_keyboard[] = [
        ['text' => $textbotlang['Admin']['Back-Adminment'], 'callback_data' => 'back_admin']
    ];
    $json_list_product = json_encode(['inline_keyboard' => $inline_keyboard], JSON_UNESCAPED_UNICODE);
}

// ============================================================
// لیست آموزش‌ها
// ============================================================
$sql = "SHOW TABLES LIKE 'help'";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$result = $stmt->fetchAll();
$table_exists = count($result) > 0;
$json_list_help    = null;
$json_list_helpkey = null;
$help_buttons      = [];

if ($table_exists) {
    $stmt = $pdo->prepare("SELECT * FROM help");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $row_buttons  = [];
    $row_keyboard = [];
    $count = 0;
    foreach ($rows as $row) {
        $row_buttons[]  = ['text' => $row['name_os'], 'callback_data' => "help_" . $row['name_os']];
        $row_keyboard[] = [['text' => $row['name_os']]];
        $count++;
        if ($count % 2 == 0) {
            $help_buttons[] = $row_buttons;
            $row_buttons = [];
        }
    }
    if (!empty($row_buttons)) {
        $help_buttons[] = $row_buttons;
    }

    // keyboard معمولی برای مدیریت آموزش
    $help_arr = [
        'keyboard'        => $row_keyboard,
        'resize_keyboard' => true
    ];
    $help_arr['keyboard'][] = [['text' => $textbotlang['Admin']['Back-Adminment']]];
    $json_list_help = json_encode($help_arr, JSON_UNESCAPED_UNICODE);

    // inline keyboard برای مدیریت آموزش (ویرایش)
    $help_inline = [];
    foreach ($rows as $row) {
        $help_inline[] = [
            ['text' => $row['name_os'], 'callback_data' => 'helpedit_' . $row['name_os']]
        ];
    }
    $help_inline[] = [['text' => $textbotlang['Admin']['Back-Adminment'], 'callback_data' => 'back_admin']];
    $json_list_helpkey = json_encode(['inline_keyboard' => $help_inline], JSON_UNESCAPED_UNICODE);

    if ($setting['inline_keyboard'] === 'on') {
        $help_buttons[] = [['text' => $textbotlang['users']['backmenu'], 'callback_data' => "backuser"]];
    }
} else {
    $help_buttons[]    = [['text' => 'هیچ آموزش موجود نیست', 'callback_data' => 'none']];
    $json_list_helpkey = json_encode([
        'inline_keyboard' => [
            [['text' => 'هیچ آموزش موجود نیست', 'callback_data' => 'none']],
            [['text' => $textbotlang['Admin']['Back-Adminment'], 'callback_data' => 'back_admin']]
        ]
    ], JSON_UNESCAPED_UNICODE);
}
$json_inline_help = json_encode(['inline_keyboard' => $help_buttons], JSON_UNESCAPED_UNICODE);

// ============================================================
// اطلاعات کاربر
// ============================================================
$users = select("user", "*", "id", $from_id, "select");
if ($users == false) {
    $users = ['step' => ''];
}

// ============================================================
// لیست پنل‌های فعال برای کاربران
// ============================================================
$stmt = $pdo->prepare("SELECT * FROM marzban_panel WHERE status = 'activepanel'");
$stmt->execute();
$list_marzban_panel_users = ['inline_keyboard' => []];
while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $list_marzban_panel_users['inline_keyboard'][] = [
        ['text' => $result['name_panel'], 'callback_data' => "location_{$result['id']}"]
    ];
}
$list_marzban_panel_user = json_encode($list_marzban_panel_users);

// ============================================================
// لیست پنل‌های تست
// ============================================================
$list_marzban_panel_usertest = ['inline_keyboard' => []];
$stmt = $pdo->prepare("SELECT * FROM marzban_panel WHERE statusTest = 'ontestshowpanel'");
$stmt->execute();
while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $list_marzban_panel_usertest['inline_keyboard'][] = [
        ['text' => $result['name_panel'], 'callback_data' => "tozihat_{$result['id']}"]
    ];
}
if ($setting['inline_keyboard'] === 'on') {
    $list_marzban_panel_usertest['inline_keyboard'][] = [
        ['text' => $textbotlang['users']['backmenu'], 'callback_data' => "backuser"]
    ];
}
$list_marzban_usertest = json_encode($list_marzban_panel_usertest, JSON_UNESCAPED_UNICODE);

// ============================================================
// لیست کدهای تخفیف
// ============================================================
$sql = "SHOW TABLES LIKE 'Discount'";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$result = $stmt->fetchAll();
$table_exists = count($result) > 0;
if ($table_exists) {
    $Discount = [];
    $stmt = $pdo->prepare("SELECT * FROM Discount");
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $Discount[] = [$row['code']];
    }
    $list_Discount = [
        'keyboard'        => [],
        'resize_keyboard' => true,
    ];
    $list_Discount['keyboard'][] = [['text' => $textbotlang['Admin']['Back-Adminment']]];
    foreach ($Discount as $button) {
        $list_Discount['keyboard'][] = [['text' => $button[0]]];
    }
    $json_list_Discount_list_admin = json_encode($list_Discount);
}

// ============================================================
// کیبورد پرداخت خرید
// ============================================================
$payment = json_encode([
    'inline_keyboard' => [
        [['text' => $textbotlang['users']['buy']['payandGet'], 'callback_data' => "confirmandgetservice"]],
        [['text' => $textbotlang['users']['buy']['discount'],  'callback_data' => "aptdc"]],
        [['text' => $textbotlang['users']['backmenu'],         'callback_data' => "backtopelan"]]
    ]
]);

// ============================================================
// کیبورد ویرایش محصول
// ============================================================
$change_product = json_encode([
    'inline_keyboard' => [
        [['text' => $textbotlang['Admin']['Product']['editname'],     'callback_data' => 'editname']],
        [['text' => $textbotlang['Admin']['Product']['editcategory'], 'callback_data' => 'editcategory'],
         ['text' => $textbotlang['Admin']['Product']['editprice'],    'callback_data' => 'editprice']],
        [['text' => $textbotlang['Admin']['Product']['editvolume'],   'callback_data' => 'editvolume'],
         ['text' => $textbotlang['Admin']['Product']['edittime'],     'callback_data' => 'edittime']],
        [['text' => $textbotlang['Admin']['Back-Adminment'], 'callback_data' => 'back_admin']]
    ]
], JSON_UNESCAPED_UNICODE);

// ============================================================
// کیبورد روش نام‌گذاری
// ============================================================
$MethodUsername = json_encode([
    'keyboard' => [
        [['text' => $textbotlang['users']['customtextandrandom']]],
        [['text' => $textbotlang['users']['customusernameorder']]],
        [['text' => $textbotlang['users']['customusernameandorder']]],
        [['text' => $textbotlang['users']['customidAndRandom']]],
        [['text' => $textbotlang['Admin']['Back-Adminment']]]
    ],
    'resize_keyboard' => true
]);

// ============================================================
// کیبورد مرزبان (optionMarzban) - با دکمه methodusername اضافه شد
// ============================================================
$optionMarzban = json_encode([
    'inline_keyboard' => [
        [['text' => $textbotlang['Admin']['managepanel']['keyboardpanel']['namepanel'],   'callback_data' => 'namepanel']],
        [['text' => $textbotlang['Admin']['managepanel']['keyboardpanel']['editurl'],     'callback_data' => 'editurl']],
        [['text' => $textbotlang['Admin']['managepanel']['keyboardpanel']['editusername'],'callback_data' => 'editusername']],
        [['text' => $textbotlang['Admin']['managepanel']['keyboardpanel']['editpassword'],'callback_data' => 'editpassword']],
        [['text' => $textbotlang['Admin']['managepanel']['setinbound'],                   'callback_data' => 'setinbound']],
        [['text' => $textbotlang['Admin']['managepanel']['methodusername'],               'callback_data' => 'methodusername']],
        [['text' => $textbotlang['Admin']['managepanel']['keyboardpanel']['on_hold_status'],'callback_data' => 'on_hold_status']],
        [['text' => $textbotlang['Admin']['managepanel']['sublinkstatus'],                'callback_data' => 'sublinkstatus']],
        [['text' => $textbotlang['Admin']['managepanel']['configstatus'],                 'callback_data' => 'configstatus']],
        [['text' => "🔄 ری‌استارت هسته",                                                 'callback_data' => 'restartcore']],
        [['text' => $textbotlang['Admin']['Back-Adminment'], 'callback_data' => 'back_admin']]
    ]
], JSON_UNESCAPED_UNICODE);

// ============================================================
// کیبورد مرزنشین - با methodusername
// ============================================================
$optionMarzneshin = json_encode([
    'inline_keyboard' => [
        [['text' => $textbotlang['Admin']['managepanel']['keyboardpanel']['namepanel'],   'callback_data' => 'namepanel']],
        [['text' => $textbotlang['Admin']['managepanel']['keyboardpanel']['editurl'],     'callback_data' => 'editurl']],
        [['text' => $textbotlang['Admin']['managepanel']['keyboardpanel']['editusername'],'callback_data' => 'editusername']],
        [['text' => $textbotlang['Admin']['managepanel']['keyboardpanel']['editpassword'],'callback_data' => 'editpassword']],
        [['text' => $textbotlang['users']['stateus']['manageService'],                   'callback_data' => 'manageService']],
        [['text' => $textbotlang['Admin']['managepanel']['methodusername'],               'callback_data' => 'methodusername']],
        [['text' => $textbotlang['Admin']['managepanel']['keyboardpanel']['on_hold_status'],'callback_data' => 'on_hold_status']],
        [['text' => $textbotlang['Admin']['managepanel']['sublinkstatus'],                'callback_data' => 'sublinkstatus']],
        [['text' => $textbotlang['Admin']['managepanel']['configstatus'],                 'callback_data' => 'configstatus']],
        [['text' => $textbotlang['Admin']['Back-Adminment'], 'callback_data' => 'back_admin']]
    ]
], JSON_UNESCAPED_UNICODE);

// ============================================================
// کیبورد x-ui (ثنایی) - با methodusername
// ============================================================
$optionX_ui_single = json_encode([
    'inline_keyboard' => [
        [['text' => $textbotlang['Admin']['managepanel']['keyboardpanel']['namepanel'],   'callback_data' => 'namepanel']],
        [['text' => $textbotlang['Admin']['managepanel']['keyboardpanel']['editurl'],     'callback_data' => 'editurl']],
        [['text' => $textbotlang['Admin']['managepanel']['keyboardpanel']['editusername'],'callback_data' => 'editusername']],
        [['text' => $textbotlang['Admin']['managepanel']['keyboardpanel']['editpassword'],'callback_data' => 'editpassword']],
        [['text' => $textbotlang['Admin']['managepanel']['keyboardpanel']['editinound'],  'callback_data' => 'setinbound']],
        [['text' => $textbotlang['Admin']['managepanel']['methodusername'],               'callback_data' => 'methodusername']],
        [['text' => $textbotlang['Admin']['managepanel']['keyboardpanel']['linksub'],     'callback_data' => 'linksub']],
        [['text' => $textbotlang['Admin']['managepanel']['sublinkstatus'],                'callback_data' => 'sublinkstatus']],
        [['text' => $textbotlang['Admin']['managepanel']['configstatus'],                 'callback_data' => 'configstatus']],
        [['text' => $textbotlang['Admin']['Back-Adminment'], 'callback_data' => 'back_admin']]
    ]
], JSON_UNESCAPED_UNICODE);

// ============================================================
// کیبورد زیرمجموعه
// ============================================================
$affiliates = json_encode([
    'keyboard' => [
        [['text' => $textbotlang['Admin']['affiliate']['Percentageset']]],
        [['text' => $textbotlang['Admin']['affiliate']['porsantafterbuy']],
         ['text' => $textbotlang['Admin']['affiliate']['giftstart']]],
        [['text' => $textbotlang['Admin']['Back-Adminment']]]
    ],
    'resize_keyboard' => true
]);

// ============================================================
// کیبورد نوع پنل
// ============================================================
$typepanel = json_encode([
    'inline_keyboard' => [
        [['text' => $textbotlang['Admin']['managepanel']['type']['marzban'],    'callback_data' => "typepanel%marzban"],
         ['text' => $textbotlang['Admin']['managepanel']['type']['3x-ui'],      'callback_data' => "typepanel%x-ui"]],
        [['text' => $textbotlang['Admin']['managepanel']['type']['marzneshin'], 'callback_data' => "typepanel%marzneshin"],
         ['text' => $textbotlang['Admin']['managepanel']['type']['alireza'],    'callback_data' => "typepanel%alireza"]],
        [['text' => $textbotlang['Admin']['Back-Adminment'], 'callback_data' => "back_admin"]]
    ],
]);

// ============================================================
// کیبورد کرون جاب
// ============================================================
if ($setting['inline_keyboard'] === 'on') {
    $keyboardcronjob = json_encode([
        'inline_keyboard' => [
            [['text' => $textbotlang['Admin']['cron']['test']['active'],    'callback_data' => 'test_active'],
             ['text' => $textbotlang['Admin']['cron']['test']['disable'],   'callback_data' => 'test_disable']],
            [['text' => $textbotlang['Admin']['cron']['volume']['active'],  'callback_data' => 'volume_active'],
             ['text' => $textbotlang['Admin']['cron']['volume']['disable'], 'callback_data' => 'volume_disable']],
            [['text' => $textbotlang['Admin']['cron']['time']['active'],    'callback_data' => 'time_active'],
             ['text' => $textbotlang['Admin']['cron']['time']['disable'],   'callback_data' => 'time_disable']],
            [['text' => $textbotlang['Admin']['cron']['remove']['active'],  'callback_data' => 'remove_active'],
             ['text' => $textbotlang['Admin']['cron']['remove']['disable'], 'callback_data' => 'remove_disable']],
            [['text' => $textbotlang['Admin']['cron']['remove']['timeset'], 'callback_data' => 'timeset']],
            [['text' => $textbotlang['Admin']['Back-Adminment'], 'callback_data' => 'back_admin']]
        ]
    ], JSON_UNESCAPED_UNICODE);
} else {
    $keyboardcronjob = json_encode([
        'keyboard' => [
            [['text' => $textbotlang['Admin']['cron']['test']['active']],
             ['text' => $textbotlang['Admin']['cron']['test']['disable']]],
            [['text' => $textbotlang['Admin']['cron']['volume']['active']],
             ['text' => $textbotlang['Admin']['cron']['volume']['disable']]],
            [['text' => $textbotlang['Admin']['cron']['time']['active']],
             ['text' => $textbotlang['Admin']['cron']['time']['disable']]],
            [['text' => $textbotlang['Admin']['cron']['remove']['active']],
             ['text' => $textbotlang['Admin']['cron']['remove']['disable']]],
            [['text' => $textbotlang['Admin']['cron']['remove']['timeset']]],
            [['text' => $textbotlang['Admin']['Back-Adminment']]]
        ],
        'resize_keyboard' => true
    ], JSON_UNESCAPED_UNICODE);
}

// ============================================================
// کیبورد ویرایش آموزش
// ============================================================
if ($setting['inline_keyboard'] === 'on') {
    $helpedit = json_encode([
        'inline_keyboard' => [
            [['text' => $textbotlang['Admin']['Help']['change']['name'],      'callback_data' => 'changehelpname'],
             ['text' => $textbotlang['Admin']['Help']['change']['dec'],       'callback_data' => 'changehelpdec']],
            [['text' => $textbotlang['Admin']['Help']['change']['editmedia'], 'callback_data' => 'changehelpmedia']],
            [['text' => $textbotlang['Admin']['Back-Adminment'], 'callback_data' => 'back_admin']]
        ]
    ], JSON_UNESCAPED_UNICODE);
} else {
    $helpedit = json_encode([
        'keyboard' => [
            [['text' => $textbotlang['Admin']['Help']['change']['name']],
             ['text' => $textbotlang['Admin']['Help']['change']['dec']]],
            [['text' => $textbotlang['Admin']['Help']['change']['editmedia']]],
            [['text' => $textbotlang['Admin']['Back-Adminment']]]
        ],
        'resize_keyboard' => true
    ]);
}

// ============================================================
// توابع کیبورد پویا
// ============================================================
function KeyboardCategory() {
    global $pdo, $textbotlang;
    $stmt = $pdo->prepare("SELECT * FROM category");
    $stmt->execute();
    $list_category = ['inline_keyboard' => []];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $list_category['inline_keyboard'][] = [
            ['text' => $row['remark'], 'callback_data' => 'category_' . $row['id']]
        ];
    }
    $list_category['inline_keyboard'][] = [
        ['text' => $textbotlang['Admin']['Back-Adminment'], 'callback_data' => 'back_admin']
    ];
    return json_encode($list_category);
}

function KeyboardCategorybuy($callback_data, $location) {
    global $pdo, $textbotlang;
    alert("📍در حال دریافت دسته بندی ها", false);
    $stmt = $pdo->prepare("SELECT * FROM category");
    $stmt->execute();
    $list_category = ['inline_keyboard' => []];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $stmts = $pdo->prepare("SELECT * FROM product WHERE (Location = :location OR Location = '/all') AND category = :category");
        $stmts->bindParam(':location', $location, PDO::PARAM_STR);
        $stmts->bindParam(':category', $row['id'], PDO::PARAM_STR);
        $stmts->execute();
        if ($stmts->rowCount() == 0) continue;
        $list_category['inline_keyboard'][] = [
            ['text' => $row['remark'], 'callback_data' => "categorylist_" . $row['id']]
        ];
    }
    $list_category['inline_keyboard'][] = [
        ['text' => $textbotlang['users']['backmenu'], "callback_data" => $callback_data]
    ];
    return json_encode($list_category);
}

function KeyboardProduct($location, $backdata, $MethodUsername, $categoryid = null) {
    global $pdo, $textbotlang;
    alert("📍در حال دریافت لیست پلن ها", false);
    $user = select("user", "*", "id", $GLOBALS['from_id'], "select");
    $discount = intval($user['discount_number'] ?? 0);
    if ($discount < 0)   $discount = 0;
    if ($discount > 100) $discount = 100;
    $query = "SELECT * FROM product WHERE (Location = :location OR Location = '/all') ";
    if ($categoryid != null) {
        $query .= "AND category = '$categoryid'";
    }
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':location', $location, PDO::PARAM_STR);
    $stmt->execute();
    $product = ['inline_keyboard' => []];
    while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $final_price = $result['price_product'] * (100 - $discount) / 100;
        $price_text  = ($final_price == 0) ? "رایگان" : number_format($final_price) . " تومان";
        $product['inline_keyboard'][] = [
            ['text' => $result['name_product'] . " - " . $price_text, 'callback_data' => "prodcutservice_{$result['code_product']}"]
        ];
    }
    $product['inline_keyboard'][] = [
        ['text' => $textbotlang['users']['backmenu'], 'callback_data' => $backdata]
    ];
    return json_encode($product);
}
