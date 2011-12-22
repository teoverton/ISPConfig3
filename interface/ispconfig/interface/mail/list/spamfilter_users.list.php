<?php

/*
	Datatypes:
	- INTEGER
	- DOUBLE
	- CURRENCY
	- VARCHAR
	- TEXT
	- DATE
*/



// Name of the list
$liste["name"] 				= "spamfilter_users";

// Database table
$liste["table"] 			= "spamfilter_users";

// Index index field of the database table
$liste["table_idx"]			= "id";

// Search Field Prefix
$liste["search_prefix"] 	= "search_";

// Records per page
$liste["records_per_page"] 	= 15;

// Script File of the list
$liste["file"]				= "spamfilter_users_list.php";

// Script file of the edit form
$liste["edit_file"]			= "spamfilter_users_edit.php";

// Script File of the delete script
$liste["delete_file"]		= "spamfilter_users_del.php";

// Paging Template
$liste["paging_tpl"]		= "templates/paging.tpl.htm";

// Enable auth
$liste["auth"]				= "yes";


/*****************************************************
* Suchfelder
*****************************************************/

$liste["item"][] = array(	'field'		=> "local",
							'datatype'	=> "VARCHAR",
							'formtype'	=> "SELECT",
							'op'		=> "=",
							'prefix'	=> "",
							'suffix'	=> "",
							'width'		=> "",
							'value'		=> array('Y' => "<div id=\"ir-Yes\" class=\"swap\"><span>Yes</span></div>", 'N' => "<div class=\"swap\" id=\"ir-No\"><span>No</span></div>"));


$liste["item"][] = array(	'field'		=> "server_id",
							'datatype'	=> "VARCHAR",
							'formtype'	=> "SELECT",
							'op'		=> "like",
							'prefix'	=> "%",
							'suffix'	=> "%",
							'datasource'	=> array ( 	'type'	=> 'SQL',
														'querystring' => 'SELECT server_id,server_name FROM server WHERE {AUTHSQL} ORDER BY server_name',
														'keyfield'=> 'server_id',
														'valuefield'=> 'server_name'
									 				  ),
							'width'		=> "",
							'value'		=> "");


$liste["item"][] = array(	'field'		=> "priority",
							'datatype'	=> "VARCHAR",
							'formtype'	=> "SELECT",
							'op'		=> "=",
							'prefix'	=> "",
							'suffix'	=> "",
							'width'		=> "",
							'value'		=> array(1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6, 7 => 7, 8 => 8, 9 => 9, 10 => 10));

$liste["item"][] = array(	'field'		=> "policy_id",
							'datatype'	=> "VARCHAR",
							'formtype'	=> "SELECT",
							'op'		=> "like",
							'prefix'	=> "%",
							'suffix'	=> "%",
							'datasource'	=> array ( 	'type'	=> 'SQL',
														'querystring' => 'SELECT id,policy_name FROM spamfilter_policy WHERE {AUTHSQL} ORDER BY policy_name',
														'keyfield'=> 'id',
														'valuefield'=> 'policy_name'
									 				  ),
							'width'		=> "",
							'value'		=> "");

$liste["item"][] = array(	'field'		=> "fullname",
							'datatype'	=> "VARCHAR",
							'formtype'	=> "TEXT",
							'op'		=> "like",
							'prefix'	=> "%",
							'suffix'	=> "%",
							'width'		=> "",
							'value'		=> "");


?>