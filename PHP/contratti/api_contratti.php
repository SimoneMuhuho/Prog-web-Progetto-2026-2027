<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$host   = 'localhost';
$dbname = 'telefonia';
$user   = 'root';
$pass   = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Errore connessione']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        // Recupera tutti i contratti con il conteggio delle telefonate e lo stato SIM
        $sql = "
            SELECT 
                c.numero, 
                c.dataAttivazione, 
                c.tipo, 
                c.minutiResidui, 
                c.creditoResiduo,
                (SELECT COUNT(*) FROM Telefonata WHERE effettuataDa = c.numero) AS numTelefonate,
                sa.codice AS simAttiva
            FROM ContrattoTelefonico c
            LEFT JOIN SIMAttiva sa ON sa.associataA = c.numero
            GROUP BY c.numero
            ORDER BY c.dataAttivazione DESC
        ";
        $stmt = $pdo->query($sql);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    case 'telefonate':
        $num = $_GET['numero'] ?? '';
        $stmt = $pdo->prepare("SELECT data, ora, durata, costo FROM Telefonata WHERE effettuataDa = ? ORDER BY data DESC, ora DESC");
        $stmt->execute([$num]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    case 'sim_attiva':
        $num = $_GET['numero'] ?? '';
        $stmt = $pdo->prepare("SELECT codice, tipoSIM, dataAttivazione FROM SIMAttiva WHERE associataA = ?");
        $stmt->execute([$num]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    case 'sim_disattive':
        $num = $_GET['numero'] ?? '';
        $stmt = $pdo->prepare("SELECT codice, tipoSIM, dataAttivazione, dataDisattivazione FROM SIMDisattiva WHERE eraAssociataA = ? ORDER BY dataDisattivazione DESC");
        $stmt->execute([$num]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Azione non valida']);
}