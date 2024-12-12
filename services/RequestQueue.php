<?php

declare(strict_types=1);

namespace app\services;

use InvalidArgumentException;
use Yii;
use yii\db\Exception as DbException;
use app\models\Request;

/**
 * RequestQueue
 */
class RequestQueue implements RequestQueueInterface
{
    /**
     * @var RequestProcessorInterface
     */
    private RequestProcessorInterface $requestProcessor;

    /**
     * The statistics of the requests processing.
     *
     * @var array
     */
    private array $summary = [
        'processedCount' => 0,
        'approvedCount' => 0,
        'declinedCount' => 0,
        'skippedCount' => 0,
    ];

    /**
     * RequestQueue constructor
     *
     * @param RequestProcessorInterface $requestProcessor
     */
    public function __construct(RequestProcessorInterface $requestProcessor)
    {
        $this->requestProcessor = $requestProcessor;
    }

    /**
     * Processes all requests with a PENDING status
     *
     * @param int $delay Delay in seconds to process each request
     * @throws DbException
     * @throws InvalidArgumentException
     */
    public function processAllPendingRequests(int $delay = 0): void
    {
        Yii::info("Start processing requests");

        if ($delay < 0) {
            throw new InvalidArgumentException("The delay parameter must be a non-negative integer");
        }

        $previousRequestId = 0;
        $attempt = 0;
        $maxAttempts = 5;

        // Requests processing cycle
        while (true) {
            $attempt++;
            try {
                // Retrieve one request with PENDING status
                /** @var Request|null $request */
                $request = Request::find()
                    ->where(['status' => Request::STATUS_PENDING])
                    ->andWhere(['>', 'id', $previousRequestId])
                    ->orderBy(['id' => SORT_ASC])
                    ->limit(1)
                    ->one();

                if (!$request) {
                    // There are no more requests to process
                    break;
                }

                Yii::info("Process request #{$request->id} (for user #{$request->user_id})");

                // Processing the request
                $result = $this->requestProcessor->processRequest($request, $delay);

                if ($result) {
                    $this->summary['processedCount']++;
                    if ($request->status === Request::STATUS_APPROVED) {
                        $this->summary['approvedCount']++;
                        Yii::info("Decision: approved");
                    } elseif ($request->status === Request::STATUS_DECLINED) {
                        $this->summary['declinedCount']++;
                        Yii::info("Decision: declined");
                    }
                } else {
                    $this->summary['skippedCount']++;
                    Yii::info("Request's user #{$request->user_id} is blocked by another process - skipping the request");
                }

                $previousRequestId = $request->id;
            } catch (DbException $e) {
                Yii::error("Database Error: " . $e->getMessage());

                // Handle specific PostgreSQL error codes that require to simple restart transaction with random delay
                $errorCode = $e->errorInfo[0] ?? null;
                if (in_array($errorCode, ['40001', '40P01', '55P03'])) { // Serialization Failure, Deadlock Detected, Lock Not Available
                    if ($attempt === $maxAttempts) {
                        Yii::error("The maximum number of attempts ({$maxAttempts}) for a transaction has been reached");
                        throw $e;
                    } else {
                        $retryDelayMs = mt_rand(0, $attempt * 100 - 1);
                        Yii::warning("Retrying ({$attempt}) with delay {$retryDelayMs} ms...");
                        usleep($retryDelayMs * 1000);
                        continue;
                    }
                } else {
                    throw $e;
                }
            }
        }

        Yii::info("Processing completed. Total processed: {$this->summary['processedCount']}, Approved: {$this->summary['approvedCount']}, Declined: {$this->summary['declinedCount']}, Skipped: {$this->summary['skippedCount']}");
    }

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
    public function getSummary(): array
    {
        return $this->summary;
    }
}
