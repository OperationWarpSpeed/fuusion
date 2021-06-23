<?php

class BackupRestoreDB extends Ruckusing_Migration_Base
{
    public function up()
    {
        $backups = $this->create_table("backups");
        $backups->column('type', 'integer', array('limit'=>1));
        $backups->column('name', 'string', array('limit'=>32));
        $backups->column('filename', 'string', array('limit'=>32));
        $backups->column('created', 'integer', array('limit'=>11));
        $backups->column('accessed', 'integer', array('limit'=>11));
        $backups->finish();
    }//up()

    public function down()
    {
    }//down()
}
