<?php
/**
 * PHP/sim_disattive/api_sim_disattive.php
 * API JSON per la pagina SIM Disattivate
 * Azioni: list (tutte), get (singola SIM), by_contratto (filtro per numero contratto)
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$host   = 'localhost';
$dbname = 'my_saucecode';
$user   = 'saucecode';
$pass   = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Errore connessione DB']);
    exit;
}

$action = $_GET['action'] ?? 'list';

switch ($action) {

    /* ── Lista completa con JOIN al contratto ─────────────────────────── */
    case 'list':
        $sql = "
            SELECT
                sd.codice,
                sd.tipoSIM,
                sd.eraAssociataA,
                sd.dataAttivazione,
                sd.dataDisattivazione,
                c.tipo           AS tipoContratto
            FROM simdisattiva sd
            LEFT JOIN contrattotelefonico c ON c.numero = sd.eraAssociataA
            ORDER BY sd.dataDisattivazione DESC
        ";
        $stmt = $pdo->query($sql);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    /* ── Dettaglio singola SIM tramite codice ─────────────────────────── */
    case 'get':
        $codice = $_GET['codice'] ?? '';
        if ($codice === '') {
            echo json_encode(['success' => false, 'message' => 'Codice mancante']);
            break;
        }
        $stmt = $pdo->prepare("
            SELECT
                sd.codice,
                sd.tipoSIM,
                sd.eraAssociataA,
                sd.dataAttivazione,
                sd.dataDisattivazione,
                c.tipo AS tipoContratto
            FROM simdisattiva sd
            LEFT JOIN contrattotelefonico c ON c.numero = sd.eraAssociataA
            WHERE sd.codice = ?
        ");
        $stmt->execute([$codice]);
        $row = $stmt->fetch();
        if ($row) {
            echo json_encode(['success' => true, 'data' => $row]);
        } else {
            echo json_encode(['success' => false, 'message' => 'SIM non trovata']);
        }
        break;

    /* ── Filtra per numero contratto ──────────────────────────────────── */
    case 'by_contratto':
        $numero = $_GET['numero'] ?? '';
        if ($numero === '') {
            echo json_encode(['success' => false, 'message' => 'Numero contratto mancante']);
            break;
        }
        $stmt = $pdo->prepare("
            SELECT codice, tipoSIM, dataAttivazione, dataDisattivazione
            FROM simdisattiva
            WHERE eraAssociataA = ?
            ORDER BY dataDisattivazione DESC
        ");
        $stmt->execute([$numero]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Azione non valida']);
}
