<?php

require_once dirname(__FILE__).'/../src/SitraApi.php';

//Initialize SitraApi
$api = new SitraApi('eSfPucIJ', '1019');

//Perform the search
$api->start()
	->selectionIds(array('25438'))
	->count(200);

//Populate query with data
foreach( $_GET as $name => $value ) {
	if( is_string($value) ) {
		$tmp = json_decode($value);
		$value = $tmp==null?$value:$tmp;
	}
	call_user_func(array($api, $name), $value);
}

$items = $api->search();

$criteria = $api->getCriteria();
$total = $api->getNumFound();
$retrieved = count($items);

//If there are more objects than the first retrieved, we get all
while( count($items) != $total ) {
	$tmp = $api->start()->raw($criteria)
		->first($retrieved)
		->search();

	$retrieved += count($tmp);
	foreach( $tmp as $result ) {
		$items[] = $result;
	}
}

//Then dump JSON
header('Content-type: application/json');
echo json_encode($items);