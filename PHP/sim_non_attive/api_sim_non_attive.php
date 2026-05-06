<?php include '../sincronizzazione.php';

$action = $_GET['action'] ?? 'list';

switch ($action) {

    /* ── Lista completa delle SIM non attive ─────────────────────────── */
    case 'list':
        $sql = "
            SELECT
                sd.codice,
                sd.tipoSIM
            FROM simnonattiva sd
        "; // <-- Rimossa la virgola dopo sd.tipoSIM
        $stmt = $pdo->query($sql);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

     case 'get':
        $codice = $_GET['codice'] ?? '';
        if ($codice === '') {
            echo json_encode(['success' => false, 'message' => 'Codice mancante']);
            break;
        }
        $stmt = $pdo->prepare("
            SELECT
                sd.codice,
                sd.tipoSIM
            FROM simnonattiva sd
            WHERE sd.codice = ? 
        "); // <-- Rimossa la virgola e aggiunta la clausola WHERE
        $stmt->execute([$codice]);
        $row = $stmt->fetch();
        
        if ($row) {
            echo json_encode(['success' => true, 'data' => $row]);
        } else {
            echo json_encode(['success' => false, 'message' => 'SIM non trovata']);
        }
        break;
}