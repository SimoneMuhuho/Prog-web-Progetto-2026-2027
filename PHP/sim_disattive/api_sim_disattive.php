<?php include '../sincronizzazione.php';


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
        $data = $stmt->fetchAll();
        foreach ($data as &$row) {
            $row['codice'] = (string)$row['codice'];
        }
        echo json_encode(['success' => true, 'data' => $data]);
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
            $row['codice'] = (string)$row['codice'];
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
        $data = $stmt->fetchAll();
        foreach ($data as &$row) {
            $row['codice'] = (string)$row['codice'];
        }
        echo json_encode(['success' => true, 'data' => $data]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Azione non valida']);
}
