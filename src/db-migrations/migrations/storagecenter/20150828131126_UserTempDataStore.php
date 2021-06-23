<?php

class UserTempDataStore extends Ruckusing_Migration_Base
{   
    public function up()
    {   
        $user_data_store = $this->create_table("user_data", array('id'=>true));
        $user_data_store->column('segment', 'string', array('limit'=>8));
        $user_data_store->column('userid', 'string', array('limit'=>32));
        $user_data_store->column('object', 'text');
        $user_data_store->column('created', 'integer');
        $user_data_store->column('updated', 'integer');
        $user_data_store->column('expire', 'integer');
        $user_data_store->finish();
    }//up()

    public function down()
    {   
        $user_data_store = $this->drop_table("user_data");
//      $sessions->finish();
    }//down()
}
