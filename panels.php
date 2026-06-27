<?php
ini_set('error_log', 'error_log');
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api/marzban_api.php';
require_once __DIR__ . '/api/sanaei_api.php';
require_once __DIR__ . '/api/marzneshin_api.php';
require_once __DIR__ . '/api/alireza_api.php';

class ManagePanel
{
    public $name_panel;
    public $connect;

    function createUser($name_panel, $usernameC, array $Data_Config, $is_test = false) {
        $Output = [];
        global $connect;
        $name_panel = htmlspecialchars(trim($name_panel), ENT_QUOTES, 'UTF-8');
        $usernameC = htmlspecialchars(trim($usernameC), ENT_QUOTES, 'UTF-8');
        $Get_Data_Panel = select("marzban_panel", "*", "name_panel", $name_panel, "select");
        $expire = $Data_Config['expire'] ?? null;
        $data_limit = $Data_Config['data_limit'] ?? null;
        if ($Get_Data_Panel['type'] === "marzban") {
            $ConnectToPanel = adduser($usernameC, $expire, $data_limit, $Get_Data_Panel['name_panel'], $is_test);
            $data_Output = json_decode($ConnectToPanel, true);
            if (!empty($data_Output['detail'])) {
                $Output['status'] = 'Unsuccessful';
                $Output['msg'] = htmlspecialchars($data_Output['detail'], ENT_QUOTES, 'UTF-8');
            } else {
                $subscription_url = $data_Output['subscription_url'] ?? '';
                if (!preg_match('/^(https?:\/\/)?([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}(:\d+)?((\/[^\s\/]+)+)?$/', $subscription_url)) {
                    $subscription_url = rtrim($Get_Data_Panel['url_panel'], '/') . "/" . ltrim($subscription_url, "/");
                }
                $Output['status'] = 'successful';
                $Output['username'] = $data_Output['username'];
                $Output['subscription_url'] = $subscription_url;
                $Output['configs'] = $data_Output['links'];
                // ==== ذخیره لینک ====
                if (!empty($Output['username']) && !empty($subscription_url)) {
                    $stmt = $connect->prepare("UPDATE invoice SET sub_url = ? WHERE username = ?");
                    if ($stmt) {
                        $stmt->bind_param("ss", $subscription_url, $Output['username']);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            }
        } elseif ($Get_Data_Panel['type'] === "marzneshin") {
            $ConnectToPanel = adduserm($Get_Data_Panel['name_panel'], $data_limit, $usernameC, $expire);
            $data_Output = json_decode($ConnectToPanel, true);
            if (!empty($data_Output['detail'])) {
                $Output['status'] = 'Unsuccessful';
                $Output['msg'] = htmlspecialchars($data_Output['detail'], ENT_QUOTES, 'UTF-8');
            } else {
                $subscription_url = $data_Output['subscription_url'] ?? '';
                if (!preg_match('/^(https?:\/\/)?([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}(:\d+)?((\/[^\s\/]+)+)?$/', $subscription_url)) {
                    $subscription_url = $Get_Data_Panel['url_panel'] . "/" . ltrim($subscription_url, "/");
                }
                $links_user = outputlink($subscription_url);
                if (isBase64($links_user)) {
                    $links_user = base64_decode($links_user);
                }
                $links_user = array_filter(explode("\n", trim($links_user)));
                $timestamp = strtotime($data_Output['expire']);
                $data_Output['expire'] = $timestamp;
                $Output['status'] = 'successful';
                $Output['username'] = $data_Output['username'];
                $Output['subscription_url'] = $subscription_url;
                $Output['configs'] = $links_user;
            }
        } elseif ($Get_Data_Panel['type'] === "x-ui") {
            $subId = bin2hex(random_bytes(8));
            $Expireac = $expire * 1000;
            $data_Output = addClient($Get_Data_Panel['name_panel'], $usernameC, $Expireac, $data_limit, generateUUID(), "", $subId);
            $link_sub = "{$Get_Data_Panel['linksubx']}/{$subId}#$usernameC";
            $config = outputlink($link_sub);
            if (isBase64($config)) {
                $config = base64_decode($config);
            }
            $config = explode("\n", $config);
            if (!$data_Output['success']) {
                $Output['status'] = 'Unsuccessful';
                $Output['msg'] = $data_Output['msg'];
            } else {
                $Output['status'] = 'successful';
                $Output['username'] = $usernameC;
                $Output['subscription_url'] = $link_sub;
                $Output['configs'] = $config;
            }
        } elseif ($Get_Data_Panel['type'] === "alireza") {
            $subId = bin2hex(random_bytes(8));
            $Expireac = $expire * 1000;
            $data_Output = addClientalireza_singel($Get_Data_Panel['name_panel'], $usernameC, $Expireac, $data_limit, generateUUID(), "", $subId);
            if (!$data_Output['success']) {
                $Output['status'] = 'Unsuccessful';
                $Output['msg'] = $data_Output['msg'];
            } else {
                $Output['status'] = 'successful';
                $Output['username'] = $usernameC;
                $Output['subscription_url'] = "{$Get_Data_Panel['linksubx']}/{$subId}?name=$usernameC";
                $Output['configs'] = [outputlink($Output['subscription_url'])];
            }
        } else {
            $Output['status'] = 'Unsuccessful';
            $Output['msg'] = 'Panel Not Found';
        }
        return $Output;
    }

    function DataUser($name_panel, $username) {
        $Output = [];
        global $connect;
        $name_panel = htmlspecialchars(trim($name_panel), ENT_QUOTES, 'UTF-8');
        $username   = htmlspecialchars(trim($username), ENT_QUOTES, 'UTF-8');
        $Get_Data_Panel = select("marzban_panel", "*", "name_panel", $name_panel, "select");
        if (!$Get_Data_Panel) {
            return [
                'status' => 'Unsuccessful',
                'msg' => "Panel not found"
            ];
        }
        if ($Get_Data_Panel['type'] === "marzban") {
            $UsernameData = getuser($username, $Get_Data_Panel['name_panel']);
            if (!isset($UsernameData['username'])) {
                return [
                    'status' => 'Unsuccessful',
                    'msg' => $UsernameData['detail'] ?? "User not found"
                ];
            }
            // گرفتن لینک از جدول
            $subscription_url = null;
            if ($connect) {
                $stmt = $connect->prepare("SELECT sub_url FROM invoice WHERE username = ? LIMIT 1");
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $stmt->bind_result($sub_url);
                if ($stmt->fetch()) {
                    $subscription_url = $sub_url;
                }
                $stmt->close();
            }
            // اگر خالی بود، از داده پنل بگیر و در دیتابیس ذخیره کن
            if (empty($subscription_url)) {
                $subscription_url = $UsernameData['subscription_url'];
                if (!preg_match('/^(https?:\/\/)?([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}(:\d+)?((\/[^\s\/]+)+)?$/', $subscription_url)) {
                    $subscription_url = $Get_Data_Panel['url_panel'] . "/" . ltrim($subscription_url, "/");
                }
                try {
                    $stmt = $connect->prepare("UPDATE invoice SET sub_url = ? WHERE username = ?");
                    $stmt->bind_param("ss", $subscription_url, $username);
                    $stmt->execute();
                    $stmt->close();
                } catch (Exception $e) {
                    file_put_contents("error_update_suburl.txt", $e->getMessage());
                }
            }
            // اگر توی دیتابیس نبود، از داده‌های پنل استفاده کن
            if (empty($subscription_url)) {
                if (!preg_match('/^(https?:\/\/)?([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}(:\d+)?((\/[^\s\/]+)+)?$/', $subscription_url)) {
                    $subscription_url = $Get_Data_Panel['url_panel'] . "/" . ltrim($subscription_url, "/");
                }
            }
            $UsernameData['expire'] = $UsernameData['status'] == 'on_hold' ? 0 : $UsernameData['expire'];
            $Output = [
                'status' => $UsernameData['status'],
                'username' => $UsernameData['username'],
                'data_limit' => $UsernameData['data_limit'],
                'expire' => $UsernameData['expire'],
                'online_at' => $UsernameData['online_at'],
                'used_traffic' => $UsernameData['used_traffic'],
                'links' => $UsernameData['links'],
                'subscription_url' => $subscription_url
            ];
        } elseif ($Get_Data_Panel['type'] === "marzneshin") {
            $UsernameData = getuserm($username, $Get_Data_Panel['name_panel']);
            if (!empty($UsernameData['detail'])) {
                return [
                    'status' => 'Unsuccessful',
                    'msg' => htmlspecialchars($UsernameData['detail'], ENT_QUOTES, 'UTF-8')
                ];
            }
            if (!isset($UsernameData['username'])) {
                return [
                    'status' => 'Unsuccessful',
                    'msg' => ""
                ];
            }  
            $subscription_url = $UsernameData['subscription_url'] ?? '';
            if (!preg_match('/^(https?:\/\/)?([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}(:\d+)?((\/[^\s\/]+)+)?$/', $subscription_url)) {
                $subscription_url = $Get_Data_Panel['url_panel'] . "/" . ltrim($subscription_url, "/");
            }
            $UsernameData['status'] = "active";
            if (!$UsernameData['enabled']) {
                $UsernameData['status'] = "disabled";
            } elseif ($UsernameData['expire_strategy'] == "start_on_first_use") {
                $UsernameData['status'] = "on_hold";
            } elseif ($UsernameData['expired']) {
                $UsernameData['status'] = "expired";
            } elseif ($UsernameData['data_limit'] - $UsernameData['used_traffic'] <= 0) {
                $UsernameData['status'] = "limtied";
            }
            $links_user = outputlink($subscription_url);
            if (isBase64($links_user)) {
                $links_user = base64_decode($links_user);
            }
            $links_user = array_filter(explode("\n", trim($links_user)));
            $expiretime = isset($UsernameData['expire_date']) ? strtotime($UsernameData['expire_date']) : 0;
            $Output = [
                'status' => $UsernameData['status'],
                'username' => $UsernameData['username'],
                'data_limit' => $UsernameData['data_limit'],
                'expire' => $expiretime,
                'online_at' => $UsernameData['online_at'],
                'used_traffic' => $UsernameData['used_traffic'],
                'links' => $links_user,
                'subscription_url' => $subscription_url,
                'sub_updated_at' => $UsernameData['sub_updated_at'],
                'sub_last_user_agent' => $UsernameData['sub_last_user_agent'],
                'uuid' => null
            ];
        } elseif ($Get_Data_Panel['type'] == "x-ui") {
            $UsernameData = get_Client($username, $Get_Data_Panel['name_panel']);
            if ($UsernameData == null) {
                return [
                    'status' => 'Unsuccessful',
                    'msg' => "User not found"
                ];
            }
            $expire = $UsernameData['expiryTime'] / 1000;
            $UsernameData['enable'] = $UsernameData['enable'] ? 'active' : 'disabled';
            if (intval($UsernameData['expiryTime']) != 0) {
                if ($expire - time() <= 0)
                    $UsernameData['enable'] = "expired";
            }
            $linksub = "{$Get_Data_Panel['linksubx']}/{$UsernameData['subId']}#$username";
            $config = outputlink($linksub);
            if (isBase64($config)) {
                $config = base64_decode($config);
            }
            $config = explode("\n", $config);
            $UsernameData['lastOnline'] = $UsernameData['lastOnline'] == 0 ? "offline" : date('c', $UsernameData['lastOnline']);
            $Output = [
                'status' => $UsernameData['enable'],
                'username' => $UsernameData['email'],
                'data_limit' => $UsernameData['total'],
                'expire' => $UsernameData['expiryTime'] / 1000,
                'online_at' => $UsernameData['lastOnline'],
                'used_traffic' => $UsernameData['up'] + $UsernameData['down'],
                'links' => $config,
                'subscription_url' => $linksub,
            ];
        } elseif ($Get_Data_Panel['type'] == "alireza") {
            $UsernameData = get_Clientalireza($username, $Get_Data_Panel['name_panel']);
            $UsernameData2 = get_clinetsalireza($username, $Get_Data_Panel['name_panel']);
            if (!$UsernameData['id']) {
                return [
                    'status' => 'Unsuccessful',
                    'msg' => $UsernameData['msg']
                ];
            }
            if ($UsernameData['enable']) {
                $UsernameData['enable'] = "active";
            } else {
                $UsernameData['enable'] = "disabled";
            }
            $subId = $UsernameData2['subId'];
            $status_user = get_onlinecli($Get_Data_Panel['name_panel'], $username);
            $linksub = "{$Get_Data_Panel['linksubx']}/{$subId}?name=$username";
            $Output = [
                'status' => $UsernameData['enable'],
                'username' => $UsernameData['email'],
                'data_limit' => $UsernameData['total'],
                'expire' => $UsernameData['expiryTime'] / 1000,
                'online_at' => $status_user,
                'used_traffic' => $UsernameData['up'] + $UsernameData['down'],
                'links' => [outputlink($linksub)],
                'subscription_url' => $linksub,
            ];
        } else {
            $Output = [
                'status' => 'Unsuccessful',
                'msg' => 'Panel Not Found'
            ];
        }
        return $Output;
    }

    function Revoke_sub($name_panel, $username) {
        $Output = array();
        global $connect;
        $name_panel = htmlspecialchars(trim($name_panel), ENT_QUOTES, 'UTF-8');
        $username = htmlspecialchars(trim($username), ENT_QUOTES, 'UTF-8');
        $Get_Data_Panel = select("marzban_panel", "*", "name_panel", $name_panel, "select");
        if ($Get_Data_Panel['type'] == "marzban") {
            $revoke_sub = revoke_sub($username, $name_panel);
            if (isset($revoke_sub['detail']) && $revoke_sub['detail']) {
                $Output = array(
                    'status' => 'Unsuccessful',
                    'msg' => $revoke_sub['detail']
                );
            } else {
                $config = new ManagePanel();
                $Data_User = $config->DataUser($name_panel, $username);
                if (!preg_match('/^(https?:\/\/)?([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}(:\d+)?((\/[^\s\/]+)+)?$/', $Data_User['subscription_url'])) {
                    $Data_User['subscription_url'] = $Get_Data_Panel['url_panel'] . "/" . ltrim($Data_User['subscription_url'], "/");
                }
                $Output = array(
                    'status' => 'successful',
                    'configs' => $Data_User['links'],
                    'subscription_url' => $Data_User['subscription_url']
                );
                // آپدیت لینک جدید 
                if (!empty($username)) {
                    $stmt = $connect->prepare("UPDATE invoice SET sub_url = '' WHERE username = ?");
                    if ($stmt) {
                        $stmt->bind_param("s", $username);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            }
        } elseif ($Get_Data_Panel['type'] == "marzneshin") {
            $revoke_sub = revoke_subm($username, $name_panel);
            if (isset($revoke_sub['detail']) && $revoke_sub['detail']) {
                $Output = array(
                    'status' => 'Unsuccessful',
                    'msg' => $revoke_sub['detail']
                );
            } else {
                $config = new ManagePanel();
                $Data_User = $config->DataUser($name_panel, $username);
                $Data_User['links'] = [base64_decode(outputlink($Data_User['subscription_url']))];
                $Output = array(
                    'status' => 'successful',
                    'configs' => $Data_User['links'],
                    'subscription_url' => $Data_User['subscription_url']
                );
            }
        } elseif ($Get_Data_Panel['type'] == "x-ui") {
            $clients = get_Client($username, $name_panel);
            $subId = bin2hex(random_bytes(8));
            $linksub = "{$Get_Data_Panel['linksubx']}/{$subId}#$username";
            $config = array(
                'id' => intval($Get_Data_Panel['inboundid']),
                'settings' => json_encode(
                    array(
                        'clients' => array(
                            array(
                                "id" => generateUUID(),
                                "flow" => "",
                                "email" => $clients['email'],
                                "totalGB" => $clients['total'],
                                "expiryTime" => $clients['expiryTime'],
                                "enable" => true,
                                "subId" => $subId,
                            )
                        ),
                    )
                )
            );
            updateClient($Get_Data_Panel['name_panel'], $config, $clients['uuid']);
            if (!$clients) {
                $Output = array(
                    'status' => 'Unsuccessful',
                    'msg' => 'Unsuccessful'
                );
            } else {
                $Output = array(
                    'status' => 'successful',
                    'configs' => outputlink($linksub),
                    'subscription_url' => $linksub,
                );
            }
        } elseif ($Get_Data_Panel['type'] == "alireza") {
            $clients = get_clinetsalireza($username, $name_panel);
            $subId = bin2hex(random_bytes(8));
            $linksub = "{$Get_Data_Panel['linksubx']}/{$subId}/?name=$username";
            $config = array(
                'id' => intval($Get_Data_Panel['inboundid']),
                'settings' => json_encode(
                    array(
                        'clients' => array(
                            array(
                                "id" => generateUUID(),
                                "flow" => $clients['flow'],
                                "email" => $clients['email'],
                                "totalGB" => $clients['totalGB'],
                                "expiryTime" => $clients['expiryTime'],
                                "enable" => true,
                                "subId" => $subId,
                            )
                        ),
                    )
                )
            );
            $updateinbound = updateClientalireza($Get_Data_Panel['name_panel'], $username, $config);
            if (!$clients) {
                $Output = array(
                    'status' => 'Unsuccessful',
                    'msg' => 'Unsuccessful'
                );
            } else {
                $Output = array(
                    'status' => 'successful',
                    'configs' => outputlink($linksub),
                    'subscription_url' => $linksub,
                );
            }
        } else {
            $Output = array(
                'status' => 'Unsuccessful',
                'msg' => 'Panel Not Found'
            );
        }
        return $Output;
    }

    function RemoveUser($name_panel, $username) {
        $Output = array();
        global $connect;
        $Get_Data_Panel = select("marzban_panel", "*", "name_panel", $name_panel, "select");
        if ($Get_Data_Panel['type'] == "marzban") {
            $UsernameData = removeuser($Get_Data_Panel['name_panel'], $username);
            if (isset($UsernameData['detail']) && $UsernameData['detail']) {
                $Output = array(
                    'status' => 'Unsuccessful',
                    'msg' => $UsernameData['detail']
                );
            } else {
                $Output = array(
                    'status' => 'successful',
                    'username' => $username,
                );
            }
        } elseif ($Get_Data_Panel['type'] == "marzneshin") {
            $UsernameData = removeuserm($Get_Data_Panel['name_panel'], $username);
            if (isset($UsernameData['detail']) && $UsernameData['detail']) {
                $Output = array(
                    'status' => 'Unsuccessful',
                    'msg' => $UsernameData['detail']
                );
            } else {
                $Output = array(
                    'status' => 'successful',
                    'username' => $username,
                );
            }
        } elseif ($Get_Data_Panel['type'] == "x-ui") {
            $UsernameData = removeClient($Get_Data_Panel['name_panel'], $username);
            if (!$UsernameData['success']) {
                $Output = array(
                    'status' => 'Unsuccessful',
                    'msg' => $UsernameData['msg']
                );
            } else {
                $Output = array(
                    'status' => 'successful',
                    'username' => $username,
                );
            }
        }
        return $Output;
    }

    function ResetUserDataUsage($name_panel, $username) {
        $Output = array();
        global $connect;
        $Get_Data_Panel = select("marzban_panel", "*", "name_panel", $name_panel, "select");
        if ($Get_Data_Panel['type'] == "marzban") {
            ResetUserDataUsage($username, $name_panel);
        } elseif ($Get_Data_Panel['type'] == "marzneshin") {
            ResetUserDataUsagem($username, $name_panel);
        } elseif ($Get_Data_Panel['type'] == "x-ui") {
            ResetUserDataUsagex_uisin($username, $name_panel);
        } elseif ($Get_Data_Panel['type'] == "alireza") {
            ResetUserDataUsagealirezasin($username, $name_panel);
        }
    }
    
    function Modifyuser($username, $name_panel, $config = array()) {
        $Output = array();
        global $connect;
        $Get_Data_Panel = select("marzban_panel", "*", "name_panel", $name_panel, "select");
        if ($Get_Data_Panel['type'] == "marzban") {
            Modifyuser($name_panel, $username, $config);
        } elseif ($Get_Data_Panel['type'] == "marzneshin") {
            $UsernameData = getuserm($username, $Get_Data_Panel['name_panel']);
            if (!isset($config['expire_date'])) {
                $config['expire_date'] = $UsernameData['expire_date'];
            }
            $config['expire_strategy'] = $UsernameData['expire_strategy'];
            $config['username'] = $username;
            Modifyuserm($name_panel, $username, $config);
        } elseif ($Get_Data_Panel['type'] == "x-ui") {
            $clients = get_Client($username, $name_panel);
            $configs = array(
                'id' => intval($clients['inboundId']),
                'settings' => json_encode(
                    array(
                        'clients' => array(
                            array(
                                "id" => $clients['uuid'],
                                "flow" => "",
                                "email" => $clients['email'],
                                "totalGB" => $clients['total'],
                                "expiryTime" => $clients['expiryTime'],
                                "enable" => true,
                                "subId" => $clients['subId'],
                            )
                        ),
                        'decryption' => 'none',
                        'fallbacks' => array(),
                    )
                ),
            );
            $configs['settings'] = json_encode(array_replace_recursive(json_decode($configs['settings'], true), json_decode($config['settings'], true)));
            updateClient($Get_Data_Panel['name_panel'], $configs, $clients['uuid']);
        } elseif ($Get_Data_Panel['type'] == "alireza") {
            $clients = get_clinetsalireza($username, $name_panel);
            $configs = array(
                'id' => intval($Get_Data_Panel['inboundid']),
                'settings' => json_encode(
                    array(
                        'clients' => array(
                            array(
                                "id" => $clients['id'],
                                "flow" => $clients['flow'],
                                "email" => $clients['email'],
                                "totalGB" => $clients['totalGB'],
                                "expiryTime" => $clients['expiryTime'],
                                "enable" => true,
                                "subId" => $clients['subId'],
                            )
                        ),
                        'decryption' => 'none',
                        'fallbacks' => array(),
                    )
                ),
            );
            $configs['settings'] = json_encode(array_replace_recursive(json_decode($configs['settings'], true), json_decode($config['settings'], true)));
            $updateinbound = updateClientalireza($Get_Data_Panel['name_panel'], $username, $configs);
        }
    }
}