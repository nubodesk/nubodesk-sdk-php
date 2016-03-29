<?php
	include('class/nubodesk.php');

	$config = array(
		'appKey' => '0p46YAbh7Rq8vBOXYQaNu86pI690DVj7yQgEUoQm54r3KewQLjvabJxrGSi2hfjb', 
		'secret' => 'mwWXa5l9pLaohR2cPBVZ7wRLTbH5pLkEjV2FKymsCVTiwSeRt6wLGf85SsgNvKM4gahEYZydu9IfdgSL4E0mAkyr2d2I4Z5px4sJay6u9jAuwvcAegEeiw6dim7iUGJZ'
	);

	$nubodesk = new Nubodesk($config);
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<title>Exemplo de integração com API Nubodesk v0.0.1</title>
</head>
<body>
	<?php
	if ($nubodesk->hasToken()) {
		$token = $nubodesk->getToken();
		echo 'Salvar o token e o secret no seu banco de dados para acesso futuro <br>';
		echo $token['token'],"<br>",$token['secret'],"<br>";
	?>
		Nubodesk foi autorizado!
	<?php } else { ?>
		<a href="<?=$nubodesk->getAuthorizeURL()?>">Autorizar o acesso ao Nubodesk</a>
	<?php } ?>
</body>
</html>