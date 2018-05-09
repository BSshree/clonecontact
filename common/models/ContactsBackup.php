<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "contacts_backup".
 *
 * @property int $file_id
 * @property int $user_id
 * @property string $file_name
 * @property int $status
 *
 * @property Users $user
 */
class ContactsBackup extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'contacts_backup';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            //[['user_id'], 'required'],
            [['user_id', 'status'], 'integer'],
            [['file_name'], 'string', 'max' => 255],
             //[['file_name'], 'file', 'skipOnEmpty' => false, 'extensions' => 'csv, xls, xlsv, vcf, txt'],
            //[['file_name'], 'file', 'extensions'=>'csv, xls, xlsv, vcf, txt'],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::className(), 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'file_id' => 'File ID',
            'user_id' => 'User ID',
            'file_name' => 'File Name',
            'status' => 'Status',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(Users::className(), ['id' => 'user_id']);
    }
}
