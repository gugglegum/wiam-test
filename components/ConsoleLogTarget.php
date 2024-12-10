<?php

declare(strict_types=1);

namespace app\components;

use yii\log\Logger;
use yii\log\Target;

class ConsoleLogTarget extends Target
{
    public $exportInterval = 1;

    /**
     * Export logs
     */
    public function export()
    {
        foreach ($this->messages as $message) {
            list($text, $level, $category, $timestamp) = $message;

            // Formatting a message
            $levelName = Logger::getLevelName($level);
            $time = date('Y-m-d H:i:s', (int) $timestamp);
//            $formattedMessage = "[$time][$levelName][$category] $text\n";
            $formattedMessage = "[$levelName][$category] $text\n";

            if ($level === Logger::LEVEL_ERROR) {
                fwrite(STDERR, $formattedMessage);
            } else {
                fwrite(STDOUT, $formattedMessage);
            }
        }
    }
}
