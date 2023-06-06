<?php

namespace App\Repository;

use Exception;
use ubRouting;
use wf_JqDtHelper;

class EmailTemplate
{
    public $id;

    public $title;

    public $subject;

    public $content;

    public $brNewLine = false;

    public function __construct($data = null)
    {
        $this->load($data);
    }

    public function load($data = null)
    {
        if (is_array($data)) {

            if (!empty($data['id'])) {
                $this->id = vf(ubRouting::filters($data['id'], 'int'), 3);
            }
            if (!empty($data['title'])) {
                $this->title = vf(ubRouting::filters($data['title'], 'callback', array(
                    'strip_tags', 'mysql_real_escape_string'
                )));
            }
            if (!empty($data['subject'])) {
                $this->subject = vf(ubRouting::filters($data['subject'], 'callback', array(
                    'strip_tags', 'mysql_real_escape_string'
                )));
            }
            if (!empty($data['content'])) {
                if (is_base64($data['content'])) {
                    $data['content'] = base64_decode($data['content']);
                    if ($this->brNewLine) {
                        $data['content'] = nl2br($data['content']);
                    } else {
                        $data['content'] = str_replace("\r\n", PHP_EOL, $data['content']);
                    }
                } else {
                    if ($this->brNewLine) {
                        $data['content'] = nl2br($data['content']);
                    } else {
                        $data['content'] = str_replace("\r\n", PHP_EOL, $data['content']);
                    }
                    $data['content'] = base64_encode($data['content']);
                }
                $this->content = $data['content'];
            }
        } elseif (vf($data, 3)) {
            $this->load($this->getOne(vf($data, 3)));
        }
        return $this;
    }

    public function show($id = null, $asJson = false)
    {
        if ($id) $this->load(vf($id, 3));
        if (empty($this->id)) self::error("Template not found");

        $template = [
            'id' => $this->id,
            'title' => $this->title,
            'subject' => $this->subject,
            'content' => $this->content
        ];

        return $asJson ? self::response($template) : $template;
    }

    public function check($id = null)
    {
        if ($id) $this->load(vf($id, 3));
        if (empty($this->id)) self::error("Template not found");

        return true;
    }

    public function create()
    {
        try {
            if (!$this->subject || !$this->content) self::error('Empty template subject or body');

            $query = "INSERT INTO `email_tpl` (`title`, `subject`, `content`) 
                VALUES ('{$this->title}', '{$this->subject}', '{$this->content}')";
            nr_query($query);

            return self::response(['id' => get_last_id('email_tpl')]);
        } catch (Exception $e) {
            return false;
        }
    }

    public function update()
    {
        try {
            if (empty($this->id)) self::error("Template not found");
            if (!$this->subject || !$this->content) self::error('Empty template subject or body');

            $query = "UPDATE `email_tpl` SET 
                `title` = '{$this->title}', `subject` = '{$this->subject}', `content` = '{$this->content}' 
                WHERE `id` = $this->id";
            nr_query($query);

            return self::response(['id' => $this->id]);
        } catch (Exception $e) {
            return false;
        }
    }

    public function delete()
    {
        try {
            if (empty($this->id)) self::error("Template not found");

            $query = "DELETE FROM `email_tpl` WHERE `id` = $this->id";
            nr_query($query);

            return self::response(['id' => $this->id]);
        } catch (Exception $e) {
            return false;
        }
    }

    public function getOne($id)
    {
        $id = vf($id, 3);
        if ($id) {
            $query = "SELECT * FROM `email_tpl` WHERE `id` = $id";
            $template = simple_query($query);
        }
        return !empty($template) ? $template : [];
    }

    public function getAll()
    {
        $query = "SELECT * FROM `email_tpl`";

        return simple_queryall($query);
    }

    public function getAllHistory()
    {
        $query = "SELECT * FROM `email_logs`";

        return simple_queryall($query);
    }

