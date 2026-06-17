<?php
/**
 * PHP/telefonate/api_telefonate.php
 * Codice completo - Integrazione logica di storno su eliminazione
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

ini_set('display_errors', 0);
error_reporting(E_ALL);

include '../sincronizzazione.php';

$action = $_REQUEST['action'] ?? 'list';

switch ($action) {
    /* ══════════════════════════════════════════════════════════════════════
       1. READ (LIST): Recupera la cronologia delle chiamate
       ═════════════════════════════════════════════════════════════════════ */
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

    /* ══════════════════════════════════════════════════════════════════════
       2. UPDATE: Modifica di durata e costo di una telefonata esistente
       ═════════════════════════════════════════════════════════════════════ */
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

    /* ══════════════════════════════════════════════════════════════════════
       3. DELETE: Eliminazione con RIACCREDITO di minuti o credito monetario
       ═════════════════════════════════════════════════════════════════════ */
    case 'delete':
        $id = $_POST['id'] ?? null;
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID mancante.']);
            break;
        }

        try {
            $pdo->beginTransaction();

            // 1. Recuperiamo i dettagli della telefonata PRIMA di cancellarla
            $stmtChiamata = $pdo->prepare("
                SELECT t.effettuataDa, t.durata, t.costo, c.tipo AS tipoContratto
                FROM telefonata t
                LEFT JOIN contrattotelefonico c ON c.numero = t.effettuataDa
                WHERE t.id = ?
                FOR UPDATE
            ");
            $stmtChiamata->execute([$id]);
            $chiamata = $stmtChiamata->fetch(PDO::FETCH_ASSOC);

            if (!$chiamata) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Telefonata non trovata o già eliminata.']);
                break;
            }

            $numero = $chiamata['effettuataDa'];
            $tipoContratto = $chiamata['tipoContratto'];

            // 2. Se il contratto esiste ancora a sistema, restituiamo il dovuto
            if ($tipoContratto) {
                if ($tipoContratto === 'consumo') {
                    // Convertiamo la durata memorizzata (in secondi) in minuti arrotondati per eccesso
                    $minutiDaStornare = (int)ceil($chiamata['durata'] / 60);

                    // Riaggiungiamo i minuti ai minutiResidui
                    $stmtRestore = $pdo->prepare("UPDATE contrattotelefonico SET minutiResidui = minutiResidui + ? WHERE numero = ?");
                    $stmtRestore->execute([$minutiDaStornare, $numero]);
                } else {
                    $creditoDaStornare = (float)($chiamata['costo'] ?? 0);

                    if ($creditoDaStornare > 0) {
                        // Riaggiungiamo gli euro al creditoResiduo
                        $stmtRestore = $pdo->prepare("UPDATE contrattotelefonico SET creditoResiduo = creditoResiduo + ? WHERE numero = ?");
                        $stmtRestore->execute([$creditoDaStornare, $numero]);
                    }
                }
            }

            // 3. Cancellazione fisica del record della chiamata
            $stmtDel = $pdo->prepare("DELETE FROM telefonata WHERE id = ?");
            $stmtDel->execute([$id]);

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Telefonata eliminata e contatori ripristinati correttamente.']);
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo json_encode(['success' => false, 'message' => 'Errore eliminazione e storno: ' . $e->getMessage()]);
        }
        break;

    /* ══════════════════════════════════════════════════════════════════════
       4. GET_TIPO_CONTRATTO: Controllo asincrono real-time del numero
       ═════════════════════════════════════════════════════════════════════ */
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

    /* ══════════════════════════════════════════════════════════════════════
       5. CREATE: Inserimento transazionale con controllo "simattiva"
       ═════════════════════════════════════════════════════════════════════ */
    case 'create':
        $effettuataDa = $_POST['effettuataDa'] ?? null;
        $data         = $_POST['data'] ?? null;
        $ora          = $_POST['ora'] ?? null;
        $durata       = $_POST['durata'] ?? null;
        $costo        = $_POST['costo'] ?? null;

        if (!$effettuataDa || !$data || !$ora || !$durata) {
            echo json_encode(['success' => false, 'message' => 'Tutti i campi obbligatori devono essere compilati.']);
            break;
        }

        try {
            $pdo->beginTransaction();

            $stmtSim = $pdo->prepare("SELECT 1 FROM simattiva WHERE associataA = ? LIMIT 1 FOR UPDATE");
            $stmtSim->execute([$effettuataDa]);
            $simEsistente = $stmtSim->fetch();

            if (!$simEsistente) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Il numero inserito non ha SIM associate.']);
                break;
            }

            $stmtC = $pdo->prepare("SELECT tipo, minutiResidui, creditoResiduo FROM contrattotelefonico WHERE numero = ? FOR UPDATE");
            $stmtC->execute([$effettuataDa]);
            $contratto = $stmtC->fetch(PDO::FETCH_ASSOC);

            if (!$contratto) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Errore: Il numero SIM inserito non corrisponde a un contratto valido.']);
                break;
            }

            $tipoContratto = $contratto['tipo'];

            if ($tipoContratto === 'consumo') {
                $costoSalvabile = null;
                $minutiTelefonata = (int)ceil($durata / 60);

                if ($contratto['minutiResidui'] < $minutiTelefonata) {
                    $pdo->rollBack();
                    echo json_encode(['success' => false, 'message' => 'Minuti insufficienti sul contratto a consumo per coprire la telefonata.']);
                    break;
                }

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

                $stmtUp = $pdo->prepare("UPDATE contrattotelefonico SET creditoResiduo = creditoResiduo - ? WHERE numero = ?");
                $stmtUp->execute([$costoSalvabile, $effettuataDa]);
            }

            $stmt = $pdo->prepare("INSERT INTO telefonata (effettuataDa, data, ora, durata, costo) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$effettuataDa, $data, $ora, $durata, $costoSalvabile]);
            
            $newId = $pdo->lastInsertId();
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
exit;