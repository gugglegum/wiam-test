<?php

declare(strict_types=1);

namespace app\commands;

use Yii;
use yii\base\Exception;
use yii\console\Controller;
use yii\console\ExitCode;
use app\models\User;

class UserController extends Controller
{
    /**
     * Creates a user with the specified data
     *
     * @param string $username User name
     * @param string $email Email
     * @param string $password password
     * @return int Exit code
     * @throws Exception
     */
    public function actionCreate(string $username, string $email, string $password): int
    {
        $user = new User();
        $user->username = $username;
        $user->email = $email;
        $user->password_hash = Yii::$app->security->generatePasswordHash($password);
        $user->created_at = time();
        $user->updated_at = null;

        if ($user->save()) {
            Yii::info("The user {$username} was successfully created.");
            return ExitCode::OK;
        } else {
            $errorMsg = "Error when creating a user:\n";
            foreach ($user->errors as $attribute => $errors) {
                $errorMsg .= " - {$attribute}: " . implode(', ', $errors) . "\n";
            }
            Yii::error($errorMsg);
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }
}
