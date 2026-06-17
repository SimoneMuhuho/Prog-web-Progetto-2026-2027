<?php include '../sincronizzazione.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

switch ($action) {

    /* Lista completa delle SIM attive */
    case 'list':
        $sql = "
            SELECT
                sd.codice,
                sd.tipoSIM,
                sd.associataA AS numero,
                sd.dataAttivazione
            FROM simattiva sd
            ORDER BY sd.dataAttivazione DESC
        ";
        $stmt = $pdo->query($sql);
        $data = $stmt->fetchAll();
        foreach ($data as &$row) {
            $row['codice'] = (string)$row['codice'];
        }
        echo json_encode(['success' => true, 'data' => $data]);
        break;

    /* Dettaglio singola SIM attiva */
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
                sd.associataA AS numero,
                sd.dataAttivazione
            FROM simattiva sd
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

    /* Disattiva SIM: simattiva → simdisattiva */
    case 'deactivate':
        $codice             = $_POST['codice']             ?? '';
        $dataDisattivazione = $_POST['dataDisattivazione'] ?? '';

        if ($codice === '' || $dataDisattivazione === '') {
            echo json_encode(['success' => false, 'message' => 'Dati incompleti (codice e data disattivazione obbligatori)']);
            break;
        }

        try {
            // 1. Recupera i dati completi della SIM attiva
            $check = $pdo->prepare("
                SELECT codice, tipoSIM, associataA, dataAttivazione
                FROM simattiva
                WHERE codice = ?
            ");
            $check->execute([$codice]);
            $sim = $check->fetch();

            if (!$sim) {
                echo json_encode(['success' => false, 'message' => 'SIM non trovata tra le attive']);
                break;
            }

            // 2. Controlla coerenza date
            if ($dataDisattivazione < $sim['dataAttivazione']) {
                echo json_encode(['success' => false, 'message' => 'La data di disattivazione non può essere precedente alla data di attivazione']);
                break;
            }

            // 3. Transazione: inserisci in simdisattiva + rimuovi da simattiva
            $pdo->beginTransaction();

            $ins = $pdo->prepare("
                INSERT INTO simdisattiva (codice, tipoSIM, eraAssociataA, dataAttivazione, dataDisattivazione)
                VALUES (?, ?, ?, ?, ?)
            ");
            $ins->execute([
                $sim['codice'],
                $sim['tipoSIM'],
                $sim['associataA'],
                $sim['dataAttivazione'],
                $dataDisattivazione
            ]);

            $del = $pdo->prepare("DELETE FROM simattiva WHERE codice = ?");
            $del->execute([$codice]);

            $pdo->commit();

            echo json_encode(['success' => true, 'message' => 'SIM disattivata con successo']);

        } catch (PDOException $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Errore DB: ' . $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Azione non valida']);
}
