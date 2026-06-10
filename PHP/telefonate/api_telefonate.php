<?php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');


include '../sincronizzazione.php';

$action = $_REQUEST['action'] ?? 'list';

switch ($action) {
    /* ── READ */
    case 'list':
        $sql = "
            SELECT 
                t.id, 
                t.effettuataDa, 
                t.data, 
                t.ora, 
                t.durata, 
                t.costo, 
                c.tipo AS tipoContratto
            FROM telefonata t
            LEFT JOIN contrattotelefonico c ON c.numero = t.effettuataDa
            ORDER BY t.data DESC, t.ora DESC
        ";
        try {
            $stmt = $pdo->query($sql);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Errore lettura: ' . $e->getMessage()]);
        }
        break;

    /* UPDATE  Modifica di durata e costo di una telefonata*/
    case 'update':
        $id     = $_POST['id'] ?? null;
        $durata = $_POST['durata'] ?? null;
        $costo  = $_POST['costo'] ?? null;

        if (!$id || $durata === null || $costo === null) {
            echo json_encode(['success' => false, 'message' => 'Dati incompleti per l\'aggiornamento']);
            break;
        }

        try {
            $stmt = $pdo->prepare("UPDATE telefonata SET durata = ?, costo = ? WHERE id = ?");
            $stmt->execute([$durata, $costo, $id]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Errore aggiornamento: ' . $e->getMessage()]);
        }
        break;

    /* ── DELETE: Cancellazione definitiva di una telefonata ── */
    case 'delete':
        $id = $_POST['id'] ?? null;

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID mancante per l\'eliminazione']);
            break;
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM telefonata WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Errore eliminazione: ' . $e->getMessage()]);
        }
        break;

    /* ── CREATE: Inserimento di una nuova telefonata ── */
    case 'get_tipo_contratto':
        $numero = $_GET['numero'] ?? '';
        if (empty($numero)) {
            echo json_encode(['success' => false]);
            break;
        }
        try {
            $stmt = $pdo->prepare("SELECT tipo FROM contrattotelefonico WHERE numero = ?");
            $stmt->execute([$numero]);
            $contratto = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($contratto) {
                echo json_encode(['success' => true, 'tipo' => $contratto['tipo']]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Contratto non trovato']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    /* ... case 'list', case 'update', case 'delete' rimangono uguali ... */

    /* ── CREATE: Inserimento di una nuova telefonata ── */
    case 'create':
        $effettuataDa = $_POST['effettuataDa'] ?? null;
        $data         = $_POST['data'] ?? null;
        $ora          = $_POST['ora'] ?? null;
        $durata       = $_POST['durata'] ?? null;
        $costo        = $_POST['costo'] ?? null;

        // MODIFICATO: Rimosso il controllo vincolante su $costo === null qui all'inizio, lo verifichiamo dopo
        if (!$effettuataDa || !$data || !$ora || $durata === null) {
            echo json_encode(['success' => false, 'message' => 'Dati incompleti per la creazione']);
            break;
        }

        try {
            // Controllo validità del contratto e recupero automatico del tipo
            $stmtCheck = $pdo->prepare("SELECT tipo FROM contrattotelefonico WHERE numero = ?");
            $stmtCheck->execute([$effettuataDa]);
            $contratto = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if (!$contratto) {
                echo json_encode(['success' => false, 'message' => 'Errore: Il numero SIM inserito non corrisponde a un contratto valido.']);
                break;
            }

            $tipoContratto = $contratto['tipo'];

            // LOGICA DI CONTROLLO: se a consumo, azzera qualsiasi input e forza a NULL
            if ($tipoContratto === 'consumo') {
                $costoSalvabile = null;
            } else {
                // Se ricarica, validiamo che il costo sia effettivamente pervenuto ed accettabile
                if ($costo === null || $costo === '') {
                    echo json_encode(['success' => false, 'message' => 'Il costo è obbligatorio per i contratti ricarica.']);
                    break;
                }
                $costoSalvabile = (float)$costo;
            }

            // Inserimento della telefonata (usando la variabile normalizzata $costoSalvabile)
            $stmt = $pdo->prepare("INSERT INTO telefonata (effettuataDa, data, ora, durata, costo) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$effettuataDa, $data, $ora, $durata, $costoSalvabile]);
            
            $newId = $pdo->lastInsertId();
            
            echo json_encode([
                'success' => true, 
                'id' => $newId, 
                'tipoContratto' => $tipoContratto
            ]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Errore creazione: ' . $e->getMessage()]);
        }
        break;

}