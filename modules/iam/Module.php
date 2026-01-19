<?php

namespace iam;

class Module extends \helpers\BaseModule
{
    /**
     * {@inheritdoc}
     */
    public $controllerNamespace = 'iam\controllers';

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
            ['label' => 'Dashboard', 'icon' => 'paw', 'route' => '/' . $this->id, 'visible' => $this->checkRights('testDashboard')],
            //This is a sample two level menu
            /*[
                'label' => 'Sample Menu',
                'icon' => 'info',
                'route' => '#',
                'submenus' => [
                    ['label' => 'Sample 1', 'route' => '/'.$this->id.'/sample-1', 'visible' => $this->checkRights('testSample1')],
                    ['label' => 'Sample 2', 'route' => '/'.$this->id.'/sample-2', 'visible' => $this->checkRights('testSample2')],
                    ['label' => 'Sample 3', 'route' => '/'.$this->id.'/sample-3', 'visible' => $this->checkRights('testSample3')],
                ]
            ],*/
        ];
    }
    public function getMenus()
    {
        return $this->menus();
    }
}
