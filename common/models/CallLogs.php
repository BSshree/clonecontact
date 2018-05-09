<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "call_logs".
 *
 * @property int $call_id
 * @property int $user_id
 * @property int $contact_id
 * @property string $name
 * @property string $number
 * @property string $time
 * @property string $duration
 * @property string $call_type
 * @property int $status
 *
 * @property Contacts $contact
 * @property Users $user
 */
class  CallLogs extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    
    public $search_log;
    public $from;
    public $to;
    
    public static function tableName()
    {
        return 'call_logs';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'number', 'time', 'duration', 'call_type'], 'required'],
            [['user_id', 'contact_id', 'status'], 'integer'],
            [['time'], 'safe'],
            [['name', 'duration'], 'string', 'max' => 94],
            [['number', 'call_type'], 'string', 'max' => 64],
            [['contact_id'], 'exist', 'skipOnError' => true, 'targetClass' => Contacts::className(), 'targetAttribute' => ['contact_id' => 'contact_id']],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::className(), 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'call_id' => 'Call ID',
            'user_id' => 'User ID',
            'contact_id' => 'Contact ID',
            'name' => 'Name',
            'number' => 'Number',
            'time' => 'Time',
            'duration' => 'Duration',
            'call_type' => 'Call Type',
            'status' => 'Status',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContact()
    {
        return $this->hasOne(Contacts::className(), ['contact_id' => 'contact_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(Users::className(), ['id' => 'user_id']);
    }
}
