<?php

namespace app\services;

use app\models\Request;
use Yii;
use yii\db\Exception as DbException;

/**
 * RequestCreator
 */
class RequestCreator implements RequestCreatorInterface
{
    /**
     * Create request from data array
     *
     * @return array{
     *     'success': bool,
     *     'request': Request
     * }
     * @throws DbException
     */
    public function createRequest(array $payload): array
    {
        Yii::info("Start creating request");

        $request = new Request();
        $request->load($payload, ''); // Loads data directly without using a nested array
        $request->status = Request::STATUS_PENDING; // Set pending status

        Yii::info("New request: " . json_encode($request->toArray()));

        $success = $request->save();
        if ($success) {
            Yii::info("Saved successfully");
        } else {
            Yii::info("Validation problem(s): " . json_encode($request->errors, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        }
        return [
            'success' => $success,
            'request' => $request,
        ];
    }
}
