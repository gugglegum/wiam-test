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
            $this->stdout("The user {$username} was successfully created.\n");
            return ExitCode::OK;
        } else {
            $this->stderr("Error when creating a user:\n");
            foreach ($user->errors as $attribute => $errors) {
                $this->stderr(" - {$attribute}: " . implode(', ', $errors) . "\n");
            }
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }
}
