<?php
require_once('class.db.php');

class ImageData
{
	var $ImageID; //primary key
	var $ImageCategoryID;
	var $MetaDataID;
	var $UserID;
	var $FileName;
	var $OldFileName;
	var $FileType;
	var $TmpName;
	var $Error;
	var $Size;
	var $IsPrivate;
	var $IsDeleted;
	var $DateCreated;
	var $CreatedBy;
	var $DateUpdated;
	var $UpdatedBy;
}


class Image {
	private $table_name       = 'tbl_Image';
	private $required_fields  = array( 'ImageID', 'ImageCategoryID', 'MetaDataID', 'UserID', 'FileName', 'OldFileName', 'FileType', 'TmpName', 'Error', 'Size', 'IsPrivate', 'IsDeleted', 'DateCreated', 'CreatedBy', 'DateUpdated', 'UpdatedBy' );

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
			$obj->ImageID = $db->insert_id;
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
		
		$sql = "UPDATE " . $this->table_name . " SET " . $set_clause . " WHERE ImageID = '" . $db->escape($obj->ImageID) . "'";

		if ($db->query($sql))
		{
			$return_val = true;
		}

		return $return_val;
	}

	function getOne($image_id)
	{
		if (!is_numeric($image_id)) { return false; }

		$return_val = false;
		
		$db = new db();

		foreach (array_keys(get_class_vars('ImageData')) as $key)
		{
			$fields .= " " . $key . ",";
		}
		$fields = rtrim($fields,',');
		
		$sql = "SELECT " . $fields . " FROM " . $this->table_name . " WHERE ImageID = '" . $db->escape($image_id) . "'";

		if($result = $db->get_row($sql))
		{
			$return_val = $result;
		}

		return $return_val;
	}

	function getAll($orderby='ImageID')
	{
		$db = new db();

		foreach (array_keys(get_class_vars('ImageData')) as $key)
		{
			$fields .= " " . $key . ",";
		}
		$fields = rtrim($fields,',');
		
		$sql = "SELECT " . $fields . " FROM " . $this->table_name . " ORDER BY ".$orderby;
		
		return $db->get_results($sql);
	}

	function getOneByName($file_name)
	{
		if (strlen($file_name) == 0) { return false; }

		$return_val = false;
		
		$db = new db();

		$fields = '';

		foreach (array_keys(get_class_vars('ImageData')) as $key)
		{
			$fields .= " " . $key . ",";
		}
		$fields = rtrim($fields,',');
		
		$sql = "SELECT " . $fields . " FROM " . $this->table_name . " WHERE FileName = '" . $db->escape($file_name) . "'";

		if($result = $db->get_row($sql))
		{
			$return_val = $result;
		}

		return $return_val;
	}

	function generateFileName()
	{
		while(true)
		{
			$attempt = $this->generateName();
			if($this->getOneByName($attempt) === false)
			{
				return $attempt;
			}
		}
	}

	function generateName()
	{
		$return = '';
		$letters = range('a','z');
		for($length = 0; $length < 7; $length++)
		{
			$res = array();
			$res[] = $letters[mt_rand(0,25)];
			$res[] = mt_rand(0,9);
			$return .= $res[mt_rand(0,1)];
		}
		return strtoupper($return);
	}

	function checkFileType($filetype,$tmp_name)
	{
		switch($filetype){
			case 'image/jpg':
			case 'image/jpeg':
			case 'image/pjpeg':
				return (@imagecreatefromjpeg($tmp_name) ? true : false);
			break;
			case 'image/gif':
				return (@imagecreatefromgif($tmp_name) ? true : false);
			break;
			case 'image/png':
			case 'image/x-png':
				return (@imagecreatefrompng($tmp_name) ? true : false);
			break;
			default:
				return false;
			break;
		}
	}
}
?>