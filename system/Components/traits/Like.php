<?php

namespace helpers\traits;

trait Like
{
    public $q;
    /**
     * Creates a case-insensitive LIKE condition for the specified column.
     * Automatically uses ILIKE for PostgreSQL and LIKE for MySQL/MariaDB.
     *
     * Usage: Model::ciLike('username', '%john%')
     *
     * @param string $column The column name
     * @param string $value The search value
     * @return array The query condition array
     */
    public static function ciLike(string $column, string $value): array
    {
        $driver = \Yii::$app->db->driverName;

        if ($driver === 'pgsql') {
            return ['ilike', $column, $value];
        }

        // MySQL / MariaDB
        return ['like', $column, $value];
    }

    /**
     * Creates a case-insensitive LIKE condition for multiple columns (OR logic).
     * Useful for searching across multiple fields simultaneously.
     *
     * Usage: Model::ciLikeAny(['username', 'email'], '%test%')
     *
     * @param array $columns The column names to search
     * @param string $value The search value
     * @return array The OR query condition array
     */
    public static function ciLikeAny(array $columns, string $value): array
    {
        $or = ['or'];

        foreach ($columns as $column) {
            $or[] = static::ciLike($column, $value);
        }

        return $or;
    }
}
