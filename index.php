<?php
require('connect.php');
require('class.MarySQL.php');

$sql = new MarySQL();


$data = $sql->findFirst(array(
	'table' => 'users',
	'fields' => array('id','username'),
	'conditions' => array('username'=>':username','password'=>':password'),
	'order' => 'id ASC',
	'join'  => array('inner',array('roles'=>'users.role_id=roles.role_id'))
	'limit' => 10
),array(':username'=>'user',':password'=>'otherpass'));

?>

<pre><?php print_r($data); ?></pre>