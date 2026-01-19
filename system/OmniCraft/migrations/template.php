<?php
/**
 * This view is used by console/controllers/MigrateController.php.
 *
 * The following variables are available in this view:
 */
/* @var $className string the new migration class name without namespace */
/* @var $namespace string the new migration class namespace */

echo "<?php\n";
if (!empty($namespace)) {
    echo "\nnamespace {$namespace};\n";
}
?>

use \helpers\Migration;

/**
 * Class <?= $className . "\n" ?>
 */
class <?= $className ?> extends Migration
{
    public function up()
    {
        $this->createTable('{{%<?= $className ?>}}', [
            'id' => $this->primaryKey(),
            'is_deleted' => $this->integer(2)->notNull()->defaultValue(0),
            'status' => $this->integer(3)->notNull()->defaultValue(10),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $this->tableOptions);
    }
    public function down()
    {
        $this->dropTable('{{%<?= $className ?>}}');
    }
}
