<?php
/**
 * This is the template for generating a module class file.
 */

/** @var yii\web\View $this */
/** @var yii\gii\generators\module\Generator $generator */

$className = $generator->moduleClass;
$pos = strrpos($className, '\\');
$ns = ltrim(substr($className, 0, $pos), '\\');
$className = substr($className, $pos + 1);

echo "<?php\n";
?>
namespace <?= $generator->moduleID ?>;


class <?= $className ?> extends \helpers\BaseModule
{
    /**
     * {@inheritdoc}
     */
    public $controllerNamespace = '<?= $generator->getControllerNamespace() ?>';

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
            ['label' => 'Dashboard', 'icon' => 'paw', 'route' => '/'.$this->id, 'visible' => $this->checkRights('<?=strtolower($generator->moduleID) ?>Dashboard')],
            //This is a sample two level menu
            /*[
                'label' => 'Sample Menu',
                'icon' => 'info',
                'route' => '#',
                'submenus' => [
                    ['label' => 'Sample 1', 'route' => '/'.$this->id.'/sample-1', 'visible' => $this->checkRights('<?=strtolower($generator->moduleID) ?>Sample1')],
                    ['label' => 'Sample 2', 'route' => '/'.$this->id.'/sample-2', 'visible' => $this->checkRights('<?=strtolower($generator->moduleID) ?>Sample2')],
                    ['label' => 'Sample 3', 'route' => '/'.$this->id.'/sample-3', 'visible' => $this->checkRights('<?=strtolower($generator->moduleID) ?>Sample3')],
                ]
            ],*/
        ];
    }
    public function getMenus()
    {
        return $this->menus();
    }
}