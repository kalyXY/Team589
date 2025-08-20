<?php
/** @var string $title */
?><!doctype html>
<html lang="fr">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo htmlspecialchars($title ?? 'Scolaria', ENT_QUOTES, 'UTF-8'); ?></title>
	<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
</head>
<body>
	<header class="site-header">
		<div class="container">
			<h1>Scolaria</h1>
		</div>
	</header>
	<main class="container">
		<h2><?php echo htmlspecialchars($title ?? '', ENT_QUOTES, 'UTF-8'); ?></h2>
		<p>Ceci est la page d’accueil de l’application.</p>
		<div class="cta">
			<a href="<?php echo BASE_URL; ?>login.php" class="btn btn-primary">Se connecter</a>
		</div>
	</main>
	<script src="<?php echo BASE_URL; ?>assets/js/app.js"></script>
</body>
</html>


