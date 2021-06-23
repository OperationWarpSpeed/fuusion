<?php

class AddSaltToUserTable extends Ruckusing_Migration_Base
{
    public function up()
    {
	$this->add_column('users', 'salt', 'string', array('limit'=>255));
    }//up()

    public function down()
    {
    }//down()
}
