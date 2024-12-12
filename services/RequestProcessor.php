<?php

declare(strict_types=1);

namespace app\services;

use app\models\User;
use Throwable;
use Yii;
use app\models\Request;
use yii\db\Exception as DbException;

/**
 * RequestProcessor
 */
class RequestProcessor implements RequestProcessorInterface
{
    /**
     * Process single request
     *
     * Returns TRUE if the request was processed successfully, FALSE if the request was skipped because this request or
     * another request from the same user is already being processed by another process. In case of an error, an
     * exception is thrown.
     *
     * @param Request $request Request to process
     * @param int $delay OPTIONAL Delay in seconds
     * @return bool TRUE if the request processed successfully, FALSE if skipped due to the blocking by another process
     * @throws DbException When database error occur
     * @throws Throwable
     */
    public function processRequest(Request $request, int $delay = 0): bool
    {
        // Starting the transaction
        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Attempting to lock a user row with FOR UPDATE NOWAIT
            try {
                $sql = User::find()->select('id')->where(['id' => $request->user_id])->createCommand()->getRawSql() . ' FOR UPDATE NOWAIT';
                Yii::$app->db->createCommand($sql)->execute();
            } catch (DbException $e) {
                $transaction->rollBack();
                // Check error code for blocking FOR UPDATE NOWAIT (Lock Not Available)
                if (isset($e->errorInfo[0]) && $e->errorInfo[0] == '55P03') {
                    // Blocked - skipping the request
                    return false;
                } else {
                    // Other DB errors
                    throw $e;
                }
            }

            // Check if the user has any approved requests
            $hasApproved = Request::find()
                ->where(['user_id' => $request->user_id, 'status' => Request::STATUS_APPROVED])
                ->exists();

            // Emulating the delay
            sleep($delay); // This is the worst place of delay in parallel execution, but the best for debugging purposes

            if ($hasApproved) {
                // Reject the current request
                $newStatus = Request::STATUS_DECLINED;
            } else {
                // Make a decision with a 10% probability of approval
                $rand = mt_rand(1, 100);
                $newStatus = ($rand <= 10) ? Request::STATUS_APPROVED : Request::STATUS_DECLINED;
            }

            // Updating the status of the request
            $request->status = $newStatus;
            if (!$request->save(false)) { // Skip validation
                throw new DbException("Failed to update the request ID: {$request->id}");
            }

            // Commit transaction
            $transaction->commit();

            return true;
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }
}
