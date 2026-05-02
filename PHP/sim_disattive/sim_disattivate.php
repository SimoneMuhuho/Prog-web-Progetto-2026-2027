<?php include 'header.php'; ?>
<link rel="stylesheet" href="CSS/contratti.css">

<main class="layout-1">

    <!-- ── Colonna sinistra: Filtro / Ricerca ────────────────────────────── -->
    <aside class="sidebar-filtro">
        <h3>Ricerca</h3>

        <div class="filtro-group">
            <label for="search-codice">Codice SIM</label>
            <input type="text" id="search-codice" placeholder="es. 6261142…" autocomplete="off">
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

        <div class="filtro-group">
            <label for="search-contratto">N° Contratto</label>
            <input type="text" id="search-contratto" placeholder="es. +39 335…" autocomplete="off">
        </div>

        <div class="filtro-group">
            <label for="search-data-disatt-da">Disattivazione dal</label>
            <input type="date" id="search-data-disatt-da">
        </div>

        <div class="filtro-group">
            <label for="search-data-disatt-a">Disattivazione al</label>
            <input type="date" id="search-data-disatt-a">
        </div>

        <div class="filtro-actions">
            <button id="btn-cerca" class="btn btn-primary btn-block">Cerca</button>
            <button id="btn-reset" class="btn btn-outline btn-block">Azzera</button>
        </div>
    </aside>

    <!-- ── Colonna destra: Contenuto / Risultati ─────────────────────────── -->
    <section class="contenuto-risultati">
        <h2>SIM Disattivate</h2>

        <div id="msg-box" class="msg-box" style="display:none;"></div>

        <div class="table-wrap">
            <table id="tbl-sim-disattive">
                <thead>
                    <tr>
                        <th>Codice SIM</th>
                        <th>Tipo SIM</th>
                        <th>N° Contratto</th>
                        <th>Tipo Contratto</th>
                        <th>Data Attivazione</th>
                        <th>Data Disattivazione</th>
                        <th>Dettaglio</th>
                    </tr>
                </thead>
                <tbody id="tbl-body">
                    <tr><td colspan="7" class="loading">Caricamento…</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <!-- ══════════════════════════════════════════════════════════════════════
         MODAL  –  Dettaglio SIM Disattivata
    ═══════════════════════════════════════════════════════════════════════ -->
    <div id="modal-overlay" class="modal-overlay" style="display:none;">
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-title">

            <div class="modal-header">
                <h3 id="modal-title">Dettaglio SIM Disattivata</h3>
                <button class="modal-close" id="modal-close-btn" aria-label="Chiudi">&times;</button>
            </div>

            <div class="modal-body">

                <section class="detail-section">
                    <h4>Dati SIM</h4>
                    <dl class="detail-grid">
                        <dt>Codice SIM</dt>        <dd id="d-codice">—</dd>
                        <dt>Tipo SIM</dt>           <dd id="d-tipo-sim">—</dd>
                        <dt>N° Contratto</dt>       <dd id="d-contratto">—</dd>
                        <dt>Tipo Contratto</dt>     <dd id="d-tipo-contratto">—</dd>
                        <dt>Data Attivazione</dt>   <dd id="d-data-att">—</dd>
                        <dt>Data Disattivazione</dt><dd id="d-data-disatt">—</dd>
                    </dl>
                </section>

            </div><!-- /.modal-body -->

            <div class="modal-footer">
                <button class="btn btn-outline" id="btn-chiudi">Chiudi</button>
            </div>

        </div>
    </div>

</main>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="JavaScript/sim_disattive.js"></script>

<?php include 'footer.php'; ?>
