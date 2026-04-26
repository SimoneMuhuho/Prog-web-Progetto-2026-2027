<?php include('header.php'); ?>

<main class="layout-1">

    <!-- ── Colonna sinistra: Filtro / Ricerca ────────────────────────────── -->
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

    <!-- ── Colonna destra: Contenuto / Risultati ─────────────────────────── -->
    <section class="contenuto-risultati">
        <h2>Contratti Telefonici</h2>

        <div id="msg-box" class="msg-box" style="display:none;"></div>

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

    <!-- ══════════════════════════════════════════════════════════════════════
         MODAL  –  Dettaglio Contratto
    ═══════════════════════════════════════════════════════════════════════ -->
    <div id="modal-overlay" class="modal-overlay" style="display:none;">
        <div class="modal modal-large" role="dialog" aria-modal="true" aria-labelledby="modal-title">

            <div class="modal-header">
                <h3 id="modal-title">Dettaglio Contratto</h3>
                <button class="modal-close" id="modal-close-btn" aria-label="Chiudi">&times;</button>
            </div>

            <div class="modal-body">

                <!-- Riepilogo contratto -->
                <section class="detail-section">
                    <h4>Dati Contratto</h4>
                    <dl class="detail-grid">
                        <dt>Numero</dt>      <dd id="d-numero">—</dd>
                        <dt>Attivazione</dt> <dd id="d-data">—</dd>
                        <dt>Tipo</dt>        <dd id="d-tipo">—</dd>
                        <dt>Residuo</dt>     <dd id="d-residuo">—</dd>
                    </dl>
                </section>

                <!-- Telefonate -->
                <section class="detail-section">
                    <h4>Telefonate effettuate</h4>
                    <div class="table-wrap">
                        <table class="tbl-detail">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Ora</th>
                                    <th>Durata (min)</th>
                                    <th>Costo (€)</th>
                                </tr>
                            </thead>
                            <tbody id="body-telefonate">
                                <tr><td colspan="4" class="loading">—</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- SIM Attiva -->
                <section class="detail-section">
                    <h4>SIM Attiva</h4>
                    <div class="table-wrap">
                        <table class="tbl-detail">
                            <thead>
                                <tr>
                                    <th>Codice SIM</th>
                                    <th>Tipo SIM</th>
                                    <th>Data Attivazione</th>
                                </tr>
                            </thead>
                            <tbody id="body-sim-attiva">
                                <tr><td colspan="3" class="loading">—</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- SIM Disattivate -->
                <section class="detail-section">
                    <h4>SIM Disattivate</h4>
                    <div class="table-wrap">
                        <table class="tbl-detail">
                            <thead>
                                <tr>
                                    <th>Codice SIM</th>
                                    <th>Tipo SIM</th>
                                    <th>Data Attivazione</th>
                                    <th>Data Disattivazione</th>
                                </tr>
                            </thead>
                            <tbody id="body-sim-disattive">
                                <tr><td colspan="4" class="loading">—</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>

            </div><!-- /.modal-body -->

            <div class="modal-footer">
                <button class="btn btn-outline" id="btn-chiudi">Chiudi</button>
            </div>

        </div>
    </div>

</main>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="../JavaScript/contratti.js"></script>

<?php include('footer.php'); ?>
