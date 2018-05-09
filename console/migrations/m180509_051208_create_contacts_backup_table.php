<?php

use yii\db\Migration;

/**
 * Handles the creation of table `contacts_backup`.
 */
class m180509_051208_create_contacts_backup_table extends Migration {

    /**
     * {@inheritdoc}
     */
    const CONTACTS_BACKUP_TABLE = '{{%contacts_backup}}';
    const USERS_TABLE = '{{%users}}';

    public function safeUp() {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable(self::CONTACTS_BACKUP_TABLE, [
            'file_id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'file_name' => $this->string(255)->Null(),
            'status' => $this->smallInteger()->notNull()->defaultValue(1),
        ], $tableOptions);
        
        
         $this->createIndex(
                'idx-contacts_backup-user_id', self::CONTACTS_BACKUP_TABLE, 'user_id'
        );

        $this->addForeignKey(
                'fk-contacts_backup-user_id', self::CONTACTS_BACKUP_TABLE, 'user_id', self::USERS_TABLE, 'id', 'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown() {
        
         $this->dropForeignKey(
                'fk-contacts_backup-user_id', self::CONTACTS_BACKUP_TABLE
        );

        // drops index for column `author_id`
        $this->dropIndex(
                'idx-contacts_backup-user_id', self::CONTACTS_BACKUP_TABLE
        );
        $this->dropTable(self::CONTACTS_BACKUP_TABLE);
    }

}
