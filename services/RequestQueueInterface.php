<?php

declare(strict_types=1);

namespace app\services;

interface RequestQueueInterface
{
    /**
     * Processes all requests with a PENDING status
     *
     * @param int $delay Delay in seconds to process each request
     */
    public function processAllPendingRequests(int $delay = 0): void;

    /**
     * Returns the statistics of the requests processing
     *
     * @return array{
     *     processedCount: int,
     *     approvedCount: int,
     *     declinedCount: int,
     *     skippedCount: int
     * }
     */
    public function getSummary(): array;
}
