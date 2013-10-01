<?php
/*
Dromaeo Test Suite
Copyright (c) 2010 John Resig

Permission is hereby granted, free of charge, to any person
obtaining a copy of this software and associated documentation
files (the "Software"), to deal in the Software without
restriction, including without limitation the rights to use,
copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the
Software is furnished to do so, subject to the following
conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
OTHER DEALINGS IN THE SOFTWARE.
*/
$db_cfg = parse_ini_file("db.ini", true);

if (array_key_exists("sqlite", $db_cfg)) {
	$db_type = "sqlite";
	$db_path = $db_cfg["sqlite"]["path"];
}
elseif (array_key_exists("mysql", $db_cfg)) {
	$db_type = "mysql";
	$server = $db_cfg["mysql"]["server"];
	$user = $db_cfg["mysql"]["user"];
	$pass = $db_cfg["mysql"]["pass"];
	$database = $db_cfg["mysql"]["database"];
}
	
require('JSON.php');

$json = new Services_JSON();

if ( $db_type == "mysql" ){
	$db = new PDO("$db_type:host=$server;dbname=$database", $username, $pass);
} else {
    $db = new PDO("$db_type:$db_path");
}

$id = str_replace(';', "", $_REQUEST['id']);

if ( $id ) {
	$sets = array();
	$ids = preg_split('/,/',$id);

	foreach ($ids as $i) {
		$results = array();

    	$query = $db->query( "SELECT * FROM runs WHERE id=$i;" );
    	$data = $query->fetchAll();

    	foreach ($db->query( "SELECT * FROM results WHERE runid=$i;" ) as $row) {
    		array_push($results, $row);
    	}

    	$data[0]['results'] = $results;
		$data[0]['ip'] = '';
		array_push($sets, $data[0]);
	}
	echo $json->encode($sets);
} else {
	$data = $json->decode(str_replace('\\"', '"', $_REQUEST['data']));
	if ( $data ) {

		if ( $db_type == "mysql" ){
			$now = "NOW()";
		} else {
			$now = "date('now')";
		}

		$db->query( sprintf("INSERT into runs VALUES(NULL,'%s','%s',$now,'%s');",
				$_SERVER['HTTP_USER_AGENT'],
				$_SERVER['REMOTE_ADDR'], 
				str_replace(';', "", $_REQUEST['style'])) );

		$id = $db->lastInsertId();
	} 
		
	if ( $id ) {
		foreach ($data as $row) {
			$db->query( sprintf("INSERT into results VALUES(NULL,'%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s');", 
				$id, 
				$row->collection,
				$row->version,
				$row->name,
				$row->scale,
				$row->median,
				$row->min,
				$row->max,
				$row->mean,
				$row->deviation,
				$row->runs) );
			}
	echo $id;
	}
}
$db = null;
?>
