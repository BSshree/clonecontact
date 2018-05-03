<?php

use yii\db\Migration;

/**
 * Handles the creation of table `users`.
 */
class m180425_045846_create_users_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    
     const USERS_TABLE = '{{%users}}';
     
    public function safeUp()
    {
         $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }
        $this->createTable(self::USERS_TABLE, [
            'id' => $this->primaryKey(),
            'username' => $this->string()->Null(),
            'auth_key' => $this->string(32)->Null(),
             'password' => $this->string()->notNull(),
            'email' => $this->string(64)->notNull()->unique(),
            'profile_image' => $this->string(255)->Null(),
            'status' => $this->smallInteger()->notNull()->defaultValue(1),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
         $this->dropTable(self::USERS_TABLE);
    }
}
