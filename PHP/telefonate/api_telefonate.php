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

        if (!$id || $durata === null) {
            echo json_encode(['success' => false, 'message' => 'Parametri mancanti per la modifica.']);
            break;
        }

        try {
            $stmtC = $pdo->prepare("
                SELECT c.tipo 
                FROM telefonata t
                JOIN contrattotelefonico c ON c.numero = t.effettuataDa
                WHERE t.id = ?
            ");
            $stmtC->execute([$id]);
            $contratto = $stmtC->fetch(PDO::FETCH_ASSOC);

            if (!$contratto) {
                echo json_encode(['success' => false, 'message' => 'Impossibile determinare il tipo di contratto per questa telefonata.']);
                break;
            }

            if ($contratto['tipo'] === 'consumo') {
                $costoSalvabile = null;
            } else {
                if ($costo === null || $costo === '') {
                    echo json_encode(['success' => false, 'message' => 'Il costo è obbligatorio per i contratti ricarica.']);
                    break;
                }
                $costoSalvabile = (float)$costo;
            }

            $stmt = $pdo->prepare("UPDATE telefonata SET durata = ?, costo = ? WHERE id = ?");
            $stmt->execute([$durata, $costoSalvabile, $id]);

            echo json_encode(['success' => true, 'message' => 'Modifica effettuata con successo.']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Errore aggiornamento: ' . $e->getMessage()]);
        }
        break;

    /* DELETE Eliminazione di una telefonata */
    case 'delete':
        $id = $_POST['id'] ?? null;
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID mancante.']);
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

    /* GET_TIPO_CONTRATTO Rilevamento real-time del tipo di contratto associato a un numero */
    case 'get_tipo_contratto':
        $numero = $_GET['numero'] ?? '';
        if (empty($numero)) {
            echo json_encode(['success' => false, 'message' => 'Numero non fornito.']);
            break;
        }
        try {
            $stmt = $pdo->prepare("SELECT tipo FROM contrattotelefonico WHERE numero = ?");
            $stmt->execute([$numero]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                echo json_encode(['success' => true, 'tipo' => $row['tipo']]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Contratto non trovato.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Errore: ' . $e->getMessage()]);
        }
        break;

    /* CREATE Inserimento di una nuova telefonata con controlli transazionali */
    case 'create':
        $effettuataDa = $_POST['effettuataDa'] ?? null;
        $data         = $_POST['data'] ?? null;
        $ora          = $_POST['ora'] ?? null;
        $durata       = $_POST['durata'] ?? null; // Ricevuta in secondi dal client
        $costo        = $_POST['costo'] ?? null;

        if (!$effettuataDa || !$data || !$ora || !$durata) {
            echo json_encode(['success' => false, 'message' => 'Tutti i campi obbligatori devono essere compilati.']);
            break;
        }

        try {
            // Inizia la transazione per garantire l'atomicità dei controlli sui residui monetari/minuti
            $pdo->beginTransaction();

            // Blocca la riga del contratto specifico per evitare problemi di concorrenza
            $stmtC = $pdo->prepare("SELECT tipo, minutiResidui, creditoResiduo FROM contrattotelefonico WHERE numero = ? FOR UPDATE");
            $stmtC->execute([$effettuataDa]);
            $contratto = $stmtC->fetch(PDO::FETCH_ASSOC);

            if (!$contratto) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Errore: Il numero SIM inserito non corrisponde a un contratto valido.']);
                break;
            }

            $tipoContratto = $contratto['tipo'];

            // Gestione dei vincoli e decremento in base alla tipologia contrattuale
            if ($tipoContratto === 'consumo') {
                $costoSalvabile = null;
                
                // Conversione della durata (in secondi) a minuti effettivi arrotondati per eccesso
                $minutiTelefonata = (int)ceil($durata / 60);

                if ($contratto['minutiResidui'] < $minutiTelefonata) {
                    $pdo->rollBack();
                    echo json_encode(['success' => false, 'message' => 'Minuti insufficienti sul contratto a consumo per coprire la telefonata.']);
                    break;
                }

                // Decremento dei minuti spesi
                $stmtUp = $pdo->prepare("UPDATE contrattotelefonico SET minutiResidui = minutiResidui - ? WHERE numero = ?");
                $stmtUp->execute([$minutiTelefonata, $effettuataDa]);

            } else {
                if ($costo === null || $costo === '') {
                    $pdo->rollBack();
                    echo json_encode(['success' => false, 'message' => 'Il costo è obbligatorio per i contratti ricarica.']);
                    break;
                }
                $costoSalvabile = (float)$costo;

                if ($contratto['creditoResiduo'] < $costoSalvabile) {
                    $pdo->rollBack();
                    echo json_encode(['success' => false, 'message' => 'Credito residuo insufficiente sul contratto ricarica per coprire il costo della chiamata.']);
                    break;
                }

                // Decremento del credito speso
                $stmtUp = $pdo->prepare("UPDATE contrattotelefonico SET creditoResiduo = creditoResiduo - ? WHERE numero = ?");
                $stmtUp->execute([$costoSalvabile, $effettuataDa]);
            }

            // Inserimento definitivo della telefonata nella cronologia
            $stmt = $pdo->prepare("INSERT INTO telefonata (effettuataDa, data, ora, durata, costo) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$effettuataDa, $data, $ora, $durata, $costoSalvabile]);
            
            $newId = $pdo->lastInsertId();
            
            // Applica tutte le modifiche eseguite in modo sicuro
            $pdo->commit();
            
            echo json_encode([
                'success' => true, 
                'id' => $newId, 
                'tipoContratto' => $tipoContratto
            ]);
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo json_encode(['success' => false, 'message' => 'Errore scrittura: ' . $e->getMessage()]);
        }
        break;
}