    public static function getUserList($asJson = false)
    {
        $result = $where = [];
        $tag = str_replace([' '], '', vf(ubRouting::get('tag'), 3));
        $search = str_replace([' '], '', vf(ubRouting::get('query')));
        $dcmsStatus = str_replace([' '], '', vf(ubRouting::get('dcms_status'), 3));

        $query = "SELECT 
                `users`.`login` AS `login`,
                `users`.`Tariff` AS `tariff`,
                `users`.`Credit` AS `credit`,
                `users`.`Cash` AS `cash`,
                `users`.`Password` AS `password`,
                `users`.`IP` AS `ip`,
                `realname`.`realname` AS `realname`,
                `tariffs`.`Fee` AS `tariffprice`,
                `contracts`.`contract` AS `paymentid`,
                `nethosts`.`mac` AS `mac`,
                CONCAT (`city`.`cityname`, ' ', `street`.`streetname`, ' ', `build`.`buildnum`, '/', `apt`.`apt`) AS `fulladdress`,
                `phones`.`phone` AS `phone`,
                `phones`.`mobile` AS `mobile`,
                `contracts`.`contract` AS `contract`,
                `emails`.`email` AS `email`
                FROM `users`
                LEFT JOIN `nethosts` USING (`ip`)
                LEFT JOIN `realname` ON (`users`.`login`=`realname`.`login`)
                LEFT JOIN `tariffs` ON (`users`.`tariff`=`tariffs`.`name`)
                LEFT JOIN `tags` ON (`users`.`login`=`tags`.`login`)
                LEFT JOIN `address` ON (`users`.`login`=`address`.`login`)
                LEFT JOIN `apt` ON (`address`.`aptid`=`apt`.`id`)
                LEFT JOIN `build` ON (`apt`.`buildid`=`build`.`id`)
                LEFT JOIN `street` ON (`build`.`streetid`=`street`.`id`)
                LEFT JOIN `city` ON (`street`.`cityid`=`city`.`id`)
                LEFT JOIN `phones` ON (`users`.`login`=`phones`.`login`)
                LEFT JOIN `contracts` ON (`users`.`login`=`contracts`.`login`)
                LEFT JOIN `emails` ON (`users`.`login`=`emails`.`login`)
                LEFT JOIN `address_extended` ON (`users`.`login`=`address_extended`.`login`)
                LEFT JOIN `users_dcms_fundings` ON (`users`.`login`=`users_dcms_fundings`.`login`)";

        $limits = " LIMIT 1000";
        $condition = " WHERE";
        if (!empty($search)) {
            $query .= $condition . " (
                `users`.`login` LIKE '%{$search}%' OR
                `emails`.`email` LIKE '%{$search}%' OR
                `contracts`.`contract` LIKE '%{$search}%' OR
                LOWER(REPLACE(`address_extended`.`postal_code`, ' ', '')) LIKE '%" . (strtolower($search)) . "%'
            )";
            $condition = " AND";
        }
        if (!empty($tag)) {
            $limits = "";
            $query .= $condition . " `tags`.`tagid` = $tag";
            $condition = " AND";
        }
        if (!empty($dcmsStatus)) {
            $limits = "";
            $query .= $condition . " `users_dcms_fundings`.`voucher_status` = $dcmsStatus";
        }
        $query .= $limits;
        $users = simple_queryall($query);

