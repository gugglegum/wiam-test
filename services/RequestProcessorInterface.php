<?php
// app/components/RequestProcessorInterface.php

namespace app\services;

use app\models\Request;

interface RequestProcessorInterface
{
    /**
     * Process single request
     *
     * @param Request $request
     * @param int $delay
     * @return bool
     */
    public function processRequest(Request $request, int $delay = 0): bool;
}
