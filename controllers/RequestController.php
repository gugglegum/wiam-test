<?php

declare(strict_types=1);

namespace app\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\Response;
use yii\web\BadRequestHttpException;
use app\models\Request;

class RequestController extends Controller
{
    /**
     * Configuring behavior to return JSON responses.
     * @return array
     */
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['contentNegotiator']['formats']['application/json'] = Response::FORMAT_JSON;
        return $behaviors;
    }

    /**
     * Create a new request by handling POST request /requests
     *
     * @return array
     * @throws BadRequestHttpException
     */
    public function actionCreate(): array
    {
        Yii::info("Start creating request");

        $payload = Yii::$app->request->post();

        if (empty($payload)) {
            throw new BadRequestHttpException('Empty POST payload');
        }

        $request = new Request();
        $request->load($payload, ''); // Loads data directly without using a nested array
        $request->status = Request::STATUS_PENDING; // Set pending status

        Yii::info("New request: " . json_encode($request->toArray()));

        try {
            if ($request->save()) {
                Yii::info("Saved successfully");

                // Set HTTP status 201 (Created)
                Yii::$app->response->statusCode = 201;

                return [
                    'result' => true,
                    'id' => $request->id,
                ];
            } else {
                Yii::info("Validation problem(s): " . json_encode($request->errors));

                // Set HTTP status 500 (Internal Server Error)
                Yii::$app->response->statusCode = 500;

                return [
                    'result' => false,
                    'error' => count($request->errors) > 0 ? $request->errors[array_key_first($request->errors)][0] : 'Unknown error',
                ];
            }
        } catch (\yii\db\Exception $e) {
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
