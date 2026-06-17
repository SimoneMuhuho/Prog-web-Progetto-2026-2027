<?php
    $pagina_corrente = 'dashboard';
    include 'header.php';
    
    // Inclusione file di logica per ottenere i dati della dashboard
    include 'PHP/dashboard/get_dashboard_data.php'; 
?>

<main class="layout-1">
    <aside class="sidebar-filtro sidebar-filtro-panoramica">
        <h3>Panoramica</h3>

        <div class="filtro-group">
            <h2>Panoramica Rapida</h2>
            <p>Contratti Totali</p> <span><?php echo $stats_dashboard['totale_contratti']; ?></span>
            <p>SIM Attive</p> <span><?php echo $stats_dashboard['sim_attive']; ?></span>
            <p>SIM Non Attive</p> <span><?php echo $stats_dashboard['sim_non_attive']; ?></span>
        </div>

        <div class="sidebar-results-footer">
            <div class="results-block">
                <div class="info">
                    La dashboard si aggiorna automaticamente al variare di SIM e contratti.
                </div>
            </div>
        </div>
        
    </aside>

    <section class="contenuto-risultati">
        <h2>Dashboard</h2>
        <p>Panoramica in tempo reale di SIM, contratti e chiamate.</p>

        <div class="sidebar-progress-box dashboard-progress-top">
            <h3>Proporzione Contratti</h3>
            
            <div class="progress-bar-container">
                <div class="progress-segment ricarica" style="width: <?php echo $stats_dashboard['perc_ricarica']; ?>%;">
                    <?php echo $stats_dashboard['perc_ricarica']; ?>%
                </div>
                <div class="progress-segment consumo" style="width: <?php echo $stats_dashboard['perc_consumo']; ?>%;">
                    <?php echo $stats_dashboard['perc_consumo']; ?>%
                </div>
            </div>                
            <div class="progress-legend">
                <span><i class="fa-solid fa-circle" style="color: var(--green);"></i> Ricarica (<?php echo $stats_dashboard['num_ricarica']; ?>)</span>
                <span><i class="fa-solid fa-circle" style="color: var(--blue);"></i> Consumo (<?php echo $stats_dashboard['num_consumo']; ?>)</span>
            </div>
        </div>

        <div class="dashboard-lower-row">
            
            <div class="dashboard-panels-matrix">
                <div class="panel">
                    <i class="fa-solid fa-signal"></i>
                    <h3><?php echo $stats_dashboard['totale_contratti']; ?></h3>
                    <p>Contratti Totali</p>
                </div>
                <div class="panel">
                    <i class="fa-solid fa-sim-card"></i>
                    <h3><?php echo $stats_dashboard['sim_attive']; ?></h3>
                    <p>SIM Attive</p>
                </div>
                <div class="panel">
                    <i class="fa-solid fa-box"></i>
                    <h3><?php echo $stats_dashboard['sim_non_attive']; ?></h3>
                    <p>SIM Non Attive</p>
                </div>
                <div class="panel">
                    <i class="fa-solid fa-ban"></i>
                    <h3><?php echo $stats_dashboard['sim_disattive']; ?></h3>
                    <p>SIM Disattivate</p>
                </div>
            </div>

            <div class="dashboard-table-wrapper">
                <div class="dashboard-table-header">
                    <h3><i class="fa-solid fa-phone"></i> Ultime Chiamate Effettuate</h3>
                </div>
                
                <div class="panel dashboard-table-panel">
                    <div class="dashboard-table-scroll">
                        <table class="tabella-risultati">
                            <thead>
                                <tr>
                                    <th>Numero Contratto</th>
                                    <th>Durata</th>
                                    <th>Costo</th>
                                    <th>Data e Ora</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($ultime_chiamate)): ?>
                                    <?php foreach ($ultime_chiamate as $chiamata): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($chiamata['effettuataDa']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($chiamata['durata']); ?> s</td>
                                            <td>
                                                <?php 
                                                    $costo_numerico = (!isset($chiamata['costo']) || $chiamata['costo'] === '' || is_null($chiamata['costo'])) ? 0 : $chiamata['costo'];
                                                    echo number_format($costo_numerico, 2, ',', '.') . ' €'; 
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                    $data_formattata = date('d/m/Y', strtotime($chiamata['data']));
                                                    $ora_formattata = date('H:i', strtotime($chiamata['ora']));
                                                    echo $data_formattata . ' ' . $ora_formattata; 
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="tabella-vuota-dashboard">
                                            Nessuna chiamata registrata di recente.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </section>
</main>

<?php include 'footer.php';?>