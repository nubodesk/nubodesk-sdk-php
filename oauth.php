<?php
	include('class/nubodesk.php');

	$config = array(
		'appKey' => '[APP_KEY]', 
		'secret' => '[APP_SECRET]'
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