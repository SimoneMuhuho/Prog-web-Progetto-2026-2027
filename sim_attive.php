<?php 
    $pagina_corrente = 'sim_attive';
    include 'header.php';
?>

<main class="layout-1">

    <!-- Colonna sinistra: Filtro / Ricerca -->
    <aside class="sidebar-filtro">
        <h3>Ricerca</h3>

        <div class="filtro-group">
            <label for="search-codice">Codice SIM</label>
            <input type="text" id="search-codice" placeholder="es. 4542…" autocomplete="off">
        </div>

        <div class="filtro-group">
            <label for="search-numero">N° Contratto</label>
            <input type="text" id="search-numero" placeholder="es. +39 335…" autocomplete="off">
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

    <!-- Colonna destra: Contenuto / Risultati -->
    <section class="contenuto-risultati">
        <h2>SIM Attive</h2>

        <div id="msg-box" class="msg-box" style="display:none;"></div>

        <div class="table-wrap">
            <table id="tbl-sim-attive">
                <thead>
                    <tr>
                        <th>Codice SIM</th>
                        <th>N° Contratto</th>
                        <th>Tipo SIM</th>
                        <th>Data Attivazione</th>
                        <th style="text-align:center;">Azioni</th>
                    </tr>
                </thead>
                <tbody id="tbl-body" class="tbl-body">
                    <tr><td colspan="5" class="loading">Caricamento…</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <!-- MODAL  –  Dettaglio SIM Attiva -->
    <div id="modal-overlay" class="modal-overlay" style="display:none;">
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-title">

            <div class="modal-header">
                <h3 id="modal-title">Dettaglio SIM Attiva</h3>
                <button class="modal-close" id="modal-close-btn" aria-label="Chiudi">&times;</button>
            </div>

            <div class="modal-body">
                <section class="detail-section">
                    <h4>Dati SIM</h4>
                    <dl class="detail-grid">
                        <dt>Codice SIM</dt>       <dd id="d-codice">—</dd>
                        <dt>N° Contratto</dt>      <dd id="d-numero">—</dd>
                        <dt>Tipo SIM</dt>          <dd id="d-tipo-sim">—</dd>
                        <dt>Data Attivazione</dt>  <dd id="d-data">—</dd>
                    </dl>
                </section>
            </div>

            <div class="modal-footer">
                <button class="btn btn-outline" id="btn-chiudi">Chiudi</button>
            </div>

        </div>
    </div>

    <!-- MODAL  –  Disattivazione SIM -->
    <div id="modal-disattiva-overlay" class="modal-overlay" style="display:none;">
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="dis-modal-title">

            <div class="modal-header">
                <h3 id="dis-modal-title">Disattiva SIM</h3>
                <button class="modal-close" id="dis-close-btn" aria-label="Chiudi">&times;</button>
            </div>

            <form id="dis-form">
                <div class="modal-body">

                    <div id="dis-errors" class="msg-box msg-error" style="display:none;"></div>

                    <!-- Riepilogo SIM che si sta disattivando -->
                    <section class="detail-section">
                        <h4>SIM da disattivare</h4>
                        <dl class="detail-grid">
                            <dt>Codice SIM</dt>      <dd id="dis-codice-display">—</dd>
                            <dt>Tipo SIM</dt>         <dd id="dis-tipo-display">—</dd>
                            <dt>N° Contratto</dt>     <dd id="dis-numero-display">—</dd>
                            <dt>Attiva dal</dt>       <dd id="dis-att-display">—</dd>
                        </dl>
                    </section>

                    <!-- Input richiesti -->
                    <section class="detail-section" style="margin-bottom:0;">
                        <h4>Data di disattivazione</h4>

                        <input type="hidden" id="dis-codice">
                        <input type="hidden" id="dis-data-attivazione">

                        <div class="filtro-group" style="padding:0; margin-bottom:0;">
                            <label for="dis-data">Data di Disattivazione *</label>
                            <input type="date" id="dis-data" required>
                        </div>
                    </section>

                </div><!-- /.modal-body -->

                <div class="modal-footer">
                    <button type="button" id="dis-btn-annulla" class="btn btn-outline">Annulla</button>
                    <button type="submit" id="dis-btn-conferma" class="btn btn-primary"
                            style="background-color: #e07b35;">
                        Conferma Disattivazione
                    </button>
                </div>
            </form>

        </div>
    </div>

</main>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="JavaScript/sim_attive.js"></script>

<?php include 'footer.php'; ?>
