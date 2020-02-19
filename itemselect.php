<?php
//include the database
include 'functions.php';
$srctrunk = $database->select("cdr", "dstrunk",[
	'GROUP' => 'dstrunk'
]);
 
$disposition = $database->select("cdr", "disposition",[
	'GROUP' => 'disposition'
]);

$calltype = $database->select("cdr", "calltype",[
	'GROUP' => 'calltype'
]);
$average = ["srctrunk"=>$srctrunk, "disposition"=>$disposition, "calltype"=>$calltype];
echo json_encode($average);

?>