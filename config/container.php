<?php

// DI-container (included by web.php and console.php)

return [
    'definitions' => [
        'app\services\RequestCreatorInterface' => 'app\services\RequestCreator',
        'app\services\RequestProcessorInterface' => 'app\services\RequestProcessor',
        'app\services\RequestQueueInterface' => 'app\services\RequestQueue',
    ],
];
