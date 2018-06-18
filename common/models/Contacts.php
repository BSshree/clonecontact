<?php

namespace common\models;
use yii2tech\ar\softdelete\SoftDeleteBehavior;
use Yii;

/**
 * This is the model class for table "contacts".
 *
 * @property int $contact_id
 * @property int $user_id
 * @property string $name
 * @property string $mobile_no
 *
 * @property Users $user
 */
class Contacts extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public $search_by;
    public $import_phone;
    
    public static function tableName()
    {
        return 'contacts';
    }

    public function behaviors() {
        return [
            'softDeleteBehavior' => [
                'class' => SoftDeleteBehavior::className(),
                'softDeleteAttributeValues' => [
                    'isDeleted' => true
                ],
                'replaceRegularDelete' => true // mutate native `delete()` method
            ],
        ];
        return [
            TimestampBehavior::className(),
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'mobile_no'], 'required'],
            [['user_id'], 'integer'],
            [['name', 'mobile_no'], 'string', 'max' => 64],
            //[['name'], 'unique'],
           // [['mobile_no'], 'unique'],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::className(), 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'contact_id' => 'Contact ID',
            'user_id' => 'User ID',
            'name' => 'Name',
            'mobile_no' => 'Mobile No',
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
