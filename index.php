<?php
require('includes/include.index.php');
?>
<form enctype="multipart/form-data" method="post">
	<p><input name="image" type="file"> <input type="submit" value="Upload"><br>JPG, JPEG, GIF or PNG no larger than 10MB accepted.</p>
	<input name="private" id="private" type="checkbox"> <label for="private">Keep my image private from Recent Uploads.</label>
	<p>Title: <input type="text" name="title"></p>
	<p>Keywords: <input type="text" name="keywords"></p>
	<p>Description: <textarea cols="25" rows="5" name="description"></textarea></p>
</form>