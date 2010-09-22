<?php
require_once('class.db.php');

class UserData
{
	var $UserID; //primary key
	var $Username;
	var $Password;
	var $IPAddress;
	var $LastLogin;
	var $DateCreated;
	var $CreatedBy;
	var $DateUpdated;
	var $UpdatedBy;
}


class User {
	private $table_name       = 'tbl_User';
	private $required_fields  = array( 'UserID', 'Username', 'Password', 'IPAddress', 'LastLogin', 'DateCreated', 'CreatedBy', 'DateUpdated', 'UpdatedBy' );

	public function __construct()
	{
	}

	function insert(&$obj)
	{
		if (!is_object($obj))
		{
			return false;
		}

		$return_val = false;

		$db = new db();

		$object_vars = get_object_vars($obj);
		array_shift($object_vars);

		$fields = '';
		$values = '';

		//array shift the primary key
		foreach ($object_vars as $key => $value)
		{
			$fields .= $key . ",";
			$values .= "'" . $db->escape($value) . "',";
		}
		$fields = rtrim($fields,',');
		$values = rtrim($values,',');

		$sql = "INSERT INTO " . $this->table_name . " (" . $fields . ") VALUES (" . $values . ")";

		if ($result = $db->query($sql))
		{
			$obj->UserID = $db->insert_id;
			$return_val = true;
		}

		return $return_val;

	}

	function update($obj)
	{
		if (!is_object($obj)) {
			return false;
		}

		$return_val = false;
		
		$db = new db();

		$object_vars = get_object_vars($obj);
		$shifted = array_shift($object_vars);

		foreach ($object_vars as $key => $value)
		{
			$set_clause .= " " . $key . " = '" . $db->escape($value) . "',";
		}
		$set_clause = rtrim($set_clause,',');
		
		$sql = "UPDATE " . $this->table_name . " SET " . $set_clause . " WHERE UserID = '" . $db->escape($obj->UserID) . "'";

		if ($db->query($sql))
		{
			$return_val = true;
		}

		return $return_val;
	}

	function getOne($user_id)
	{
		if (!is_numeric($user_id)) { return false; }

		$return_val = false;
		
		$db = new db();

		foreach (array_keys(get_class_vars('UserData')) as $key)
		{
			$fields .= " " . $key . ",";
		}
		$fields = rtrim($fields,',');
		
		$sql = "SELECT " . $fields . " FROM " . $this->table_name . " WHERE UserID = '" . $db->escape($user_id) . "'";

		if($result = $db->get_row($sql))
		{
			$return_val = $result;
		}

		return $return_val;
	}

	function getAll($orderby='UserID')
	{
		$db = new db();

		foreach (array_keys(get_class_vars('UserData')) as $key)
		{
			$fields .= " " . $key . ",";
		}
		$fields = rtrim($fields,',');
		
		$sql = "SELECT " . $fields . " FROM " . $this->table_name . " ORDER BY ".$orderby;
		
		return $db->get_results($sql);
	}
}
?>