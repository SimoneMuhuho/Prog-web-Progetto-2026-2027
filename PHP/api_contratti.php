<?php
/**
 * API REST per CRUD ContrattoTelefonico
 * Endpoint: api_contratti.php?action=...
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// ── Connessione al database ───────────────────────────────────────────────────
// MODIFICA questi parametri con le credenziali del tuo DB
$host   = 'localhost';
$dbname = 'telefonia';
$user   = 'root';
$pass   = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Connessione DB fallita: ' . $e->getMessage()]);
    exit;
}

// ── Routing ───────────────────────────────────────────────────────────────────
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// Leggi body JSON (per POST/PUT)
$body = json_decode(file_get_contents('php://input'), true) ?? [];

switch ($action) {

    // ── READ: lista tutti i contratti ─────────────────────────────────────────
    case 'list':
        $sql = "
            SELECT
                c.numero,
                c.dataAttivazione,
                c.tipo,
                c.minutiResidui,
                c.creditoResiduo,
                COUNT(t.id) AS numTelefonate,
                sa.codice   AS simAttiva
            FROM ContrattoTelefonico c
            LEFT JOIN Telefonata     t  ON t.effettuataDa = c.numero
            LEFT JOIN SIMAttiva      sa ON sa.associataA  = c.numero
            GROUP BY c.numero
            ORDER BY c.numero
        ";
        $stmt = $pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $rows]);
        break;

    // ── READ: telefonate del contratto ───────────────────────────────────────
    case 'telefonate':
        $numero = $_GET['numero'] ?? '';
        if (!$numero) { echo json_encode(['success' => false, 'message' => 'Numero mancante']); break; }
        $stmt = $pdo->prepare("
            SELECT data, ora, durata, costo
            FROM Telefonata
            WHERE effettuataDa = ?
            ORDER BY data DESC, ora DESC
        ");
        $stmt->execute([$numero]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    // ── READ: SIM attiva del contratto ────────────────────────────────────────
    case 'sim_attiva':
        $numero = $_GET['numero'] ?? '';
        if (!$numero) { echo json_encode(['success' => false, 'message' => 'Numero mancante']); break; }
        $stmt = $pdo->prepare("
            SELECT codice, tipoSIM, dataAttivazione
            FROM SIMAttiva
            WHERE associataA = ?
        ");
        $stmt->execute([$numero]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    // ── READ: SIM disattivate del contratto ───────────────────────────────────
    case 'sim_disattive':
        $numero = $_GET['numero'] ?? '';
        if (!$numero) { echo json_encode(['success' => false, 'message' => 'Numero mancante']); break; }
        $stmt = $pdo->prepare("
            SELECT codice, tipoSIM, dataAttivazione, dataDisattivazione
            FROM SIMDisattiva
            WHERE associataA = ?
            ORDER BY dataDisattivazione DESC
        ");
        $stmt->execute([$numero]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    // ── READ: singolo contratto ───────────────────────────────────────────────
    case 'get':
        $numero = $_GET['numero'] ?? '';
        if (!$numero) { echo json_encode(['success' => false, 'message' => 'Parametro numero mancante']); break; }

        $stmt = $pdo->prepare("SELECT * FROM ContrattoTelefonico WHERE numero = ?");
        $stmt->execute([$numero]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            echo json_encode(['success' => true, 'data' => $row]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Contratto non trovato']);
        }
        break;

    // ── CREATE ────────────────────────────────────────────────────────────────
    case 'create':
        $numero          = trim($body['numero']          ?? '');
        $dataAttivazione = trim($body['dataAttivazione'] ?? '');
        $tipo            = trim($body['tipo']            ?? '');
        $minutiResidui   = $body['minutiResidui']   !== '' ? (int)$body['minutiResidui']   : null;
        $creditoResiduo  = $body['creditoResiduo']  !== '' ? (float)$body['creditoResiduo'] : null;

        // Validazione
        $errors = validaContratto($numero, $dataAttivazione, $tipo, $minutiResidui, $creditoResiduo);
        if ($errors) { echo json_encode(['success' => false, 'message' => implode('; ', $errors)]); break; }

        // Controlla duplicato
        $check = $pdo->prepare("SELECT numero FROM ContrattoTelefonico WHERE numero = ?");
        $check->execute([$numero]);
        if ($check->fetch()) { echo json_encode(['success' => false, 'message' => 'Numero già esistente']); break; }

        $stmt = $pdo->prepare("
            INSERT INTO ContrattoTelefonico (numero, dataAttivazione, tipo, minutiResidui, creditoResiduo)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$numero, $dataAttivazione, $tipo, $minutiResidui, $creditoResiduo]);
        echo json_encode(['success' => true, 'message' => 'Contratto creato con successo']);
        break;

    // ── UPDATE ────────────────────────────────────────────────────────────────
    case 'update':
        $numero          = trim($body['numero']          ?? '');
        $dataAttivazione = trim($body['dataAttivazione'] ?? '');
        $tipo            = trim($body['tipo']            ?? '');
        $minutiResidui   = ($body['minutiResidui']  !== null && $body['minutiResidui']  !== '') ? (int)$body['minutiResidui']   : null;
        $creditoResiduo  = ($body['creditoResiduo'] !== null && $body['creditoResiduo'] !== '') ? (float)$body['creditoResiduo'] : null;

        $errors = validaContratto($numero, $dataAttivazione, $tipo, $minutiResidui, $creditoResiduo);
        if ($errors) { echo json_encode(['success' => false, 'message' => implode('; ', $errors)]); break; }

        $stmt = $pdo->prepare("
            UPDATE ContrattoTelefonico
            SET dataAttivazione = ?, tipo = ?, minutiResidui = ?, creditoResiduo = ?
            WHERE numero = ?
        ");
        $affected = $stmt->execute([$dataAttivazione, $tipo, $minutiResidui, $creditoResiduo, $numero]);
        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Contratto non trovato']);
        } else {
            echo json_encode(['success' => true, 'message' => 'Contratto aggiornato con successo']);
        }
        break;

    // ── DELETE ────────────────────────────────────────────────────────────────
    case 'delete':
        $numero = trim($body['numero'] ?? $_GET['numero'] ?? '');
        if (!$numero) { echo json_encode(['success' => false, 'message' => 'Numero mancante']); break; }

        // Verifica dipendenze: telefonate
        $dep = $pdo->prepare("SELECT COUNT(*) FROM Telefonata WHERE effettuataDa = ?");
        $dep->execute([$numero]);
        if ($dep->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'Impossibile eliminare: esistono telefonate associate a questo contratto']);
            break;
        }
        // Verifica dipendenze: SIM attive
        $dep2 = $pdo->prepare("SELECT COUNT(*) FROM SIMAttiva WHERE associataA = ?");
        $dep2->execute([$numero]);
        if ($dep2->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'Impossibile eliminare: esiste una SIM attiva associata a questo contratto']);
            break;
        }

        $stmt = $pdo->prepare("DELETE FROM ContrattoTelefonico WHERE numero = ?");
        $stmt->execute([$numero]);
        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Contratto non trovato']);
        } else {
            echo json_encode(['success' => true, 'message' => 'Contratto eliminato con successo']);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Azione non valida']);
}

// ── Helper: validazione ───────────────────────────────────────────────────────
function validaContratto($numero, $dataAttivazione, $tipo, $minutiResidui, $creditoResiduo) {
    $errors = [];
    if (empty($numero))          $errors[] = 'Il numero di telefono è obbligatorio';
    if (!preg_match('/^\+?[\d\s\-]{7,20}$/', $numero)) $errors[] = 'Formato numero non valido';
    if (empty($dataAttivazione)) $errors[] = 'La data di attivazione è obbligatoria';
    if (!in_array($tipo, ['ricarica', 'consumo'])) $errors[] = 'Tipo deve essere "ricarica" o "consumo"';

    if ($tipo === 'consumo') {
        if ($minutiResidui === null) $errors[] = 'minutiResidui obbligatorio per tipo consumo';
        if ($creditoResiduo !== null) $errors[] = 'creditoResiduo deve essere NULL per tipo consumo';
    }
    if ($tipo === 'ricarica') {
        if ($creditoResiduo === null) $errors[] = 'creditoResiduo obbligatorio per tipo ricarica';
        if ($minutiResidui !== null) $errors[] = 'minutiResidui deve essere NULL per tipo ricarica';
    }
    return $errors;
}
