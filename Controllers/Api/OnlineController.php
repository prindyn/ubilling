<?php

namespace App\Controllers\Api;

use PONizer;
use App\Models\User;
use App\Filters\OnlineFilter;
use App\Controllers\AbstractController;
use App\Models\PonOnu;
use App\Models\TagType;

class OnlineController extends AbstractController
{
    public function getSummary()
    {
        global $ubillingConfig;
        ini_set('memory_limit', '512M');

        $users = User::with([
            'tags',
            'email',
            'tariff',
            'tariff.speed',
            'phoneTariff',
            'nethost',
            'contract',
            'property',
            'gcssMandate',
            'realname',
            'virtualServices',
            'phone',
            'dcmsFunding',
            'contractDate',
            'startDate',
            'addressExtended',
            'pononu',
            'pononu.olt',
            'pononu.olt.model',
        ])->filter(new OnlineFilter)->get();

        $usersFieldsConf = cf_ExtraFieldsConfsGetAll();

        $tagTypes = TagType::all(['id', 'tagname']);
        $discountCfType = $ubillingConfig->getAlterParam('DISCOUNT_PERCENT_CFID');

        $users = $users->transform(function ($item) use ($tagTypes, $usersFieldsConf, $discountCfType) {
            $vatK = 1.2;
            $vat = ((100 - 20) / 100);
            $discount = null;
            $discountExp = null;
            $tariffPriceExVat = '';
            $vServiceName = '';
            $vServicePrice = '';
            $vServicePriceExVat = '';
            $phoneTariffPriceExVat = '';
            if ($item->tariff) {
                $speedd = $item->tariff->speed->speeddown / 1000 . " Mb";
                $speedu = $item->tariff->speed->speedup / 1000 . " Mb";
                $tariffPriceExVat = round(($item->tariff->Fee / $vatK), 2);
            }
            if ($item->phoneTariff) {
                $phoneTariffPriceExVat = round(($item->phoneTariff->price / $vatK), 2);
            }
            if ($item->virtualServices->count()) {
                foreach ($item->virtualServices as $vservice) {
                    if ($vservice->cashtype == 'stargazer') {
                        $vServicePrice = $vservice->price;
                        $vServiceName = @$tagTypes->where('id', $vservice->tagid)->first()->tagname;
                        $vServicePriceExVat = round(($vservice->price / $vatK), 2);
                    }
                }
            }

            $curFieldsConf = isset($usersFieldsConf[$item['login']]) ? $usersFieldsConf[$item['login']] : [];
            if (!empty($curFieldsConf)) {
                if ($discountCfType && isset($curFieldsConf[$discountCfType])) {
                    $discountExp = @$curFieldsConf[$discountCfType]['expiredate'];
                    $discount = cf_FieldGet($item['login'], $discountCfType);
                }
            }
            
            $result = [
                'olt' => '',
                'onu_iface' => '',
                'onu_ip' => $item->pononu ? $item->pononu->ip : '',
                'onu_mac' => $item->pononu ? $item->pononu->mac : '',
                'onu_serial' => $item->pononu ? $item->pononu->serial : '',
                'login' => $item->login,
                'password' => $item->Password,
                'uprn' => $item->property ? $item->property->uprn : '',
                'email' => $item->email ? $item->email->email : '',
                'ip' => $item->nethost ? $item->nethost->ip : '',
                'mac' => $item->nethost ? $item->nethost->mac : '',
                'mobile' => $item->phone ? $item->phone->mobile : '',
                'phone' => $item->phone ? $item->phone->phone : '',
                'company' => $item->realname ? $item->realname->company_name : '',
                'realname' => $item->realname ? $item->realname->realname : '',
                'address' => $item->property ? $item->property->singleline_address : '',
                'gcss_mandate' => $item->gcssMandate ? $item->gcssMandate->mandate_id : '',
                'postcode' => $item->addressExtended ? $item->addressExtended->postal_code : '',
                'technology' => '',
                'package' => $item->tariff ? "{$item->tariff->name} ({$speedd}/{$speedu})" : '',
                'package_price' => $item->tariff ? $item->tariff->Fee : '',
                'package_price_ex_vat' => $tariffPriceExVat,
                'phone_package' => $item->phoneTariff ? $item->phoneTariff->name : '',
                'phone_package_price' => $item->phoneTariff ? $item->phoneTariff->price : '',
                'phone_package_price_ex_vat' => $phoneTariffPriceExVat,
                'vservices_name' => $vServiceName,
                'vservices_price' => $vServicePrice,
                'vservices_ex_vat' => $vServicePriceExVat,
                'arpu' => $item->tariff ? round($item->tariff->Fee * ((100 - 20) / 100), 2) : '',
                'contract' => $item->contract ? $item->contract->contract : '',
                'next_bill_date' => date('Y-m-d', strtotime('first day of next month')),
                'start_date' => $item->startDate ? date('Y-m-d', strtotime($item->startDate->start_date)) : '',
                'is_working' => $item->startDate ? 'Yes' : 'No',
                'contract_end' => $item->contractDate ? date('Y-m-d', strtotime("{$item->contractDate->date} + 12 months")) : '',
                'invoice_req' => ($item->tags->count() && $item->tags->where('tagid', 7)->count()) ? 'Yes' : 'No',
                'connected' => ($item->tags->count() && $item->tags->where('tagid', 14)->count()) ? 'Yes' : 'No',
                'disconnected' => ($item->tags->count() && $item->tags->where('tagid', 21)->count()) ? 'Yes' : 'No',
                'frozen' => $item->Passive ? 'Yes' : 'No',
                'down' => $item->Down ? 'Yes' : 'No',
                'discount' => $discount,
                'discount_exp' => $discountExp,
            ];

            if ($item->pononu) {
                $fileName = PONizer::ONUCACHE_PATH . $item->pononu->oltid . '_' . PONizer::INTCACHE_EXT;
                if (file_exists($fileName)) {
                    $ifaces = unserialize(file_get_contents($fileName));
                    if (isset($ifaces[$item->pononu->mac])) {
                        $result['onu_iface'] = $ifaces[$item->pononu->mac];
                    } else if (isset($ifaces[$item->pononu->serial])) {
                        $result['onu_iface'] = $ifaces[$item->pononu->serial];
                    }
                }
            }

            if ($item->pononu && $item->pononu->olt) {
                $result['olt'] = $item->pononu->olt->ip;
                if ($item->pononu->olt->model) {
                    $result['olt'] .= ' - ' . $item->pononu->olt->model->modelname;
                }
                $result['olt'] .= ' - ' . $item->pononu->olt->location;
            }

            foreach ($result as &$res) {
                $res = str_ireplace(',', ' ', $res);
            }

            return $result;
        });

        $this->send(
            $this->response()->json($users->toArray())
        );
    }
}
