<?php include '../sincronizzazione.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

switch ($action) {

    /* ── Lista completa delle SIM non attive ─────────────────────────── */
    case 'list':
        $sql = "
            SELECT
                sd.codice,
                sd.tipoSIM
            FROM simnonattiva sd
        ";
        $stmt = $pdo->query($sql);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    /* ── Dettaglio singola SIM ────────────────────────────────────────── */
    case 'get':
        $codice = $_GET['codice'] ?? '';
        if ($codice === '') {
            echo json_encode(['success' => false, 'message' => 'Codice mancante']);
            break;
        }
        $stmt = $pdo->prepare("
            SELECT sd.codice, sd.tipoSIM
            FROM simnonattiva sd
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

    /* ── Attiva SIM: simnonattiva → simattiva ────────────────────────── */
    case 'activate':
        $codice          = $_POST['codice']          ?? '';
        $associataA      = $_POST['associataA']      ?? '';
        $dataAttivazione = $_POST['dataAttivazione'] ?? '';

        if ($codice === '' || $associataA === '' || $dataAttivazione === '') {
            echo json_encode(['success' => false, 'message' => 'Dati incompleti (codice, numero contratto e data obbligatori)']);
            break;
        }

        try {
            // 1. Verifica che la SIM esista in simnonattiva
            $check = $pdo->prepare("SELECT codice, tipoSIM FROM simnonattiva WHERE codice = ?");
            $check->execute([$codice]);
            $sim = $check->fetch();

            if (!$sim) {
                echo json_encode(['success' => false, 'message' => 'SIM non trovata in archivio non attive']);
                break;
            }

            // 2. Verifica che il contratto esista
            $checkC = $pdo->prepare("SELECT numero FROM contrattotelefonico WHERE numero = ?");
            $checkC->execute([$associataA]);
            if (!$checkC->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Numero contratto non trovato']);
                break;
            }

            // 3. Verifica che il contratto non abbia già una SIM attiva
            $checkS = $pdo->prepare("SELECT codice FROM simattiva WHERE associataA = ?");
            $checkS->execute([$associataA]);
            if ($checkS->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Il contratto ha già una SIM attiva associata']);
                break;
            }

            // 4. Transazione: inserisci in simattiva + rimuovi da simnonattiva
            $pdo->beginTransaction();

            $ins = $pdo->prepare("
                INSERT INTO simattiva (codice, tipoSIM, associataA, dataAttivazione)
                VALUES (?, ?, ?, ?)
            ");
            $ins->execute([$sim['codice'], $sim['tipoSIM'], $associataA, $dataAttivazione]);

            $del = $pdo->prepare("DELETE FROM simnonattiva WHERE codice = ?");
            $del->execute([$codice]);

            $pdo->commit();

            echo json_encode(['success' => true, 'message' => 'SIM attivata con successo']);

        } catch (PDOException $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Errore DB: ' . $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Azione non valida']);
}
