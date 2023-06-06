<?php

namespace App\Traits;

use App\Models\Contract;
use App\Models\Payment;
use App\Models\PbTransTag;
use App\Models\PbTransUser;
use App\Models\User;
use Illuminate\Support\Carbon;

trait PbTransactionPayment
{
    protected $users = [];

    protected $payments = [];

    public $totalAssigned = 0;

    protected $paymentType = 1;

    protected $errors = [];

    protected $paymentNote = "Payment based on Bank transaction";

    public function errors()
    {
        return $this->errors;
    }

    public function identifiedUsers($logins): bool
    {
        $logins = !is_array($logins) ? [$logins] : $logins;
        foreach ($logins as $login) {

            $user = User::find($login);
            if ($user) $this->users[] = $user;
        }
        return !empty($this->users);
    }

    public function identifiedPayments($logins = null, $ignoreReal = false): bool
    {
        $this->totalAssigned = 0;
        $logins = !is_array($logins) ? [$logins] : $logins;

        foreach ($logins as $login) {
            $commitedPayments = $this->commitedPayments($login);
            foreach ($commitedPayments as $commited) {
                if (!$ignoreReal && !$commited->payment) continue;
                $this->payments[] = $commited->payment ? $commited->payment : $commited;
                $this->totalAssigned += $commited->sum;
            }
        }
        $this->items['payments'] = $this->payments;
        return !empty($this->payments);
    }

    public function createPayments($sum): array
    {
        $date = Carbon::now()->format('Y-m-d H:i:s');
        foreach ($this->users as $user) {
            try {
                $payment = new Payment();
                $payment->admin = whoami();
                $payment->login = $user->login;
                $payment->balance = $user->Cash;
                $payment->summ = $sum;
                $payment->cashtypeid = $this->paymentType;
                $payment->date = $date;
                $payment->note = "{$this->paymentNote} ({$date})";

                if ($this->commitUserTransPayment($payment)) {
                    $this->payments[] = $payment;
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        return $this->payments;
    }

    public function rollbackPayments(): array
    {
        foreach ($this->payments as $io => $payment) {
            try {
                if ($this->commitUserTransPayment($payment, true)) {
                    unset($this->payments[$io]);
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        return $this->payments;
    }

    public function assignedUsers()
    {
        $users = [];
        $payments = $this->commitedPayments();

        if ($payments) {
            foreach ($payments as $payment) {
                $user = User::with('contract')->find($payment->login);
                if ($user && $user->contract) {
                    $contracts = Contract::with('user.realname')
                        ->where('contract', '=', $user->contract->contract)
                        ->get();
                    foreach ($contracts as $contract) {
                        if ($contract && $contract->user && !isset($users[$contract->login])) {
                            $contract->sum = $contract->login == $payment->login ? $payment->sum : 0;
                            $contract->assigned = true;
                            $contract->payment = $payment;
                            $users[$contract->login] = $contract;
                        }
                    }
                }
            }
            if (!$this->has('users')) {
                $this->items['users'] = $users;
            } else {
                $this->items['users'] = array_merge($this->items['users'], $users);
            }
        }

        return $users;
    }

    public function advisableUsers()
    {
        $users = [];
        preg_match_all('!\d+!', $this->osnd, $matches);

        if ($matches) {
            foreach (current($matches) as $number) {
                $contracts = Contract::with('user.realname')
                    ->where('contract', '=', $number)
                    ->get();
                foreach ($contracts as $contract) {
                    if ($contract && $contract->user && !isset($users[$contract->login])) {
                        $contract->sum = 0;
                        $contract->advisable = true;
                        $users[$contract->login] = $contract;
                    }
                }
            }
            if (!$this->has('users')) {
                $this->items['users'] = $users;
            } else {
                $this->items['users'] = array_merge($this->items['users'], $users);
            }
        }

        return $users;
    }

    protected function commitedPayments($login = null)
    {
        try {
            $commit = PbTransUser::with(['payment', 'transaction'])
                ->where('trans_id', '=', $this->getId());
            if ($login) {
                $commit->where('login', '=', $login);
            }
            return $commit->get();
        } catch (\Exception $e) {
            return [];
        }
    }

    public function commitOnlyTransPayment($login, $sum, $rollback = false)
    {
        try {
            if ($rollback) {
                $commit = PbTransUser::where('trans_id', '=', $this->getId())
                    ->where('login', '=', $login)
                    ->first();
                if ($commit) {
                    PbTransTag::where('user_id', $commit->id)
                        ->delete();
                    return $commit->delete();
                }
            } else {
                $commit = new PbTransUser();
                $commit->sum = $sum;
                $commit->date = curdatetime();
                $commit->admin = whoami();
                $existed = PbTransUser::where('login', '=', $commit->login = $login)
                    ->where('trans_id', '=', $commit->trans_id = $this->getId())
                    ->first();
                if ($existed) {
                    $this->errors[] = __('Payment already exists');
                    return false;
                }
                $commit->payment_id = null;
                return $commit->save();
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function commitUserTransPayment($payment, $rollback = false)
    {
        try {
            if ($rollback) {
                $commit = PbTransUser::where('trans_id', '=', $this->getId())
                    ->where('payment_id', '=', $payment->id)
                    ->where('login', '=', $payment->login)
                    ->first();
                if ($payment->rollback()) {
                    PbTransTag::where('user_id', $commit->id)
                        ->delete();
                    return $commit->delete();
                }
            } else {
                $commit = new PbTransUser();
                $commit->sum = $payment->summ;
                $commit->date = $payment->date;
                $commit->admin = $payment->admin;
                $existed = PbTransUser::where('login', '=', $commit->login = $payment->login)
                    ->where('trans_id', '=', $commit->trans_id = $this->getId())
                    ->first();
                if ($existed) {
                    $this->errors[] = __('Payment already exists');
                    return false;
                }
                if ($payment->add()) {
                    $commit->payment_id = $payment->id;
                    return $commit->save() ? true : !$payment->rollback();
                }
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    abstract public function getId();
    abstract public function getSum();
}
