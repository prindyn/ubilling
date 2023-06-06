<?php

namespace App\Repository;

use PONizer;
use ubRouting;
use App\Repository\UbHelper;
use wf_JqDtHelper;

class PonizerHelper extends UbHelper
{
    const ONU_OFF_STATUS = 'Offline';

    const ONU_OFF_STATUS_INCL = 'No Such Instance';

    protected static $oltId;

    protected static $onuMac;

    protected static $onuSigStatus;

    protected static $ponizer;

    protected static $debug = false;

    public static function api()
    {
        self::initPononu(vf(ubRouting::get('login')));
        switch (vf(ubRouting::get('action'))) {
            case 'realtimeonusig':
                self::ajaxOnuRealtimeSignalStatus();
                break;
            default:
                self::ajaxUnknownRequest();
                exit;
        }
    }

    public static function onuSignalStatusUrl($login, $oltId = '', $onuMac = '')
    {
        self::initPononu($login);
        $oltId = $oltId ? $oltId : self::$oltId;
        $onuMac = $onuMac ? $onuMac : self::$onuMac;
        return "?module=ponizer&mikrotik=true&action=realtimeonusig&mac=$onuMac&olt=$oltId&login=$login";
    }

    protected static function ajaxOnuRealtimeSignalStatus($asJson = true)
    {
        self::$onuSigStatus = 0;
        if (self::$ponizer) {
            $onuSigStatus = self::$ponizer->getONURealtimeSignal(self::$oltId, self::$onuMac, true);
            if ($onuSigStatus) {
                self::$onuSigStatus = ($onuSigStatus != self::ONU_OFF_STATUS && !ispos(strtolower($onuSigStatus), strtolower(self::ONU_OFF_STATUS_INCL)));
            }
        }
        return $asJson ? self::ajaxResponse(['code' => 200, 'status' => self::$onuSigStatus]) : self::$onuSigStatus;
    }

    protected static function initPononu($login)
    {
        if (whoami() == 'vasyl') self::$debug = true;
        $pononuData = self::getUserPononuData($login);
        self::$oltId = !empty($pononuData['oltid']) ? $pononuData['oltid'] : 'unknown';
        self::$onuMac = empty($pononuData['serial']) ? $pononuData['mac'] : $pononuData['serial'];
        self::$onuMac = self::$onuMac ? self::$onuMac : 'unknown';
        if (!self::$ponizer) self::$ponizer = new PONizer();
    }

    protected static function getUserPononuData($login)
    {
        $query = "SELECT
                    `pononu`.`id`,
                    `pononu`.`onumodelid`,
                    `pononu`.`oltid`,
                    `pononu`.`ip`,
                    `pononu`.`mac`,
                    `pononu`.`serial`,
                    `switchmodels`.`modelname` 
                FROM 
                    `pononu`
                LEFT JOIN 
                    `switchmodels`
                ON 
                    `pononu`.`onumodelid` = `switchmodels`.`id`  
                WHERE 
                    `login`='" . $login . "'";
        return simple_query($query);
    }

    public static function renderActualSignals($allOnu)
    {
        if (!wf_CheckGet(array('ajaxlist'))) {
            $columns = [__('Login'), __('IP'), __('Date'), __('Signal')];
            return wf_JqDtLoader($columns, '?module=ponizer&actualsignals=true&ajaxlist=true', false, __('Actual signals'), 50);
        }
        global $ubillingConfig;
        $billCfg = $ubillingConfig->getBilling();
        $json = new wf_JqDtHelper();
        foreach ($allOnu as $onu) {
            $historyKey = '';
            if (!$onu['login'] || !$onu['ip']) continue;
            if ($onu['mac']) {
                $historyKey = PONizer::ONUSIG_PATH . md5($onu['mac']);
            } elseif ($onu['serial']) {
                $historyKey = PONizer::ONUSIG_PATH . md5($onu['serial']);
            }
            if (!empty($historyKey)) {
                $curdate = curdate();
                $getTodayDataCmd = $billCfg['CAT'] . ' ' . $historyKey . ' | ' . $billCfg['GREP'] . ' ' . $curdate;
                $rawData = shell_exec($getTodayDataCmd);
                if ($rawData) {
                    $data = [];
                    $lastSignal = [];
                    $rawData = explodeRows($rawData);
                    foreach ($rawData as $signal) {
                        $signal = trim($signal);
                        if (!empty($signal)) $lastSignal = explode(',', $signal);
                    }
                    if (!empty($lastSignal)) {
                        $data[] = wf_Link('?module=userprofile&username=' . $onu['login'], web_profile_icon() . ' ' . $onu['login']);
                        $data[] = $onu['ip'];
                        $data[] = $lastSignal[0];
                        $data[] = $lastSignal[1];
                        $json->addRow($data);
                    }
                }
            }
        }

        $json->getJson();
    }
}
