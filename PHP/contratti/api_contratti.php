<?php include '../sincronizzazione.php';

$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        // Recupera tutti i contratti con il conteggio delle telefonate, lo stato SIM e SIM disattive
        $sql = "
            SELECT 
                c.numero, 
                c.dataAttivazione, 
                c.tipo, 
                c.minutiResidui, 
                c.creditoResiduo,
                (SELECT COUNT(*) FROM telefonata WHERE effettuataDa = c.numero) AS numTelefonate,
                (SELECT COUNT(*) FROM simdisattiva WHERE eraAssociataA = c.numero) AS numDisattive,
                sa.codice AS simAttiva
            FROM contrattotelefonico c
            LEFT JOIN simattiva sa ON sa.associataA = c.numero
            GROUP BY c.numero
            ORDER BY c.dataAttivazione DESC
        ";
        $stmt = $pdo->query($sql);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    case 'telefonate':
        $num = $_GET['numero'] ?? '';
        $stmt = $pdo->prepare("SELECT data, ora, durata, costo FROM telefonata WHERE effettuataDa = ? ORDER BY data DESC, ora DESC");
        $stmt->execute([$num]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    case 'sim_attiva':
        $num = $_GET['numero'] ?? '';
        $stmt = $pdo->prepare("SELECT codice, tipoSIM, dataAttivazione FROM simattiva WHERE associataA = ?");
        $stmt->execute([$num]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    case 'sim_disattive':
        $num = $_GET['numero'] ?? '';
        $stmt = $pdo->prepare("SELECT codice, tipoSIM, dataAttivazione, dataDisattivazione FROM simdisattiva WHERE eraAssociataA = ? ORDER BY dataDisattivazione DESC");
        $stmt->execute([$num]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Azione non valida']);
}