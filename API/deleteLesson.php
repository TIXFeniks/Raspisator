<?php
	require_once('../Dbconfig.php'); 
	echo json_encode($user->delete_lesson($_POST['lesson_id']));
?>