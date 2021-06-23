<?php

class UserLastseenType extends Ruckusing_Migration_Base
{   
    public function up()
    {   
        $this->change_column("users", "lastseen", "integer", array('limit' => 11));
    }//up()

    public function down()
    {   
        $this->change_column("users", "lastseen", "string");
    }//down()
}
