<?php

namespace App\Repository;

use ubRouting;
use App\Repository\UbHelper;
use App\Repository\PonizerHelper;

class UserHelper extends UbHelper
{
    const AUTH_REPLY_TB = 'mlg_reply';

    const AUTH_REPLY_ATTR = 'Mikrotik-Address-List';

    const AUTH_REPLY_OK = 'ALLOW';

    const UNACTIVE_TARIFFS = ['*_NO_TARIFF_*'];

    protected static $login;

    protected static $oltId;

    protected static $onuMac;

    protected static $userData;

    protected static $onuSigStatus;

    protected static $userEthStatus;

    public static function api()
    {
        self::initUser(vf(ubRouting::get('login')));
        switch (vf(ubRouting::get('action'))) {
            case 'userethstate':
                self::ajaxUserEthernetStatus();
                break;
            default:
                self::ajaxUnknownRequest();
                exit;
        }
    }

    public static function userStatePanelHtml($login)
    {
        return vue_component('
            <mk-router-state-pannel
                onu-sig-status-url="' . PonizerHelper::onuSignalStatusUrl($login) . '"
                user-eth-status-url="' . self::userEthernetStatusUrl($login) . '"
            ></mk-router-state-pannel>
        ');
    }

    public static function userEthernetStatusUrl($login = '')
    {
        $login = $login ? $login : self::$login;
        return "?module=userprofile&mikrotik=true&action=userethstate&login=$login";
    }

    protected static function ajaxUserEthernetStatus($asJson = true)
    {
        self::$userEthStatus = !empty(self::$userData);
        $result = ['code' => 200, 'status' => self::$userEthStatus];
        if (self::$userEthStatus && self::$userData['Down']) {
            $result['status'] = (self::$userEthStatus = false);
            $result['reason'] = 'is down';
        }
        if (self::$userEthStatus && self::$userData['Passive']) {
            $result['status'] = (self::$userEthStatus = false);
            $result['reason'] = 'is freezed';
        }
        if (self::$userEthStatus && !self::$userData['AlwaysOnline']) {
            $result['status'] = (self::$userEthStatus = false);
            $result['reason'] = 'has AlwaysOnline disabled';
        }
        if (self::$userEthStatus && (self::$userData['Credit'] + self::$userData['Cash']) < 0) {
            $result['status'] = (self::$userEthStatus = false);
            $result['reason'] = 'is debtor';
        }
        if (self::$userEthStatus && in_array(self::$userData['Tariff'], self::UNACTIVE_TARIFFS)) {
            $result['status'] = (self::$userEthStatus = false);
            $result['reason'] = 'has no tariff selected';
        }
        if (self::$userEthStatus && !self::radiusEthernetAccess()) {
            $result['status'] = (self::$userEthStatus = false);
            $result['reason'] = 'radius access not allow';
        }
        return $asJson ? self::ajaxResponse($result) : self::$userEthStatus;
    }

    protected static function radiusEthernetAccess()
    {
        $result = false;
        if (!empty(self::$userData['mac'])) {
            $query = "
                    SELECT
                        `username`
                    FROM
                        `" . self::AUTH_REPLY_TB . "`
                    WHERE
                        `username` = '" . self::$userData['mac'] . "'
                    AND
                        `attribute` = '" . self::AUTH_REPLY_ATTR . "'
                    AND
                        `value` = '" . self::AUTH_REPLY_OK . "'";
            $result = !empty(simple_query($query));
        }
        return $result;
    }

    protected static function initUser($login)
    {
        self::$login = $login;
        self::$userData = self::getUserData();
    }

    protected static function getUserData($login = '')
    {
        $result = array();
        $login = $login ? $login : self::$login;
        $queryWh = (!empty($login)) ? "WHERE `users`.`login` = '" . vf($login) . "'" : "";

        $query = "
            SELECT `users`.`login`, `realname`.`realname`, `Passive`, `Down`, `Password`,`AlwaysOnline`, `Tariff`, `TariffChange`, `Credit`, `Cash`,
                    `realname`.`company_name`, `realname`.`company_number`, `ip`, `mac`, `cityname`, `streetname`, `buildnum`, `entrance`, `floor`, `apt`, `geo`,";
        $query .= "
                    `phones`.`phone`,`mobile`,`contract`,`emails`.`email`,`adults`.`status` AS `adult`
                    FROM `users` LEFT JOIN `nethosts` USING (`ip`)
                    LEFT JOIN `realname` ON (`users`.`login`=`realname`.`login`)
                    LEFT JOIN `address` ON (`users`.`login`=`address`.`login`)
                    LEFT JOIN `apt` ON (`address`.`aptid`=`apt`.`id`)
                    LEFT JOIN `build` ON (`apt`.`buildid`=`build`.`id`)
                    LEFT JOIN `street` ON (`build`.`streetid`=`street`.`id`)
                    LEFT JOIN `city` ON (`street`.`cityid`=`city`.`id`)
                    LEFT JOIN `phones` ON (`users`.`login`=`phones`.`login`)
                    LEFT JOIN `contracts` ON (`users`.`login`=`contracts`.`login`)
                    LEFT JOIN `emails` ON (`users`.`login`=`emails`.`login`)
                    LEFT JOIN `adults` ON (`users`.`login`=`adults`.`login`)
                    " . $queryWh;
        $allData = (!empty($login)) ? simple_query($query) : simple_queryall($query);

        if (empty($login) and !empty($allData)) {
            foreach ($allData as $data) {
                $result[$data['login']] = $data;
            }
        } else {
            $result = $allData;
        }
        return $result;
    }
}
