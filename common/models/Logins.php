<?php

namespace common\models;

use Yii;
use yii\db\ActiveQuery;
use yii\web\IdentityInterface;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%logins}}".
 *
 * @property integer $login_id
 * @property integer $user_id
 * @property string $username
 * @property string $auth_key
 * @property string $password_hash
 * @property string $password_reset_token
 * @property string $email
 * @property integer $status
 * @property integer $created_at    
 * @property integer $updated_at
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $deleted_at
 *
 * @property Users $user
 */
class Logins extends RActiveRecord implements IdentityInterface {

    const STATUS_ACTIVE = 1;
    const FRONT_LOGIN = 1;
    const BACK_LOGIN = -1;

    /**
     * @inheritdoc
     */
    public $old_pass;
    public $new_pass;
    public $confirm_pass;

    public static function tableName() {
        return '{{%logins}}';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
                [['user_id', 'username', 'password_hash', 'email'], 'required'],
                [['username', 'email'], 'required', 'on' => 'update'],
                [['old_pass', 'new_pass', 'confirm_pass'], 'required', 'on' => 'changepassword'],
                ['old_pass', 'findPasswords', 'on' => 'changepassword'],
                [['user_id', 'status', 'created_at', 'updated_at', 'created_by', 'updated_by', 'deleted_at'], 'integer'],
                [['username', 'password_hash', 'password_reset_token', 'email'], 'string', 'max' => 255],
                ['confirm_pass', 'compare', 'compareAttribute' => 'new_pass', 'on' => 'changepassword'],
                [['auth_key'], 'string', 'max' => 32],
                [['username'], 'unique'],
                [['email'], 'unique'],
                [['email'], 'email'],
                [['password_reset_token'], 'unique'],
                [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::className(), 'targetAttribute' => ['user_id' => 'user_id']],
        ];
    }

    public function scenarios() {
        $scenarios = parent::scenarios();
        $scenarios['update'] = ['username', 'email']; //Scenario Values Only Accepted
        $scenarios['changepassword'] = ['old_pass', 'new_pass', 'confirm_pass']; //Scenario Values Only Accepted
        return $scenarios;
    }

    public function findPasswords($attribute, $params) {
        $user = Logins::findOne(Yii::$app->user->getId());
        $password = $user->password_hash;
        if (!Yii::$app->security->validatePassword($this->old_pass, $password))
            $this->addError($attribute, 'Old password is incorrect');
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'login_id' => 'Login ID',
            'user_id' => 'User ID',
            'username' => 'Username',
            'auth_key' => 'Auth Key',
            'password_hash' => 'Password',
            'password_reset_token' => 'Password Reset Token',
            'email' => 'Email',
            'status' => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'created_by' => 'Created By',
            'updated_by' => 'Updated By',
            'deleted_at' => 'Deleted At',
            'old_pass' => 'Old Password',
            'new_pass' => 'New Password',
            'confirm_pass' => 'Confirm Password',
        ];
    }

    public function authenticate() {
        $userinfo = Users::findEmail($this->email);
        if ($userinfo === null):
            $this->addError('email', "email address not exist");  // Error Code : 1         
        else:
            $randpass = self::getRandomString(8);
            $hash = Yii::$app->security->generatePasswordHash($randpass);
            $userinfo->password_hash = $hash;
            $userinfo->save();
            $toemail = $userinfo->email;
            return Yii::$app->mailer->compose()
                            ->setFrom('noreply@elite.in')
                            ->setTo($toemail)
                            ->setSubject('Elite.in - Request to reset admin password')
                            ->setHtmlBody('Hi, <br/><br/> We have received your request to reset your password.<br/><br/> Please note that the new password is  <b>' . $randpass . '</b> which is to be used to login <br/><br/>Thanks, <br/><br/> Elite Supporting Team.')
                            ->send();
        endif;
    }

    public static function getRandomString($length = 9) {
        $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890"; //length:36
        $final_rand = '';
        for ($i = 0; $i < $length; $i++) {
            $final_rand .= $chars[rand(0, strlen($chars) - 1)];
        }
        return $final_rand;
    }

    public static function findEmail($email) {
        $login_table = self::tableName();
        $login = self::find()
                ->joinWith('user')
                ->where([
                    "{$login_table}.email" => $email,
                    "{$login_table}.status" => self::STATUS_ACTIVE
                ])
                ->one();
        return $login;
    }

    /**
     * @return ActiveQuery
     */
    public function getUser() {
        return $this->hasOne(Users::className(), ['user_id' => 'user_id']);
    }

    /**
     * @inheritdoc
     * @return LoginsQuery the active query used by this AR class.
     */
//    public static function find() {
//        return new LoginsQuery(get_called_class());
//    }

    /**
     * @inheritdoc
     */
    public static function findIdentity($id) {
        return static::findOne(['login_id' => $id, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null) {
        return static::findOne(['auth_key' => $token]);
    }

    /**
     * Finds user by username
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username) {
        return static::findOne(['username' => $username, 'status' => self::STATUS_ACTIVE]);
    }

    public static function findByEmail($email, $login_from) {
        $login_table = self::tableName();
        $users_table = Users::tableName();

        if ($login_from == self::FRONT_LOGIN) {
            $user_type_cond = "{$users_table}.user_type_id != " . UserTypes::AD_USER_TYPE;
        } else {
            $user_type_cond = "{$users_table}.user_type_id = " . UserTypes::AD_USER_TYPE;
        }
        $login = self::find()
                ->joinWith('user')
                ->where([
                    "{$login_table}.email" => $email,
                    "{$login_table}.status" => self::STATUS_ACTIVE
                ])
                ->andWhere($user_type_cond)
                ->one();
        return $login;
    }

    /**
     * Finds user by password reset token
     *
     * @param string $token password reset token
     * @return static|null
     */
    public static function findByPasswordResetToken($token) {
        if (!static::isPasswordResetTokenValid($token)) {
            return null;
        }

        return static::findOne([
                    'password_reset_token' => $token,
                    'status' => self::STATUS_ACTIVE,
        ]);
    }

    /**
     * Finds out if password reset token is valid
     *
     * @param string $token password reset token
     * @return bool
     */
    public static function isPasswordResetTokenValid($token) {
        if (empty($token)) {
            return false;
        }

        $timestamp = (int) substr($token, strrpos($token, '_') + 1);
        $expire = Yii::$app->params['user.passwordResetTokenExpire'];
        return $timestamp + $expire >= time();
    }

    /**
     * @inheritdoc
     */
    public function getId() {
        return $this->getPrimaryKey();
    }

    /**
     * @inheritdoc
     */
    public function getUserId() {
        return $this->user_id;
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey() {
        return $this->auth_key;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey) {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return bool if password provided is valid for current user
     */
    public function validatePassword($password) {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password) {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey() {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    /**
     * Generates new password reset token
     */
    public function generatePasswordResetToken() {
        $this->password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    /**
     * Removes password reset token
     */
    public function removePasswordResetToken() {
        $this->password_reset_token = null;
    }

    /**
     * @inheritdoc
     * Get Logged in User Type Id 
     */
    public function getUserTypeId() {
        return $this->user->user_type_id;
    }

}
