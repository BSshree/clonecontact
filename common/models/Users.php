<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "users".
 *
 * @property int $id
 * @property string $username
 * @property string $auth_key
 * @property string $password
 * @property string $email
 * @property string $profile_image
 * @property int $status
 */
class Users extends \yii\db\ActiveRecord {

    /**
     * @inheritdoc
     */
    public $old_pass;
    public $new_pass;
    public $confirm_pass;

    public static function tableName() {
        return '{{%users}}';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
                [['password', 'email'], 'required', 'on' => 'register'],
                [['status'], 'integer'],
                [['username', 'password', 'profile_image'], 'string', 'max' => 255],
                [['auth_key'], 'string', 'max' => 32],
                [['email'], 'string', 'max' => 64],
                [['email'], 'unique'],
                ['email', 'email'],
                [['profile_image'], 'file'],
                [['old_pass', 'new_pass', 'confirm_pass'], 'required', 'on' => 'changepassword'],
                ['old_pass', 'findPasswords', 'on' => 'changepassword'],
                ['confirm_pass', 'compare', 'compareAttribute' => 'new_pass', 'on' => 'changepassword'],
        ];
    }

    public function scenarios() {
        $scenarios = parent::scenarios();
        //$scenarios['update'] = ['username', 'email']; //Scenario Values Only Accepted
        $scenarios['changepassword'] = ['old_pass', 'new_pass', 'confirm_pass']; //Scenario Values Only Accepted
        return $scenarios;
    }

    public function findPasswords($attribute, $params) {
        $user = Users::findOne($this->id);
        $password = $user->password;
        if (!Yii::$app->security->validatePassword($this->old_pass, $password))
            $this->addError($attribute, 'Old password is incorrect');
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
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

    public function getId() {
        return $this->getPrimaryKey();
    }

    public function verifyPassword($password) {

        $dbpassword = static ::findOne(['username' => yii::$app->user->identity->username, 'status' => self::STATUS_ACTIVE])->password;
        return yii::$app->security->validatepassword($password, $dbpassword);
    }

    public function validateoldPassword($attribute, $params) {

        if (!$this->verifyPassword($this->oldpass)) {
            $this->addError($attribute, 'Incorrect old password.');
        }
    }

    public function setPassword($password) {
        return Yii::$app->security->generatePasswordHash($password);
    }

    public function validatePassword($password) {
        return Yii::$app->security->validatePassword($password, $this->password);
    }

    public function generateAuthKey() {
        return Yii::$app->security->generateRandomString();
    }

//    public static function findByEmail($email) {
//        return static::findOne(['email' => $email, 'status' => self::STATUS_ACTIVE]);
//    }

    public function authenticate() {
        $userinfo = Users::find()->where(['email' => $this->email])->one();
        if ($userinfo === null):
            $this->addError('email', "email address not exist");  // Error Code : 1         
        else:
            $randpass = self::getRandomString(8);
            $hash = Yii::$app->security->generatePasswordHash($randpass);
            $userinfo->password = $hash;
            $userinfo->save();
            $toemail = $userinfo->email;
            return Yii::$app->mailer->compose()
                            ->setFrom('sumanasdev@gmail.com')
                            ->setTo($toemail)
                            ->setSubject('Clone Contact - Request to reset your password')
                            ->setHtmlBody('Hi, <br/><br/> We have received your request to reset your password.<br/><br/> Please note the new password  <b>' . $randpass . '</b> which is to be used to login <br/><br/>Thanks, <br/><br/> Clone Contact Team.')
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

}
