<?php
namespace admin;


class Module extends \helpers\BaseModule
{
    /**
     * {@inheritdoc}
     */
    public $controllerNamespace = 'admin\controllers';

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
            ['label' => 'Dashboard', 'icon' => 'paw', 'route' => '/'.$this->id, 'visible' => $this->checkRights('adminDashboard')],
            //This is a sample two level menu
            /*[
                'label' => 'Sample Menu',
                'icon' => 'info',
                'route' => '#',
                'submenus' => [
                    ['label' => 'Sample 1', 'route' => '/'.$this->id.'/sample-1', 'visible' => $this->checkRights('adminSample1')],
                    ['label' => 'Sample 2', 'route' => '/'.$this->id.'/sample-2', 'visible' => $this->checkRights('adminSample2')],
                    ['label' => 'Sample 3', 'route' => '/'.$this->id.'/sample-3', 'visible' => $this->checkRights('adminSample3')],
                ]
            ],*/
        ];
    }
    public function getMenus()
    {
        return $this->menus();
    }
}