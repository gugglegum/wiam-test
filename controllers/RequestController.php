<?php

declare(strict_types=1);

namespace app\controllers;

use app\services\RequestCreator;
use Yii;
use yii\db\Exception as DbException;
use yii\rest\Controller;
use yii\web\Response;
use yii\web\BadRequestHttpException;

/**
 * RequestController
 *
 * Creates new request
 */
class RequestController extends Controller
{
    private RequestCreator $requestCreator;

    public function __construct($id, $module, RequestCreator $requestCreator, array $config = [])
    {
        $this->requestCreator = $requestCreator;
        parent::__construct($id, $module, $config);
    }

    /**
     * Configuring behavior to return JSON responses
     *
     * @return array
     */
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['contentNegotiator']['formats']['application/json'] = Response::FORMAT_JSON;
        return $behaviors;
    }

    /**
     * Create a new request by handling request POST /requests
     *
     * @return array
     * @throws BadRequestHttpException
     *
     * @SWG\Post(
     *     path="/requests",
     *     summary="Create new request",
     *     tags={"Requests"},
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         required=true,
     *         @SWG\Schema(
     *             type="object",
     *             required={"user_id", "amount", "term"},
     *             @SWG\Property(
     *                 property="user_id",
     *                 type="integer",
     *                 description="User ID",
     *                 example=4
     *             ),
     *             @SWG\Property(
     *                 property="amount",
     *                 type="integer",
     *                 description="Amount of money",
     *                 example=3000
     *             ),
     *             @SWG\Property(
     *                 property="term",
     *                 type="integer",
     *                 description="Credit request period in days",
     *                 example=30
     *             )
     *         )
     *     ),
     *     @SWG\Response(
     *         response=201,
     *         description="Request successfully created",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(property="result", type="boolean", example=true),
     *             @SWG\Property(property="id", type="integer", example=123)
     *         )
     *     ),
     *     @SWG\Response(
     *         response=400,
     *         description="Incorrect input data",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(property="result", type="boolean", example=false),
     *             @SWG\Property(property="error", type="string", example="User Id is invalid.")
     *         )
     *     )
     * )
     */
    public function actionCreate(): array
    {
        $payload = Yii::$app->request->post();

        if (empty($payload)) {
            throw new BadRequestHttpException('Empty POST payload');
        }

        try {
            $result = $this->requestCreator->createRequest($payload);
            if ($result['success']) {
                // Set HTTP status 201 (Created)
                Yii::$app->response->statusCode = 201;
                return [
                    'result' => true,
                    'id' => $result['request']->id,
                ];
            } else {
                // Set HTTP status 500 (Internal Server Error)
                Yii::$app->response->statusCode = 500;
                return [
                    'result' => false,
                    'error' => $result['request']->hasErrors() ? $result['request']->errors[array_key_first($result['request']->errors)][0] : 'Unknown error',
                ];
            }
        } catch (DbException $e) {
            Yii::info("DB error: [{$e->getCode()}] {$e->getMessage()}");

            // Set HTTP status 500 (Internal Server Error)
            Yii::$app->response->statusCode = 500;

            return [
                'result' => false,
                'error' => YII_DEBUG ? $e->getMessage() : 'Check logs for details',
            ];
        }
    }
}
