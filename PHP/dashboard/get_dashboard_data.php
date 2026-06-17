<?php
// Risaliamo di una cartella per trovare sincronizzazione.php dentro la cartella PHP
include_once __DIR__ . '/../sincronizzazione.php'; 

// Rimuoviamo l'header JSON forzato da sincronizzazione.php per permettere all'HTML di caricarsi
header_remove('Content-Type');
header('Content-Type: text/html; charset=utf-8');

// 1. Query per il conteggio generale dei pannelli della Dashboard
$query_totale = "SELECT COUNT(*) as totale FROM contrattotelefonico";
$res_totale = $pdo->query($query_totale)->fetch();
$totale_contratti = $res_totale ? $res_totale['totale'] : 0;

$query_attive = "SELECT COUNT(*) as totale FROM simattiva";
$res_attive = $pdo->query($query_attive)->fetch();
$sim_attive = $res_attive ? $res_attive['totale'] : 0;

$query_non_attive = "SELECT COUNT(*) as totale FROM simnonattiva";
$res_non_attive = $pdo->query($query_non_attive)->fetch();
$sim_non_attive = $res_non_attive ? $res_non_attive['totale'] : 0;

$query_disattive = "SELECT COUNT(*) as totale FROM simdisattiva";
$res_disattive = $pdo->query($query_disattive)->fetch();
$sim_disattive = $res_disattive ? $res_disattive['totale'] : 0;

// 2. Query per calcolare i dati numerici di Ricarica vs Consumo
$query_ricarica = "SELECT COUNT(*) as totale FROM contrattotelefonico WHERE tipo = 'ricarica'";
$res_ricarica = $pdo->query($query_ricarica)->fetch();
$num_ricarica = $res_ricarica ? $res_ricarica['totale'] : 0;

$query_consumo = "SELECT COUNT(*) as totale FROM contrattotelefonico WHERE tipo = 'consumo'";
$res_consumo = $pdo->query($query_consumo)->fetch();
$num_consumo = $res_consumo ? $res_consumo['totale'] : 0;

// 3. Calcolo matematico delle percentuali
if ($totale_contratti > 0) {
    $perc_ricarica = round(($num_ricarica / $totale_contratti) * 100);
    $perc_consumo = round(($num_consumo / $totale_contratti) * 100);
} else {
    $perc_ricarica = 0;
    $perc_consumo = 0;
}

// Salviamo i dati pronti nell'array per index.php
$stats_dashboard = [
    'totale_contratti' => $totale_contratti,
    'sim_attive'       => $sim_attive,
    'sim_non_attive'   => $sim_non_attive,
    'sim_disattive'    => $sim_disattive,
    'num_ricarica'     => $num_ricarica,
    'num_consumo'      => $num_consumo,
    'perc_ricarica'    => $perc_ricarica,
    'perc_consumo'     => $perc_consumo
];

// 4. Query per recuperare le ultime 5 chiamate effettuate
$query_chiamate = "SELECT effettuataDa, durata, costo, data, ora 
                   FROM telefonata 
                   ORDER BY data DESC, ora DESC 
                   LIMIT 5";

$res_chiamate = $pdo->query($query_chiamate)->fetchAll();

// Salviamo i risultati nella variabile per il file HTML
$ultime_chiamate = $res_chiamate ? $res_chiamate : [];