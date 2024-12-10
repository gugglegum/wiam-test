<?php

declare(strict_types=1);

namespace app\controllers;

use Yii;
use yii\db\Exception as DbException;
use yii\rest\Controller;
use yii\web\Response;
use yii\web\BadRequestHttpException;
use yii\filters\ContentNegotiator;
use app\components\RequestProcessor;
use app\models\Request;

class ProcessorController extends Controller
{
    /**
     * Configuring behavior to return JSON responses.
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
     * Handle GET request /processor?delay=5
     *
     * @param int $delay
     * @return array
     * @throws BadRequestHttpException
     */
    public function actionIndex(int $delay = 0): array
    {
        Yii::info("Start processing requests");

        if (!is_numeric($delay) || $delay < 0) {
            throw new BadRequestHttpException("The delay parameter must be a non-negative integer");
        }

        $processedCount = 0;
        $skippedCount = 0;
        $approvedCount = 0;
        $declinedCount = 0;
        $previousRequestId = 0;

        $processor = new RequestProcessor();


        // Requests processing cycle
        while (true) {
            // Starting the transaction
            $transaction = Yii::$app->db->beginTransaction();
            try {
                // Retrieve one request with PENDING status
                /** @var Request $request */
                $request = Request::find()
                    ->where(['status' => Request::STATUS_PENDING])
                    ->andWhere(['>', 'id', $previousRequestId])
                    ->orderBy(['id' => SORT_ASC])
                    ->limit(1)
                    ->one();

                if (!$request) {
                    // There are no more requests to process
                    $transaction->rollBack();
                    break;
                }

                Yii::info("Process request #{$request->id} (for user #{$request->user_id})");

                // Processing the request
                $result = $processor->processRequest($request, $delay);

                if ($result) {
                    $processedCount++;
                    if ($request->status === Request::STATUS_APPROVED) {
                        $approvedCount++;
                        Yii::info("Decision: approved");
                    } elseif ($request->status === Request::STATUS_DECLINED) {
                        $declinedCount++;
                        Yii::info("Decision: declined");
                    }
                } else{
                    $skippedCount++;
                    Yii::info("Request's user #{$request->user_id} is blocked by another process - skipping the request");
                }

                $previousRequestId = $request->id;
                $transaction->commit();
            } catch (DbException $e) {
                Yii::error("Error: " . $e->getMessage());
                $transaction->rollBack();

                // If PostgreSQL error code "55P03" (Deadlock detected) or "40001" (Serialization failure) - repeat
                // the transaction with current request, otherwise skip the request and move on.
                if (!isset($e->errorInfo[0]) || !in_array($e->errorInfo[0], ['55P03', '40001'])) {
                    $previousRequestId = $request->id;
                }
                continue;
            } catch (\Exception $e) {
                Yii::error("Error: " . $e->getMessage());
                $transaction->rollBack();
                $previousRequestId = $request->id;
                continue;
            }
        }

        Yii::info("Processing completed. Total processed: {$processedCount}, skipped: {$skippedCount}, Approved: {$approvedCount}, Declined: {$declinedCount}");

        return [
            'result' => true,
        ];
    }
}
