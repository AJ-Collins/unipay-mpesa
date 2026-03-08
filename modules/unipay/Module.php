<?php

namespace unipay;

class Module extends \helpers\BaseModule
{
    /**
     * {@inheritdoc}
     */
    public $controllerNamespace = 'unipay\controllers';

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
    }

    protected function menus()
    {
        return [
            ['label' => 'Dashboard',    'icon' => 'tachometer-alt', 'route' => '/' . $this->id,                         'visible' => $this->checkRights('unipayDashboard')],
            ['label' => 'Transactions', 'icon' => 'exchange-alt',   'route' => '/' . $this->id . '/mpesa/transactions', 'visible' => $this->checkRights('unipayTransactions')],
            [
                'label' => 'Payments',
                'icon'  => 'mobile-alt',
                'route' => '#',
                'submenus' => [
                    ['label' => 'STK Push',   'route' => '/' . $this->id . '/mpesa/stk-push', 'visible' => $this->checkRights('unipayStkPush')],
                    ['label' => 'B2C Payment', 'route' => '/' . $this->id . '/mpesa/b2c',      'visible' => $this->checkRights('unipayB2c')],
                    ['label' => 'B2B Payment', 'route' => '/' . $this->id . '/mpesa/b2b',      'visible' => $this->checkRights('unipayB2b')],
                ],
            ],
            [
                'label' => 'Tools',
                'icon'  => 'tools',
                'route' => '#',
                'submenus' => [
                    ['label' => 'Register C2B URLs',  'route' => '/' . $this->id . '/mpesa/c2b-register',       'visible' => $this->checkRights('unipayC2bRegister')],
                    ['label' => 'Transaction Status', 'route' => '/' . $this->id . '/mpesa/transaction-status', 'visible' => $this->checkRights('unipayTransactionStatus')],
                    ['label' => 'Account Balance',    'route' => '/' . $this->id . '/mpesa/account-balance',    'visible' => $this->checkRights('unipayAccountBalance')],
                    ['label' => 'Reversal',           'route' => '/' . $this->id . '/mpesa/reversal',           'visible' => $this->checkRights('unipayReversal')],
                ],
            ],
        ];
    }

    public function getMenus()
    {
        return $this->menus();
    }
}