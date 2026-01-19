<?php

if (!function_exists('dd')) {
    /**
     * Dump and die with JSON output (Yii2 equivalent of Laravel's dd).
     *
     * @param mixed $var The variable to dump
     * @param bool $pretty Print with indentation?
     */
    function dd($var, $pretty = true)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        Yii::$app->response->statusCode = 200;
        Yii::$app->response->data = $var;
        if ($pretty) {
            echo json_encode($var, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode($var);
        }
        Yii::$app->end();
    }
}