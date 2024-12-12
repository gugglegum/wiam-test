<?php

declare(strict_types=1);

namespace app\commands;

use app\services\RequestQueue;
use InvalidArgumentException;
use Throwable;
use Yii;
use yii\base\Module;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * ProcessorController
 *
 * Processes all requests in PENDING status, making a decision: Approve or Decline.
 */
class ProcessorController extends Controller
{
    /**
     * Request processing service
     *
     * @var RequestQueue
     */
    private RequestQueue $requestQueue;

    /**
     * ProcessorController constructor
     *
     * @param string $id
     * @param Module $module
     * @param RequestQueue $requestQueue
     * @param array $config
     */
    public function __construct($id, $module, RequestQueue $requestQueue, array $config = [])
    {
        $this->requestQueue = $requestQueue;
        parent::__construct($id, $module, $config);
    }

    /**
     * Processes all requests with a PENDING status.
     *
     * @param integer $delay Delay in seconds to process each request.
     * @return int Exit code
     */
    public function actionIndex(int $delay = 0): int
    {
        try {
            $this->requestQueue->processAllPendingRequests($delay);
        } catch (InvalidArgumentException $e) {
            Yii::error("Invalid Argument: " . $e->getMessage());
            return ExitCode::DATAERR;
        } catch (Throwable $e) {
            Yii::error("Processing Error: " . $e->getMessage());
            return ExitCode::UNSPECIFIED_ERROR;
        }
        return ExitCode::OK;
    }
}
