<?php
if(isset($_FILES['image']))
{
	$internal_error = false;

	if($_FILES['image']['error'] == 4)
	{
		$internal_error = true; //no file selected
		echo 'NO FILE SELECTED';
	}

	if($internal_error !== true && $_FILES['image']['size'] > 10485760) // 10mb
	{
		$internal_error = true; //too big
		echo 'TOO BIG';
	}

	$time = time();

	require('classes/class.Image.php');

	$Image = new Image();

	if($internal_error !== true && $Image->checkFileType($_FILES['image']['type'],$_FILES['image']['tmp_name']) === false)
	{
		$internal_error = true; //invalid filetype
		echo 'INVALID FILETYPE';
	}

	if($internal_error === false)
	{
		$time = time();

		require('classes/class.MetaData.php');

		$MetaData = new MetaData();

		$MetaDataData = new MetaDataData();

		$MetaDataData->Title = $_POST['title'];
		$MetaDataData->Description = $_POST['description'];
		$MetaDataData->Keywords = $_POST['keywords'];
		$MetaDataData->DateCreated = $time;
		$MetaDataData->CreatedBy = 1;
		$MetaDataData->DateUpdated = $time;
		$MetaDataData->UpdatedBy = 1;

		$MetaData->insert($MetaDataData);

		$ImageData = new ImageData();

		$ImageData->ImageCategoryID = 1;
		$ImageData->MetaDataID = $MetaDataData->MetaDataID;
		$ImageData->FileName = $Image->generateFileName();
		$ImageData->OldFileName = $_FILES['image']['name'];
		$ImageData->FileType = $_FILES['image']['type'];
		$ImageData->TmpName = $_FILES['image']['tmp_name'];
		$ImageData->Error = $_FILES['image']['error'];
		$ImageData->Size = $_FILES['image']['size'];
		$ImageData->IsPrivate = 1;
		$ImageData->IsDeleted = 0;
		$ImageData->DateCreated = $time;
		$ImageData->CreatedBy = 1;
		$ImageData->DateUpdated = $time;
		$ImageData->UpdatedBy = 1;

		$Image->insert($ImageData);

		$extension = explode('.',strtolower($_FILES['image']['name']));
		@move_uploaded_file($_FILES['image']['tmp_name'],'uploaded/'.$ImageData->FileName.'.'.$extension[(count($extension) - 1)]);

		header('Location: uploaded/'.$ImageData->FileName.'.'.$extension[(count($extension) - 1)]);
	}
}
?>