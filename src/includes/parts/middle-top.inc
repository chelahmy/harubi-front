</head>
<body>
<?php if (!isset($main) || $main === TRUE) : ?>
<main>
<?php endif; ?>

<?php
if (!isset($noloading) || $noloading === FALSE)
	include $rpath . "includes/parts/loading.inc";
?>

