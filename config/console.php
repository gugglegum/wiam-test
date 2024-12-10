<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'app\commands',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
        '@tests' => '@app/tests',
    ],
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'log' => [
            'flushInterval' => 1, // To output log in realtime without buffering
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                // Target for log into file
                [
                    'class' => yii\log\FileTarget::class,
                    'levels' => ['error', 'warning', 'info'],
                    'logFile' => '@runtime/logs/app.log', // Путь к файлу лога
                    'maxFileSize' => 1024, // Максимальный размер файла в МБ
                    'maxLogFiles' => 5, // Количество файлов для хранения
                    'categories' => [], // Категории логов (по умолчанию все)
                    'except' => [], // Исключенные категории
                    'logVars' => [],
                ],
                // Target for log into console
                [
                    'class' => app\components\ConsoleLogTarget::class,
                    'levels' => ['error', 'warning', 'info'],
                    'categories' => ['application'], // Категории логов (по умолчанию все)
                    'logVars' => [],
                ],
            ],
        ],
        'db' => $db,
    ],
    'params' => $params,
    /*
    'controllerMap' => [
        'fixture' => [ // Fixture generation command line.
            'class' => 'yii\faker\FixtureController',
        ],
    ],
    */
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
    ];
    // configuration adjustments for 'dev' environment
    // requires version `2.1.21` of yii2-debug module
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;
