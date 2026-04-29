<?php include 'header.php';?>
<link rel="stylesheet" href="CSS/contratti.css">
    <main class="layout-1">

        <aside class="sidebar-filtro">
            <h3>Ricerca</h3>

            <div class="filtro-group">
                <label for="search-numero">Numero</label>
                <input type="text" id="search-numero" placeholder="es. +39 333…" autocomplete="off">
            </div>

            <div class="filtro-group">
                <label for="search-tipo">Tipo</label>
                <select id="search-tipo">
                    <option value="">Tutti</option>
                    <option value="ricarica">Ricarica</option>
                    <option value="consumo">Consumo</option>
                </select>
            </div>

            <div class="filtro-group">
                <label for="search-data-da">Attivazione dal</label>
                <input type="date" id="search-data-da">
            </div>

            <div class="filtro-group">
                <label for="search-data-a">Attivazione al</label>
                <input type="date" id="search-data-a">
            </div>

            <div class="filtro-actions">
                <button id="btn-cerca" class="btn btn-primary btn-block">Cerca</button>
                <button id="btn-reset" class="btn btn-outline btn-block">Azzera</button>
            </div>
        </aside>

        <section class="contenuto-risultati">
            <h2>Contratti Telefonici</h2>

            <div id="msg-box" class="msg-box"></div>

            <div class="table-wrap">
                <table id="tbl-contratti">
                    <thead>
                        <tr>
                            <th>Numero</th>
                            <th>Data Attivazione</th>
                            <th>Tipo</th>
                            <th>Minuti Residui</th>
                            <th>Credito Residuo (€)</th>
                            <th>N° Telefonate</th>
                            <th>SIM Attiva</th>
                            <th>Dettaglio</th>
                        </tr>
                    </thead>
                    <tbody id="tbl-body">
                        <tr><td colspan="8" class="loading">Caricamento…</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

    </main>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="JavaScript/contratti.js"></script>

<?php include 'footer.php';?>
