<?php include('header.php'); ?>

<main>
    <h2>Contratti Telefonici</h2>

    <!-- ── Barra ricerca ─────────────────────────────────────────────────── -->
    <div class="toolbar">
        <div class="search-wrap">
            <input type="text" id="search-input" placeholder="Cerca per numero..." autocomplete="off">
            <button id="btn-cerca" class="btn btn-secondary">Cerca</button>
            <button id="btn-reset" class="btn btn-outline">Tutti</button>
        </div>
    </div>

    <!-- ── Messaggio di stato ─────────────────────────────────────────────── -->
    <div id="msg-box" class="msg-box" style="display:none;"></div>

    <!-- ── Tabella contratti ─────────────────────────────────────────────── -->
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
                    <th class="col-dettaglio">Dettaglio</th>
                </tr>
            </thead>
            <tbody id="tbl-body">
                <tr><td colspan="8" class="loading">Caricamento…</td></tr>
            </tbody>
        </table>
    </div>

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

<style>
/* ── Toolbar ── */
.toolbar { display:flex; align-items:center; gap:.75rem; flex-wrap:wrap; margin:1.25rem 0; }
.search-wrap { display:flex; gap:.5rem; flex-wrap:wrap; margin-left:auto; }
.search-wrap input {
    padding:.45rem .75rem; border:1px solid #ccc;
    border-radius:4px; font-size:.95rem; width:220px;
}

/* ── Bottoni ── */
.btn {
    cursor:pointer; padding:.45rem 1rem; border-radius:4px;
    font-size:.92rem; font-weight:600;
    transition:background .15s, opacity .15s; border:2px solid transparent;
}
.btn-secondary { background:#6c757d; color:#fff; border-color:#6c757d; }
.btn-secondary:hover { opacity:.85; }
.btn-outline { background:transparent; color:#0066cc; border-color:#0066cc; }
.btn-outline:hover { background:#e8f0fc; }
.btn-info { background:#0099aa; color:#fff; border-color:#0099aa; }
.btn-info:hover { background:#007a88; }
.btn-sm { padding:.28rem .6rem; font-size:.82rem; }

/* ── Messaggio errore ── */
.msg-box { padding:.75rem 1rem; border-radius:4px; margin-bottom:1rem; font-weight:500; }
.msg-error { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }

/* ── Tabella principale ── */
.table-wrap { overflow-x:auto; }
#tbl-contratti { width:100%; border-collapse:collapse; font-size:.93rem; }
#tbl-contratti th {
    background:#0066cc; color:#fff;
    padding:.65rem .85rem; text-align:left; white-space:nowrap;
}
#tbl-contratti td { padding:.55rem .85rem; border-bottom:1px solid #e0e0e0; vertical-align:middle; }
#tbl-contratti tbody tr:hover { background:#f0f6ff; }
.col-dettaglio { width:90px; text-align:center; }
.loading, .no-data { text-align:center; color:#666; padding:2rem; }

/* Badge tipo */
.badge { display:inline-block; padding:.2rem .6rem; border-radius:12px; font-size:.78rem; font-weight:700; text-transform:uppercase; }
.badge-ricarica { background:#fff3cd; color:#856404; }
.badge-consumo  { background:#cce5ff; color:#004085; }

/* ── Modal overlay ── */
.modal-overlay {
    position:fixed; inset:0; background:rgba(0,0,0,.5);
    z-index:1000; display:flex; align-items:center; justify-content:center;
    animation:fadeIn .15s ease;
}
@keyframes fadeIn { from{opacity:0} to{opacity:1} }

.modal {
    background:#fff; border-radius:8px; width:100%; max-width:480px;
    box-shadow:0 8px 32px rgba(0,0,0,.22); animation:slideUp .18s ease;
    display:flex; flex-direction:column; max-height:90vh;
}
.modal-large { max-width:740px; }
@keyframes slideUp { from{transform:translateY(24px);opacity:0} to{transform:none;opacity:1} }

.modal-header {
    display:flex; align-items:center; justify-content:space-between;
    padding:1rem 1.5rem; border-bottom:1px solid #e0e0e0; flex-shrink:0;
}
.modal-header h3 { margin:0; font-size:1.1rem; }
.modal-close { background:none; border:none; font-size:1.5rem; cursor:pointer; color:#666; }
.modal-close:hover { color:#000; }

.modal-body { overflow-y:auto; padding:0 1.5rem; flex:1; }

.modal-footer {
    display:flex; justify-content:flex-end; gap:.75rem;
    padding:1rem 1.5rem; border-top:1px solid #e0e0e0; flex-shrink:0;
}

/* ── Sezioni dettaglio ── */
.detail-section { margin:1.25rem 0; }
.detail-section h4 {
    margin:0 0 .6rem; font-size:.95rem; color:#0066cc;
    border-bottom:1px solid #e0e0e0; padding-bottom:.3rem;
}
.detail-grid {
    display:grid; grid-template-columns:140px 1fr;
    gap:.35rem .75rem; margin:0; font-size:.92rem;
}
.detail-grid dt { font-weight:600; color:#555; }
.detail-grid dd { margin:0; }

/* Tabelle interne al modal */
.tbl-detail { width:100%; border-collapse:collapse; font-size:.88rem; }
.tbl-detail th { background:#f0f6ff; color:#0066cc; padding:.45rem .7rem; text-align:left; }
.tbl-detail td { padding:.4rem .7rem; border-bottom:1px solid #eee; }
.tbl-detail .no-data, .tbl-detail .loading { text-align:center; color:#888; padding:.75rem; }
</style>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="JavaScript/contratti.js"></script>

<?php include('footer.php'); ?>
