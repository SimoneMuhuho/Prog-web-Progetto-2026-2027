<?php
    $pagina_corrente = 'contratti';
    include 'header.php';
?>
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

        <div class="filtro-group-inline">
            <div class="filtro-group-half">
                <label for="search-data-da">Attivazione dal</label>
                <input type="date" id="search-data-da">
            </div>
            <div class="filtro-group-half">
                <label for="search-data-a">Attivazione al</label>
                <input type="date" id="search-data-a">
            </div>
        </div>

        <div class="filtro-actions">
            <button id="btn-cerca" class="btn btn-primary btn-block">Cerca</button>
            <button id="btn-reset" class="btn btn-outline btn-block">Azzera</button>
        </div>

        <div class="sidebar-results-footer">
            <div class="results-num">
                <span class="label">Risultati</span>
                <span class="count" id="contatore-risultati">0</span>
            </div>
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
                        <th style="text-align:center;">Attiva SIM</th>
                        <th style="text-align:center;">Dettaglio</th>
                    </tr>
                </thead>
                <tbody id="tbl-body" class="tbl-body">
                    <tr><td colspan="9" class="loading">Caricamento…</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <!-- ══════════════════════════════════════════════════════════════════════
         MODAL  –  Dettaglio Contratto
    ═══════════════════════════════════════════════════════════════════════ -->
    <div id="modal-overlay" class="modal-overlay" style="display:none;">
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-title">

            <div class="modal-header">
                <h3 id="modal-title">Dettaglio Contratto</h3>
                <button class="modal-close" id="modal-close-btn" aria-label="Chiudi">&times;</button>
            </div>

            <div class="modal-body">

                <section class="detail-section">
                    <h4>Dati Contratto</h4>
                    <dl class="detail-grid">
                        <dt>Numero</dt>          <dd id="d-numero">—</dd>
                        <dt>Data Attivazione</dt><dd id="d-data">—</dd>
                        <dt>Tipo</dt>            <dd id="d-tipo">—</dd>
                        <dt>Residuo</dt>         <dd id="d-residuo">—</dd>
                    </dl>
                </section>

                <section class="detail-section">
                    <h4>SIM Attiva</h4>
                    <table style="width:100%;font-size:13px;">
                        <thead><tr><th>Codice</th><th>Tipo</th><th>Attiva dal</th></tr></thead>
                        <tbody id="body-sim-attiva"></tbody>
                    </table>
                </section>

                <section class="detail-section">
                    <h4>Storico SIM Disattivate</h4>
                    <table style="width:100%;font-size:13px;">
                        <thead><tr><th>Codice</th><th>Tipo</th><th>Attivazione</th><th>Disattivazione</th></tr></thead>
                        <tbody id="body-sim-disattive"></tbody>
                    </table>
                </section>

                <section class="detail-section">
                    <h4>Telefonate</h4>
                    <table style="width:100%;font-size:13px;">
                        <thead><tr><th>Data</th><th>Ora</th><th>Durata (s)</th><th>Costo (€)</th></tr></thead>
                        <tbody id="body-telefonate"></tbody>
                    </table>
                </section>

            </div>

            <div class="modal-footer">
                <button class="btn btn-outline" id="btn-chiudi">Chiudi</button>
            </div>

        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════
         MODAL  –  Attivazione SIM per contratto
    ═══════════════════════════════════════════════════════════════════════ -->
    <div id="modal-attiva-overlay" class="modal-overlay" style="display:none;">
        <div class="modal" style="max-width:480px;" role="dialog" aria-modal="true" aria-labelledby="att-modal-title">

            <div class="modal-header">
                <h3 id="att-modal-title">Attiva una SIM</h3>
                <button class="modal-close" id="att-close-btn" aria-label="Chiudi">&times;</button>
            </div>

            <form id="att-form">
                <div class="modal-body">

                    <div id="att-errors" class="msg-box msg-error" style="display:none;"></div>

                    <section class="detail-section">
                        <h4>Contratto selezionato</h4>
                        <dl class="detail-grid">
                            <dt>Numero</dt><dd id="att-contratto-display">—</dd>
                        </dl>
                    </section>

                    <section class="detail-section" style="margin-bottom:0;">
                        <h4>Preferenze SIM</h4>

                        <input type="hidden" id="att-numero-contratto">

                        <div class="filtro-group" style="padding:0; margin-bottom:14px;">
                            <label for="att-tipo-sim">Tipo SIM desiderato *</label>
                            <select id="att-tipo-sim" required>
                                <option value="">— Seleziona —</option>
                                <option value="nano">Nano</option>
                                <option value="micro">Micro</option>
                                <option value="standard">Standard</option>
                                <option value="eSIM">eSIM</option>
                            </select>
                        </div>

                        <div class="filtro-group" style="padding:0; margin-bottom:0;">
                            <label for="att-data">Data di Attivazione *</label>
                            <input type="date" id="att-data" required>
                        </div>
                    </section>

                </div>

                <div class="modal-footer">
                    <button type="button" id="att-btn-annulla" class="btn btn-outline">Annulla</button>
                    <button type="submit" id="att-btn-conferma" class="btn btn-primary"
                            style="background-color:var(--green);">
                        Conferma Attivazione
                    </button>
                </div>
            </form>

        </div>
    </div>

</main>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="JavaScript/contratti.js"></script>

<?php include 'footer.php'; ?>
