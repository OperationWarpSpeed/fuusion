<?php

class DataStoreIdType extends Ruckusing_Migration_Base
{
    public function up()
    {
	$this->change_column("user_data", "id", "string", array('limit' => 40));
    }//up()

    public function down()
    {
	$this->change_column("user_data", "id", "int", array());
    }//down()
}
