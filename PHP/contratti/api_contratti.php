<?php include '../sincronizzazione.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

switch ($action) {
    case 'list':
        $sql = "
            SELECT 
                c.numero, 
                c.dataAttivazione, 
                c.tipo, 
                c.minutiResidui, 
                c.creditoResiduo,
                (SELECT COUNT(*) FROM telefonata WHERE effettuataDa = c.numero) AS numTelefonate,
                (SELECT COUNT(*) FROM simdisattiva WHERE eraAssociataA = c.numero) AS numDisattive,
                CAST(sa.codice AS CHAR) AS simAttiva
            FROM contrattotelefonico c
            LEFT JOIN simattiva sa ON sa.associataA = c.numero
            GROUP BY c.numero
            ORDER BY c.dataAttivazione DESC
        ";
        $stmt = $pdo->query($sql);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    case 'get':
        $num = $_GET['numero'] ?? '';
        $stmt = $pdo->prepare("SELECT * FROM contrattotelefonico WHERE numero = ?");
        $stmt->execute([$num]);
        $row = $stmt->fetch();
        echo json_encode($row ? ['success' => true, 'data' => $row] : ['success' => false, 'message' => 'Non trovato']);
        break;

    case 'telefonate':
        $num = $_GET['numero'] ?? '';
        $stmt = $pdo->prepare("SELECT data, ora, durata, costo FROM telefonata WHERE effettuataDa = ? ORDER BY data DESC, ora DESC");
        $stmt->execute([$num]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    case 'sim_attiva':
        $num = $_GET['numero'] ?? '';
        $stmt = $pdo->prepare("SELECT CAST(codice AS CHAR) AS codice, tipoSIM, dataAttivazione FROM simattiva WHERE associataA = ?");
        $stmt->execute([$num]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    case 'sim_disattive':
        $num = $_GET['numero'] ?? '';
        $stmt = $pdo->prepare("SELECT CAST(codice AS CHAR) AS codice, tipoSIM, dataAttivazione, dataDisattivazione FROM simdisattiva WHERE eraAssociataA = ? ORDER BY dataDisattivazione DESC");
        $stmt->execute([$num]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    /* ── Attiva SIM: prende la prima simnonattiva del tipo richiesto ───── */
    case 'activate_sim':
        $numeroContratto = $_POST['numeroContratto'] ?? '';
        $tipoSIM         = $_POST['tipoSIM']         ?? '';
        $dataAttivazione = $_POST['dataAttivazione'] ?? '';

        if ($numeroContratto === '' || $tipoSIM === '' || $dataAttivazione === '') {
            echo json_encode(['success' => false, 'message' => 'Dati incompleti']);
            break;
        }

        try {
            // 1. Contratto esistente?
            $stmtC = $pdo->prepare("SELECT numero FROM contrattotelefonico WHERE numero = ?");
            $stmtC->execute([$numeroContratto]);
            if (!$stmtC->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Contratto non trovato']);
                break;
            }

            // 2. Il contratto ha già una SIM attiva?
            $stmtS = $pdo->prepare("SELECT codice FROM simattiva WHERE associataA = ?");
            $stmtS->execute([$numeroContratto]);
            if ($stmtS->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Il contratto ha già una SIM attiva associata']);
                break;
            }

            // 3. Cerca la prima SIM non attiva del tipo richiesto
            $stmtN = $pdo->prepare("SELECT CAST(codice AS CHAR) AS codice, tipoSIM FROM simnonattiva WHERE tipoSIM = ? LIMIT 1");
            $stmtN->execute([$tipoSIM]);
            $sim = $stmtN->fetch();

            if (!$sim) {
                echo json_encode(['success' => false, 'message' => 'Nessuna SIM di tipo "' . htmlspecialchars($tipoSIM) . '" disponibile in magazzino']);
                break;
            }

            // 4. Transazione: inserisci in simattiva + rimuovi da simnonattiva
            $pdo->beginTransaction();

            $ins = $pdo->prepare("INSERT INTO simattiva (codice, tipoSIM, associataA, dataAttivazione) VALUES (?, ?, ?, ?)");
            $ins->execute([$sim['codice'], $sim['tipoSIM'], $numeroContratto, $dataAttivazione]);

            $del = $pdo->prepare("DELETE FROM simnonattiva WHERE codice = ?");
            $del->execute([$sim['codice']]);

            $pdo->commit();

            echo json_encode([
                'success' => true,
                'codice'  => $sim['codice'],
                'tipoSIM' => $sim['tipoSIM'],
                'message' => 'SIM attivata con successo'
            ]);

        } catch (PDOException $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Errore DB: ' . $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Azione non valida']);
}
