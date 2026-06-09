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
    case 'create':
        $effettuataDa = $_POST['effettuataDa'] ?? null;
        $data         = $_POST['data'] ?? null;
        $ora          = $_POST['ora'] ?? null;
        $durata       = $_POST['durata'] ?? null;
        $costo        = $_POST['costo'] ?? null;

        if (!$effettuataDa || !$data || !$ora || $durata === null || $costo === null) {
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

            // Inserimento della telefonata
            $stmt = $pdo->prepare("INSERT INTO telefonata (effettuataDa, data, ora, durata, costo) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$effettuataDa, $data, $ora, $durata, $costo]);
            
            $newId = $pdo->lastInsertId();
            
            // Restituiamo anche il tipoContratto per permettere al JS di fare l'inserimento immediato nel client
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