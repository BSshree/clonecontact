<?php

use yii\db\Migration;

/**
 * Handles the creation of table `contacts`.
 */
class m180426_065659_create_contacts_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    
    const CONTACTS_TABLE = '{{%contacts}}';
    const USERS_TABLE = '{{%users}}';
    
    public function safeUp()
    {
         $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }
        $this->createTable(self::CONTACTS_TABLE, [
            'contact_id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'name' => $this->string(64)->notNull()->unique(),
            'mobile_no' => $this->string(64)->notNull()->unique(),
         ], $tableOptions);
        
         $this->createIndex(
                'idx-users-user_id', self::CONTACTS_TABLE, 'user_id'
        );

        // add foreign key for table `el_user_types`
        $this->addForeignKey(
                'fk-users-user_id', self::CONTACTS_TABLE, 'user_id', self::USERS_TABLE, 'contact_id', 'CASCADE'
        );
        
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable(self::CONTACTS_TABLE);
    }
}
