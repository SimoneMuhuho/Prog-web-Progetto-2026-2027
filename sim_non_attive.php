<?php 
    $pagina_corrente = 'sim_non_attive';
    include 'header.php';
?>

<main class="layout-1">

    <!-- ── Colonna sinistra: Filtro / Ricerca ────────────────────────────── -->
    <aside class="sidebar-filtro">
        <h3>Ricerca</h3>

        <div class="filtro-group">
            <label for="search-codice">Codice SIM</label>
            <input type="text" id="search-codice" placeholder="es. 9284…" autocomplete="off">
        </div>

        <div class="filtro-group">
            <label for="search-tipo-sim">Tipo SIM</label>
            <select id="search-tipo-sim">
                <option value="">Tutti</option>
                <option value="nano">Nano</option>
                <option value="micro">Micro</option>
                <option value="standard">Standard</option>
                <option value="eSIM">eSIM</option>
            </select>
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

    <!-- ── Colonna destra: Contenuto / Risultati ─────────────────────────── -->
    <section class="contenuto-risultati">
        <h2>SIM Non Attive</h2>

        <div id="msg-box" class="msg-box" style="display:none;"></div>

        <div class="table-wrap">
            <table id="tbl-sim-non-attive">
                <thead>
                    <tr>
                        <th>Codice SIM</th>
                        <th>Tipo SIM</th>
                        <th style="text-align:center;">Azioni</th>
                    </tr>
                </thead>
                <tbody id="tbl-body" class="tbl-body">
                    <tr><td colspan="3" class="loading">Caricamento…</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <!-- ══════════════════════════════════════════════════════════════════════
         MODAL  –  Dettaglio SIM Non Attiva
    ═══════════════════════════════════════════════════════════════════════ -->
    <div id="modal-overlay" class="modal-overlay" style="display:none;">
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-title">

            <div class="modal-header">
                <h3 id="modal-title">Dettaglio SIM Non Attiva</h3>
                <button class="modal-close" id="modal-close-btn" aria-label="Chiudi">&times;</button>
            </div>

            <div class="modal-body">
                <section class="detail-section">
                    <h4>Dati SIM</h4>
                    <dl class="detail-grid">
                        <dt>Codice SIM</dt> <dd id="d-codice">—</dd>
                        <dt>Tipo SIM</dt>   <dd id="d-tipo-sim">—</dd>
                    </dl>
                </section>
            </div>

            <div class="modal-footer">
                <button class="btn btn-outline" id="btn-chiudi">Chiudi</button>
            </div>

        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════
         MODAL  –  Attivazione SIM
    ═══════════════════════════════════════════════════════════════════════ -->
    <div id="modal-attiva-overlay" class="modal-overlay" style="display:none;">
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="att-modal-title">

            <div class="modal-header">
                <h3 id="att-modal-title">Attiva SIM</h3>
                <button class="modal-close" id="att-close-btn" aria-label="Chiudi">&times;</button>
            </div>

            <form id="att-form">
                <div class="modal-body">

                    <div id="att-errors" class="msg-box msg-error" style="display:none;"></div>

                    <!-- Riepilogo SIM che si sta attivando -->
                    <section class="detail-section">
                        <h4>SIM da attivare</h4>
                        <dl class="detail-grid">
                            <dt>Codice SIM</dt> <dd id="att-codice-display">—</dd>
                            <dt>Tipo SIM</dt>   <dd id="att-tipo-display">—</dd>
                        </dl>
                    </section>

                    <!-- Input richiesti -->
                    <section class="detail-section" style="margin-bottom:0;">
                        <h4>Dati di attivazione</h4>

                        <input type="hidden" id="att-codice">

                        <div class="filtro-group" style="padding:0; margin-bottom:14px;">
                            <label for="att-contratto">Numero Contratto *</label>
                            <input type="text" id="att-contratto"
                                   placeholder="es. +39 333 1234567"
                                   autocomplete="off" required>
                        </div>

                        <div class="filtro-group" style="padding:0; margin-bottom:0;">
                            <label for="att-data">Data di Attivazione *</label>
                            <input type="date" id="att-data" required>
                        </div>
                    </section>

                </div><!-- /.modal-body -->

                <div class="modal-footer">
                    <button type="button" id="att-btn-annulla" class="btn btn-outline">Annulla</button>
                    <button type="submit" id="att-btn-conferma" class="btn btn-primary"
                            style="background-color: var(--green);">
                        Conferma Attivazione
                    </button>
                </div>
            </form>

        </div>
    </div>

</main>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="JavaScript/sim_non_attive.js"></script>

<?php include 'footer.php'; ?>
