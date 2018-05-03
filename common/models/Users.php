<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "users".
 *
 * @property int $id
 * @property string $username
 * @property string $auth_key
 * @property string $password_hash
 * @property string $email
 * @property string $profile_image
 * @property int $status
 */
class Users extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'users';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['password', 'email'], 'required'],
            [['status'], 'integer'],
            [['username', 'password', 'profile_image'], 'string', 'max' => 255],
            [['auth_key'], 'string', 'max' => 32],
            [['email'], 'string', 'max' => 64],
            [['email'], 'unique'],
            ['email', 'email'],
        ];
    }

     public function scenarios() {
        $scenarios = parent::scenarios();
        $scenarios['update'] = ['username', 'email']; //Scenario Values Only Accepted
        $scenarios['changepassword'] = ['old_pass', 'new_pass', 'confirm_pass']; //Scenario Values Only Accepted
        return $scenarios;
    }
    
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'username' => 'Username',
            'auth_key' => 'Auth Key',
            'password' => 'Password',
            'email' => 'Email',
            'profile_image' => 'Profile Image',
            'status' => 'Status',
        ];
    }
    
     public function setPassword($password)
    {
        return Yii::$app->security->generatePasswordHash($password);
    }
    
     public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password);
    }

    public function generateAuthKey()
    {
        return  Yii::$app->security->generateRandomString();
    }
    
    public static function findByEmail($email)
    {
        return static::findOne(['email' => $email, 'status' => self::STATUS_ACTIVE]);
    }
}
