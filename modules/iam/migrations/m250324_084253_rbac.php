<?php

use yii\db\Query;
use helpers\Migration;
use yii\base\InvalidConfigException;
use helpers\auth\AuthManager as DbManager;

/**
 * Class m250324_084253_rbac
 */
class m250324_084253_rbac extends Migration
{

    /**
     * @throws yii\base\InvalidConfigException
     * @return DbManager
     */
    protected function getAuthManager()
    {
        $authManager = Yii::$app->getAuthManager();
        if (!$authManager instanceof DbManager) {
            throw new InvalidConfigException('You should configure "authManager" component to use database before executing this migration.');
        }
        return $authManager;
    }
    public function up()
    {
        $authManager = $this->getAuthManager();
        $this->db = $authManager->db;
        $schema = $this->db->getSchema()->defaultSchema;

        $this->createTable($authManager->ruleTable, [
            'name' => $this->string(64)->notNull(),
            'data' => $this->binary(),
            'created_at' => $this->integer(),
            'updated_at' => $this->integer(),
            'PRIMARY KEY ([[name]])',
        ], $this->tableOptions);

        $this->createTable($authManager->itemTable, [
            'name' => $this->string(64)->notNull(),
            'type' => $this->smallInteger()->notNull(),
            'description' => $this->text(),
            'rule_name' => $this->string(64),
            'data' => $this->text(),
            'created_at' => $this->integer(),
            'updated_at' => $this->integer(),
            'PRIMARY KEY ([[name]])',
            'FOREIGN KEY ([[rule_name]]) REFERENCES ' . $authManager->ruleTable . ' ([[name]])' .
                $this->buildFkClause('ON DELETE SET NULL', 'ON UPDATE CASCADE'),
        ], $this->tableOptions);
        $this->createIndex('idx-auth_item-type', $authManager->itemTable, 'type');

        $this->createTable($authManager->itemChildTable, [
            'parent' => $this->string(64)->notNull(),
            'child' => $this->string(64)->notNull(),
            'PRIMARY KEY ([[parent]], [[child]])',
            'FOREIGN KEY ([[parent]]) REFERENCES ' . $authManager->itemTable . ' ([[name]])' .
                $this->buildFkClause('ON DELETE CASCADE', 'ON UPDATE CASCADE'),
            'FOREIGN KEY ([[child]]) REFERENCES ' . $authManager->itemTable . ' ([[name]])' .
                $this->buildFkClause('ON DELETE CASCADE', 'ON UPDATE CASCADE'),
        ], $this->tableOptions);

        $this->createTable($authManager->assignmentTable, [
            'item_name' => $this->string(64)->notNull(),
            'user_id' => $this->string(64)->notNull(),
            'created_at' => $this->integer(),
            'PRIMARY KEY ([[item_name]], [[user_id]])',
            'FOREIGN KEY ([[item_name]]) REFERENCES ' . $authManager->itemTable . ' ([[name]])' .
                $this->buildFkClause('ON DELETE CASCADE', 'ON UPDATE CASCADE'),
        ], $this->tableOptions);

        if ($this->isMSSQL()) {
            $this->execute("CREATE TRIGGER {$schema}.trigger_auth_item_child
            ON {$schema}.{$authManager->itemTable}
            INSTEAD OF DELETE, UPDATE
            AS
            DECLARE @old_name VARCHAR (64) = (SELECT name FROM deleted)
            DECLARE @new_name VARCHAR (64) = (SELECT name FROM inserted)
            BEGIN
            IF COLUMNS_UPDATED() > 0
                BEGIN
                    IF @old_name <> @new_name
                    BEGIN
                        ALTER TABLE {$authManager->itemChildTable} NOCHECK CONSTRAINT FK__auth_item__child;
                        UPDATE {$authManager->itemChildTable} SET child = @new_name WHERE child = @old_name;
                    END
                UPDATE {$authManager->itemTable}
                SET name = (SELECT name FROM inserted),
                type = (SELECT type FROM inserted),
                description = (SELECT description FROM inserted),
                rule_name = (SELECT rule_name FROM inserted),
                data = (SELECT data FROM inserted),
                created_at = (SELECT created_at FROM inserted),
                updated_at = (SELECT updated_at FROM inserted)
                WHERE name IN (SELECT name FROM deleted)
                IF @old_name <> @new_name
                    BEGIN
                        ALTER TABLE {$authManager->itemChildTable} CHECK CONSTRAINT FK__auth_item__child;
                    END
                END
                ELSE
                    BEGIN
                        DELETE FROM {$schema}.{$authManager->itemChildTable} WHERE parent IN (SELECT name FROM deleted) OR child IN (SELECT name FROM deleted);
                        DELETE FROM {$schema}.{$authManager->itemTable} WHERE name IN (SELECT name FROM deleted);
                    END
            END;");

            $authManager = $this->getAuthManager();
            $this->db = $authManager->db;
            $schema = $this->db->getSchema()->defaultSchema;
            $triggerSuffix = $this->db->schema->getRawTableName($authManager->itemChildTable);

            $this->execute("IF (OBJECT_ID(N'{$schema}.trigger_{$triggerSuffix}') IS NOT NULL) DROP TRIGGER {$schema}.trigger_{$triggerSuffix};");
            $this->execute("IF (OBJECT_ID(N'{$schema}.trigger_auth_item_child') IS NOT NULL) DROP TRIGGER {$schema}.trigger_auth_item_child;");

            $this->execute("CREATE TRIGGER {$schema}.trigger_delete_{$triggerSuffix}
            ON {$schema}.{$authManager->itemTable}
            INSTEAD OF DELETE
            AS
            BEGIN
                  DELETE FROM {$schema}.{$authManager->itemChildTable} WHERE parent IN (SELECT name FROM deleted) OR child IN (SELECT name FROM deleted);
                  DELETE FROM {$schema}.{$authManager->itemTable} WHERE name IN (SELECT name FROM deleted);
            END;");

            $foreignKey = $this->findForeignKeyName($authManager->itemChildTable, 'child', $authManager->itemTable, 'name');
            $this->execute("CREATE TRIGGER {$schema}.trigger_update_{$triggerSuffix}
            ON {$schema}.{$authManager->itemTable}
            INSTEAD OF UPDATE
            AS
                DECLARE @old_name NVARCHAR(64) = (SELECT name FROM deleted)
                DECLARE @new_name NVARCHAR(64) = (SELECT name FROM inserted)
            BEGIN
                IF @old_name <> @new_name
                BEGIN
                    ALTER TABLE {$authManager->itemChildTable} NOCHECK CONSTRAINT {$foreignKey};
                    UPDATE {$authManager->itemChildTable} SET child = @new_name WHERE child = @old_name;
                END
            UPDATE {$authManager->itemTable}
            SET name = (SELECT name FROM inserted),
            type = (SELECT type FROM inserted),
            description = (SELECT description FROM inserted),
            rule_name = (SELECT rule_name FROM inserted),
            data = (SELECT data FROM inserted),
            created_at = (SELECT created_at FROM inserted),
            updated_at = (SELECT updated_at FROM inserted)
            WHERE name IN (SELECT name FROM deleted)
            IF @old_name <> @new_name
                BEGIN
                    ALTER TABLE {$authManager->itemChildTable} CHECK CONSTRAINT {$foreignKey};
                END
            END;");
        }
        $this->createIndex('{{%idx-auth_assignment-user_id}}', $authManager->assignmentTable, 'user_id');

        $this->dropIndex('idx-auth_item-type', $authManager->itemTable);
        $this->createIndex('{{%idx-auth_item-type}}', $authManager->itemTable, 'type');

        $this->addColumn($authManager->ruleTable, 'rule_title', $this->string(64)->null()->after('name'));
        $this->addColumn($authManager->ruleTable, 'description', $this->text()->null()->after('rule_title'));
        $this->addColumn($authManager->ruleTable, 'module', $this->string(64)->null()->after('description'));
        $this->addColumn($authManager->ruleTable, 'file_hash', $this->string(64)->null()->after('module'));
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
        $authManager = $this->getAuthManager();
        $this->db = $authManager->db;
        $schema = $this->db->getSchema()->defaultSchema;
        $this->dropTable($authManager->assignmentTable);
        $this->dropTable($authManager->itemChildTable);
        $this->dropTable($authManager->itemTable);
        $this->dropTable($authManager->ruleTable);
        if ($this->isMSSQL()) {
            $this->execute("DROP TRIGGER {$schema}.trigger_auth_item_child;");
            $triggerSuffix = $this->db->schema->getRawTableName($authManager->itemChildTable);

            $this->execute("DROP TRIGGER {$schema}.trigger_update_{$triggerSuffix};");
            $this->execute("DROP TRIGGER {$schema}.trigger_delete_{$triggerSuffix};");

            $this->execute("CREATE TRIGGER {$schema}.trigger_auth_item_child
            ON {$schema}.{$authManager->itemTable}
            INSTEAD OF DELETE, UPDATE
            AS
            DECLARE @old_name VARCHAR (64) = (SELECT name FROM deleted)
            DECLARE @new_name VARCHAR (64) = (SELECT name FROM inserted)
            BEGIN
            IF COLUMNS_UPDATED() > 0
                BEGIN
                    IF @old_name <> @new_name
                    BEGIN
                        ALTER TABLE {$authManager->itemChildTable} NOCHECK CONSTRAINT FK__auth_item__child;
                        UPDATE {$authManager->itemChildTable} SET child = @new_name WHERE child = @old_name;
                    END
                UPDATE {$authManager->itemTable}
                SET name = (SELECT name FROM inserted),
                type = (SELECT type FROM inserted),
                description = (SELECT description FROM inserted),
                rule_name = (SELECT rule_name FROM inserted),
                data = (SELECT data FROM inserted),
                created_at = (SELECT created_at FROM inserted),
                updated_at = (SELECT updated_at FROM inserted)
                WHERE name IN (SELECT name FROM deleted)
                IF @old_name <> @new_name
                    BEGIN
                        ALTER TABLE {$authManager->itemChildTable} CHECK CONSTRAINT FK__auth_item__child;
                    END
                END
                ELSE
                    BEGIN
                        DELETE FROM {$schema}.{$authManager->itemChildTable} WHERE parent IN (SELECT name FROM deleted) OR child IN (SELECT name FROM deleted);
                        DELETE FROM {$schema}.{$authManager->itemTable} WHERE name IN (SELECT name FROM deleted);
                    END
            END;");
        }
    }

    public function buildFkClause($delete = '',  $update = '')
    {
        if ($this->isMSSQL()) {
            return '';
        }

        if ($this->isOracle()) {
            return ' ' . $delete;
        }

        return implode(' ', ['', $delete, $update]);
    }
    protected function findForeignKeyName($table, $column, $referenceTable, $referenceColumn)
    {
        return (new Query())
            ->select(['OBJECT_NAME(fkc.constraint_object_id)'])
            ->from(['fkc' => 'sys.foreign_key_columns'])
            ->innerJoin(['c' => 'sys.columns'], 'fkc.parent_object_id = c.object_id AND fkc.parent_column_id = c.column_id')
            ->innerJoin(['r' => 'sys.columns'], 'fkc.referenced_object_id = r.object_id AND fkc.referenced_column_id = r.column_id')
            ->andWhere('fkc.parent_object_id=OBJECT_ID(:fkc_parent_object_id)', [':fkc_parent_object_id' => $this->db->schema->getRawTableName($table)])
            ->andWhere('fkc.referenced_object_id=OBJECT_ID(:fkc_referenced_object_id)', [':fkc_referenced_object_id' => $this->db->schema->getRawTableName($referenceTable)])
            ->andWhere(['c.name' => $column])
            ->andWhere(['r.name' => $referenceColumn])
            ->scalar($this->db);
    }
    protected function isMSSQL()
    {
        return $this->db->driverName === 'mssql' || $this->db->driverName === 'sqlsrv' || $this->db->driverName === 'dblib';
    }
    protected function isOracle()
    {
        return $this->db->driverName === 'oci' || $this->db->driverName === 'oci8';
    }
}
