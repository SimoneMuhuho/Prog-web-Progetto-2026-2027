<?php
    $pagina_corrente = 'dashboard';
    include 'header.php';
?>

<main class="layout-1">
    <aside class="sidebar-filtro sidebar-filtro-panoramica">
        <h3>Panoramica</h3>

        <div class="filtro-group">
            <h2>Panoramica Rapida</h2>
            <p>Contratti Totali</p>
            <p>SIM Attive</p>
            <p>SIM Disattive</p>
            <p>SIM Non Attive</p>
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

    </section>

</main>

<?php include 'footer.php';?>
