<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="CSS/new-style.css">
    <title>Contratti Telefonici - Dashboard</title>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>

    <header class="header">
        <h1>SIM Salabim</h1>
    </header>

    <nav class="nav">
        <ul>
            <li><a href="index.php" class="<?php echo($pagina_corrente == 'dashboard' ? 'active' : ''); ?>">Dashboard</a></li>
            <li><a href="contratto-telefonico.php" class="<?php echo($pagina_corrente == 'contratti' ? 'active' : ''); ?>">Contratti</a></li>
            <li><a href="telefonate.php" class="<?php echo($pagina_corrente == 'telefonate' ? 'active' : ''); ?>">Telefonate</a></li>
            <li>
                <div class="dropdown">
                    <button class="dropbtn">SIM</button>
                    <div class="dropdown-content">
                        <a href="sim_attive.php" class="<?php echo($pagina_corrente == 'sim_attive' ? 'active' : ''); ?>">Attive</a>
                        <a href="sim_disattivate.php" class="<?php echo($pagina_corrente == 'sim_disattivate' ? 'active' : ''); ?>">Disattivate</a>
                        <a href="sim_non_attive.php" class="<?php echo($pagina_corrente == 'sim_non_attive' ? 'active' : ''); ?>">Non Attive</a>
                    </div>
                </div>
            </li>
        </ul>
    </nav>
