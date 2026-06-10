<?php
    // Questo file serve a fare i calcoli e preparare i dati per la dashboard.
    // La variabile $pdo viene ereditata automaticamente dal file index.php.

    // 1. Query per contare i contratti di tipo 'ricarica'
    $query_ricarica = "SELECT COUNT(*) as totale FROM contratti WHERE tipo = 'ricarica'";
    $res_ricarica = $pdo->query($query_ricarica)->fetch();
    $num_ricarica = $res_ricarica ? $res_ricarica['totale'] : 0;

    // 2. Query per contare i contratti di tipo 'consumo'
    $query_consumo = "SELECT COUNT(*) as totale FROM contratti WHERE tipo = 'consumo'";
    $res_consumo = $pdo->query($query_consumo)->fetch();
    $num_consumo = $res_consumo ? $res_consumo['totale'] : 0;

    // 3. Calcolo del totale complessivo e delle percentuali matematiche
    $totale_contratti = $num_ricarica + $num_consumo;

    if ($totale_contratti > 0) {
        $perc_ricarica = round(($num_ricarica / $totale_contratti) * 100);
        $perc_consumo = round(($num_consumo / $totale_contratti) * 100);
    } else {
        $perc_ricarica = 0;
        $perc_consumo = 0;
    }

    // Impacchettiamo tutti i risultati dentro un unico array comodo da usare
    $stats_dashboard = [
        'num_ricarica'     => $num_ricarica,
        'num_consumo'      => $num_consumo,
        'totale_contratti' => $totale_contratti,
        'perc_ricarica'    => $perc_ricarica,
        'perc_consumo'     => $perc_consumo
    ];

?>