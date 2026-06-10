<?php
    $pagina_corrente = 'dashboard';
    include 'header.php';
    include 'PHP/dashboard/get_dashboard_data.php';
?>

<main class="layout-1">
    <aside class="sidebar-filtro sidebar-filtro-panoramica">
        <h3>Panoramica</h3>

        <div class="filtro-group">
            <div class="sidebar-progress-box">
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
                    <span><i class="fa-solid fa-circle" style="color: var(--red);"></i> Ricarica (<?php echo $stats_dashboard['num_ricarica']; ?>)</span>
                    <span><i class="fa-solid fa-circle" style="color: var(--light-gray);"></i> Consumo (<?php echo $stats_dashboard['num_consumo']; ?>)</span>
                </div>
            </div>
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

        <div class="dashboard-panels">
            <div class="panel">
                <i class="fa-solid fa-signal"></i>
                <h3><?php echo $stats_dashboard['totale_contratti']; ?></h3>
                <p>Contratti Totali</p>
            </div>
            <div class="panel">
                <i class="fa-solid fa-sim-card"></i>
                <h3>120</h3>
                <p>SIM Attive</p>
            </div>
            <div class="panel">
                <i class="fa-solid fa-box"></i>
                <h3>10</h3>
                <p>SIM Non Attive</p>
            </div>
        </div>
    </section>
</main>

<?php include 'footer.php';?>