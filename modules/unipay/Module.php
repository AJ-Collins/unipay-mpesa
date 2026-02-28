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
    protected  function menus()
    {
        return [
            ['label' => 'Dashboard', 'icon' => 'paw', 'route' => '/'.$this->id, 'visible' => $this->checkRights('unipayDashboard')],
            //This is a sample two level menu
            /*[
                'label' => 'Sample Menu',
                'icon' => 'info',
                'route' => '#',
                'submenus' => [
                    ['label' => 'Sample 1', 'route' => '/'.$this->id.'/sample-1', 'visible' => $this->checkRights('unipaySample1')],
                    ['label' => 'Sample 2', 'route' => '/'.$this->id.'/sample-2', 'visible' => $this->checkRights('unipaySample2')],
                    ['label' => 'Sample 3', 'route' => '/'.$this->id.'/sample-3', 'visible' => $this->checkRights('unipaySample3')],
                ]
            ],*/
        ];
    }
    public function getMenus()
    {
        return $this->menus();
    }
}