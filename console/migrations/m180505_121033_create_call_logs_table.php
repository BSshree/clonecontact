<?php

use yii\db\Migration;

/**
 * Handles the creation of table `call_logs`.
 */
class m180505_121033_create_call_logs_table extends Migration {

    /**
     * {@inheritdoc}
     */
    const CALL_LOGS_TABLE = '{{%call_logs}}';
    const USERS_TABLE = '{{%users}}';
    const CONTACTS_TABLE = '{{%contacts}}';

    public function safeUp() {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }
        $this->createTable(self::CALL_LOGS_TABLE, [
            'call_id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'contact_id' => $this->integer()->notNull(),
            'name' => $this->string(94)->Null(),
            'number' => $this->string(64)->notNull(),
            'time' => $this->dateTime()->notNull(),
            'duration' => $this->string(94)->notNull(),
            'call_type' => $this->string(64)->notNull(),
            'status' => $this->smallInteger()->notNull()->defaultValue(1),
        ]);
        $this->createIndex(
                'idx-call_logs-contact_id', self::CALL_LOGS_TABLE, 'contact_id'
        );

        // add foreign key for table `user`
        $this->addForeignKey(
                'fk-call_logs-contact_id', self::CALL_LOGS_TABLE, 'contact_id', self::CONTACTS_TABLE, 'contact_id', 'CASCADE'
        );

        $this->createIndex(
                'idx-call_logs-user_id', self::CALL_LOGS_TABLE, 'user_id'
        );

        // add foreign key for table `user`
        $this->addForeignKey(
                'fk-call_logs-user_id', self::CALL_LOGS_TABLE, 'user_id', self::USERS_TABLE, 'id', 'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown() {
        $this->dropForeignKey(
                'fk-call_logs-contact_id', self::CALL_LOGS_TABLE
        );

        // drops index for column `author_id`
        $this->dropIndex(
                'idx-call_logs-contact_id', self::CALL_LOGS_TABLE
        );

        $this->dropForeignKey(
                'fk-call_logs-user_id', self::CALL_LOGS_TABLE
        );

        // drops index for column `author_id`
        $this->dropIndex(
                'idx-call_logs-user_id', self::CALL_LOGS_TABLE
        );



        $this->dropTable(self::CALL_LOGS_TABLE);
    }

}
