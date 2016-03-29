<?php
	include('class/nubodesk.php');

	$config = array(
		'appKey' => '[APP_KEY]', 
		'secret' => '[APP_SECRET]'
	);

	$nubodesk = new Nubodesk($config);

	// set token and secret
	$nubodesk->setToken('[ACCESS_TOKEN]', '[ACCESS_TOKEN_SECRET]');

	// 
	// 
	// insert contact into api
	// 
	// 
	$field = array(
		'name' => 'Meu contato SDK PHP',
		'email' => 'contact@email.com',
		'phones' => array(
			array(
				'ds_phone' => '(99) 9999-99999',
				'type_phone' => array('id_type_phone' => 1)
			)
		),
		'address' => array(
			array(
				'type_address' => array('id_type_address' => 1),
				'address' => 'Street, 2200 - City, UF - Country',
				'addition' => 'Near the gas stationp'
			)
		),
		'categories' => array(
			array('id_contact_categorie' => 1)
		),
		'people' => array(
			array(
				'name' => 'People 1',
				'email' => 'people@email.com'
			)
		),
		// custom fields for de contact
		'contact_fields' => json_encode(array('id_local' => 1, 'other_field_important' => 'data'))
	);
	$res = $nubodesk->api('contacts', 'POST', $field);

	// 
	// 
	// update contact into api
	// 
	// 
	$field = array(
		'email' => 'contact@email.com',
		'phones' => array(
			array(
				'ds_phone' => '(99) 9999-99999',
				'type_phone' => array('id_type_phone' => 1)
			)
		),
		'address' => array(
			array(
				'type_address' => array('id_type_address' => 1),
				'address' => 'Street, 2200 - City, UF - Country',
				'addition' => 'Near the gas stationp'
			)
		),
		'categories' => array(
			array('id_contact_categorie' => 1)
		),
		'people' => array(
			array(
				'name' => 'People 1',
				'email' => 'people@email.com'
			)
		),
		// custom fields for de contact
		'contact_fields' => json_encode(array('id_local' => 1, 'other_field_important' => 'data'))
	);
	// $res = $nubodesk->api('contacts/[ID_CONTACT]', 'PUT', $field);


	//
	//
	// get list contacts
	// 
	// 
	// $res = $nubodesk->api('contacts/[ID_CONTACT]');
	// 
	// show
	print_r($res);
?>