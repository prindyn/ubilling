<?php

namespace App\Models;

use App\Traits\Filterable;
use App\Models\Passive\BaseModel;
use App\Models\Passive\ContractDate;
use App\Models\Passive\AddressExtended;

class User extends BaseModel
{
    use Filterable;

    public function tariff()
    {
        return $this->hasOne(Tariff::class, 'name', 'Tariff');
    }

    public function contract()
    {
        return $this->hasOne(Contract::class, 'login', 'login');
    }

    public function contractDate()
    {
        return $this->hasOneThrough(
            ContractDate::class,
            Contract::class,
            'login',
            'contract',
            'login',
            'login'
        );
    }

    public function startDate()
    {
        return $this->hasOne(StartDate::class, 'login', 'login');
    }

    public function virtualServices()
    {
        return $this->hasManyThrough(
            VirtualService::class,
            Tag::class,
            'login',
            'tagid',
            'login',
            'tagid'
        );
    }

    public function nethost()
    {
        return $this->hasOne(Nethost::class, 'ip', 'IP');
    }

    public function phone()
    {
        return $this->hasOne(Phone::class, 'login', 'login');
    }

    public function pononu()
    {
        return $this->hasOne(PonOnu::class, 'login', 'login');
    }

    public function phoneTariff()
    {
        return $this->hasOneThrough(
            PhoneTariff::class,
            UsersPhoneService::class,
            'login',
            'id',
            'login',
            'tariff_id'
        );
    }

    public function gcssMandate()
    {
        return $this->hasOne(GcssMandate::class, 'login', 'login');
    }

    public function email()
    {
        return $this->hasOne(Email::class, 'login', 'login');
    }

    public function tags()
    {
        return $this->hasMany(Tag::class, 'login', 'login');
    }

    public function realname()
    {
        return $this->hasOne(RealName::class, 'login', 'login');
    }

    public function addressExtended()
    {
        return $this->hasOne(AddressExtended::class, 'login', 'login');
    }

    public function property()
    {
        return $this->hasOneThrough(
            Property::class,
            AddressExtended::class,
            'login',
            'uprn',
            'login',
            'uprn'
        );
    }

    public function dcmsFunding()
    {
        return $this->hasOne(DcmsFunding::class, 'login', 'login');
    }
}
