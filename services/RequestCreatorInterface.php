<?php

namespace app\services;

use app\models\Request;

interface RequestCreatorInterface
{
    /**
     * Create request from data array
     *
     * @return array{
     *     'success': bool,
     *     'request': Request
     * }
     */
    public function createRequest(array $payload): array;
}
