<?php

class CreateUserTable extends Ruckusing_Migration_Base
{
    public function up()
    {
	$users = $this->create_table("users");
	$users->column('username', 'string', array('limit'=>32));
	$users->column('password', 'string', array('limit'=>256));
	$users->column('name', 'string', array('limit'=>96));
	$users->column('email', 'string', array('limit'=>96));
	$users->column('level', 'tinyinteger'); // user security level
	$users->column('uid', 'string', array('limit'=>11));
	$users->column('gid', 'string', array('limit'=>11));
	$users->column('ip', 'string', array('limit'=>128));
	$users->column('profile', 'text'); // serialized array of user profile settings
	$users->column('api_keys', 'text'); // serialized array of API keys
	$users->column('created', 'integer', array('limit'=>11));
	$users->column('updated', 'integer', array('limit'=>11));
	$users->column('lastseen', 'string', array('limit'=>11));
	$users->column('session_id', 'string', array('limit'=>11));
	$users->column('session_hash', 'text');
	$users->column('session_saved', 'text');
	$users->column('active', 'tinyinteger');
	$users->finish();
    }//up()

    public function down()
    {
//	$users = $this->drop_table("users");
//	Maybe this should never be down-ed! ;-)
    }//down()
}