        if (!empty($users)) {
            foreach ($users as $eachUser) {
                $eachUser['curdate'] = date('d-m-Y');
                $result[$eachUser['login']] = $eachUser;
            }
        }
        return $asJson ? self::response($result) : $result;
    }

    public static function getTemplateList($asJson = false)
    {
        $query = "SELECT * FROM `email_tpl`";
        $templates = simple_queryall($query);

        return $asJson ? self::response($templates) : $templates;
    }

    public static function getAvailableFilters($asJson = false)
    {
        try {
            $query = "SELECT * from `tagtypes`";
            $tags = simple_queryall($query);

            foreach ($tags as $io => $each) {
                $tagsFilter[$each['id']] = $each['tagname'];
            }

            $dcmsStatuses = parse_ini_file(CONFIG_PATH . 'dcms_funding.ini')['voucher_status'];
        } catch (\Exception $e) {
            return self::error("Filters not found");
        }
        $filters = [
            'tag' => $tagsFilter,
            'dcms_status' => $dcmsStatuses,
        ];

        return $asJson ? self::response($filters) : $filters;
    }

    public static function error($message)
    {
        return die(json_encode(['error' => $message]));
    }

    public static function response($data)
    {
        return die(json_encode($data));
    }

    public function renderAjaxList()
    {
        $json = new wf_JqDtHelper();
        $templates = $this->getAll();

        if (!empty($templates)) {
            foreach ($templates as $eachItem) {
                $data[] = $eachItem['id'];
                $data[] = $eachItem['title'];
                $data[] = $eachItem['content'];
                $data[] = $eachItem['subject'];

                $json->addRow($data);
                unset($data);
            }
        }
        $json->getJson();
    }

    public function renderHistoryAjaxList()
    {
        $json = new wf_JqDtHelper();
        $history = $this->getAllHistory();

        if (!empty($history)) {
            foreach ($history as $eachItem) {
                $data[] = $eachItem['id'];
                $data[] = $eachItem['status'];
                $data[] = $eachItem['from'];
                $data[] = $eachItem['to'];
                $data[] = date('d-m-Y H:i:s', $eachItem['timestamp']);
                $data[] = $eachItem['desc'];

                $json->addRow($data);
                unset($data);
            }
        }
        $json->getJson();
    }

    public function getEmailPreview($type = 'default')
    {
        $result = ['subject' => "", 'body' => ""];
        try {
            $result['subject'] = $this->subject;

            $defaultTpl = DATA_PATH . "documents/main_mails/{$type}.tpl";

            if (file_exists($defaultTpl)) {
                $result['body'] = str_ireplace('{CONTENT}', $this->content, file_get_contents($defaultTpl));
            } else {
                throw new Exception(__('Template file not found'));
            }
            return $result;
        } catch (\Exception $e) {
            return self::error($e->getMessage());
        }
    }

    public function processMailing($users, $handler, $type = 'default', $asJson = false)
    {
        $result = ['success' => [], 'warning' => []];
        try {
            if (!is_array($users)) {
                $users = array_map('trim', explode(',', $users));
            }
            if (empty($allUsers = $this->getUserList())) {
                throw new Exception(__('Empty database user list'));
            }
            foreach ($users as $eachLogin) {
                if (!isset($allUsers[$eachLogin]) || empty($allUsers[$eachLogin]['email'])) {
                    $result['warning'][] = $eachLogin;
                    continue;
                }

                $body = $this->content;
                $subject = $this->subject;
                $user = $allUsers[$eachLogin];

                foreach (self::getAvailableMacros() as $key => $value) {
                    $cleanKey = strtolower(str_replace(['{', '}'], '', $key));
                    $value = isset($user[$cleanKey]) ? $user[$cleanKey] : '';
                    $subject = str_ireplace($key, $value, $subject);
                    $body = str_ireplace($key, $value, $body);
                }

                $body = nl2br($body, false);
                $defaultTpl = DATA_PATH . "documents/main_mails/{$type}.tpl";

                if (file_exists($defaultTpl)) {
                    $body = str_ireplace('{CONTENT}', $body, file_get_contents($defaultTpl));
                }

                $handler->setRecipients([$user['email']])
                    ->setSubject($subject)
                    ->setMessage($body)
                    ->send();
                $result['success'][] = $eachLogin;
            }
            return $result;
        } catch (Exception $e) {
            return self::error($e->getMessage());
        }
    }

    public static function getAvailableTypes($asJson = false)
    {
        $types = array(
            'default' => __('Default'),
            'mobile' => __('Mobile (footer present)'),
            'fullscreen' => __('Full Screen (footer present)'),
        );
        return $asJson ? self::response($types) : $types;
    }

    public static function getAvailableMacros($asJson = false)
    {
        $supportedMacro = array(
            '{LOGIN}' => __('Login'),
            '{TARIFF}' => __('Tariff'),
            '{CREDIT}' => __('Credit'),
            '{CASH}' => __('Balance'),
            '{PASSWORD}' => __('Password'),
            '{IP}' => __('IP'),
            '{REALNAME}' => __('Real Name'),
            '{TARIFFPRICE}' => __('Tariff fee'),
            '{PAYMENTID}' => __('Payment ID'),
            '{MAC}' => __('MAC address'),
            '{FULLADDRESS}' => __('Full address'),
            '{PHONE}' => __('Phone') . ' ' . __('number'),
            '{MOBILE}' => __('Mobile') . ' ' . __('number'),
            '{CONTRACT}' => __('User contract'),
            '{EMAIL}' => __('Email'),
            '{CURDATE}' => __('Current date'),
        );
        return $asJson ? self::response($supportedMacro) : $supportedMacro;
    }
}
