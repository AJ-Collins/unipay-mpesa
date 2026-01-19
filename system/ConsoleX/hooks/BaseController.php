<?php

namespace ConsoleX\hooks;

class BaseController extends \yii\console\Controller
{
    public function actionIndex()
    {
        $this->stdout("Welcome to the IAM Console Controller!\n");
        $this->stdout("Use 'help' to see available commands.\n");
    }
    public function actionHelp()
    {
        $this->stdout("Available commands:\n");
        $this->stdout("  index - Displays a welcome message.\n");
        $this->stdout("  help - Displays this help message.\n");
    }
}