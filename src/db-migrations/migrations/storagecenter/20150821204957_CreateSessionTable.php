<?php

class CreateSessionTable extends Ruckusing_Migration_Base
{
    public function up()
    {
	$sessions = $this->create_table("sessions", array('id'=>false));
	$sessions->column('guid', 'string', array('primary_key'=>true, 'limit'=>40));
	$sessions->column('ip', 'string', array('limit'=>128));
	$sessions->column('userid', 'string', array('limit'=>11));
	$sessions->column('session', 'text');
	$sessions->column('lastPage', 'string');
	$sessions->column('date', 'integer');

	$sessions->finish();
    }//up()

    public function down()
    {
	$sessions = $this->drop_table("sessions");
//	$sessions->finish();
    }//down()
}
