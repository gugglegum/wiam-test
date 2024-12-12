<?php

declare(strict_types=1);

namespace app\controllers;

use InvalidArgumentException;
use Throwable;
use Yii;
use yii\base\Module;
use yii\rest\Controller;
use yii\web\Response;
use yii\web\BadRequestHttpException;
use yii\filters\ContentNegotiator;
use app\services\RequestQueue;
use yii\web\ServerErrorHttpException;

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
     * Configuring behavior to return JSON responses
     *
     * @return array
     */
    public function behaviors(): array
    {
        return [
            'contentNegotiator' => [
                'class' => ContentNegotiator::class,
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ],
            ],
        ];
    }

    /**
     * Processing of all requests with PENDING status
     *
     * @param int $delay
     * @return array
     * @throws BadRequestHttpException|ServerErrorHttpException
     *
     * @SWG\Get(
     *     path="/processor",
     *     summary="Process all pending requests",
     *     tags={"Processor"},
     *     @SWG\Parameter(
     *         name="delay",
     *         in="query",
     *         type="integer",
     *         description="Delay in seconds before each request is processed",
     *         required=false,
     *         default=0,
     *         example=5
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Successful processing of requests",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(property="result", type="boolean", example=true)
     *         )
     *     ),
     *     @SWG\Response(
     *         response=400,
     *         description="Incorrect delay parameter",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(property="result", type="boolean", example=false),
     *             @SWG\Property(property="error", type="string", example="Invalid delay parameter")
     *         )
     *     ),
     *     @SWG\Response(
     *         response=500,
     *         description="Internal server error",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(property="result", type="boolean", example=false),
     *             @SWG\Property(property="error", type="string", example="Processing error")
     *         )
     *     )
     * )
     */
    public function actionHandle(int $delay = 0): array
    {
        try {
            $this->requestQueue->processAllPendingRequests($delay);
        } catch (InvalidArgumentException $e) {
            Yii::error("Invalid Argument: " . $e->getMessage());
            throw new BadRequestHttpException($e->getMessage());
        } catch (Throwable $e) {
            Yii::error("Processing Error: " . $e->getMessage());
            throw new ServerErrorHttpException($e->getMessage());
        }

        return [
            'result' => true,
        ];
    }
}
