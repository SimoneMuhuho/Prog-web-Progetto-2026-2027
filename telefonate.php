<?php
    $pagina_corrente = 'telefonate';
    include 'header.php';
?>

<main class="layout-1">

    <aside class="sidebar-filtro">
        <h3>Ricerca & Filtri</h3>
        
        <div id="form-filtri-telefonate">
            <div class="filtro-group">
                <label for="search-contratto">Numero SIM</label>
                <input type="text" id="search-contratto" placeholder="es. 3605188442…" autocomplete="off">
            </div>

            <div class="filtro-group">
                <label for="search-tipo-contratto">Tipo Contratto</label>
                <select id="search-tipo-contratto">
                    <option value="">Tutti</option>
                    <option value="ricarica">Ricarica</option>
                    <option value="consumo">Consumo</option>
                </select>
            </div>

            <div class="filtro-group-inline">
                <div class="filtro-group-half">
                    <label for="search-data-da">Chiamate dal</label>
                    <input type="date" id="search-data-da">
                </div>

                <div class="filtro-group-half">
                    <label for="search-data-a">Chiamate al</label>
                    <input type="date" id="search-data-a">
                </div>
            </div>

            <div class="filtro-group">
                <label for="search-costo-max">Costo Massimo (€)</label>
                <input type="number" id="search-costo-max" step="0.01" placeholder="es. 5.00" min="0">
            </div>

            <div class="filtro-actions">
                <button type="button" id="btn-cerca" class="btn btn-primary btn-block">Cerca</button>
                <button type="button" id="btn-reset" class="btn btn-outline btn-block">Azzera</button>
            </div>
        </div>
    </aside>

    <section class="contenuto-risultati">
        <h2>Registro Telefonate</h2>
        
        <div id="msg-box"></div>
        
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Numero SIM</th>
                        <th>Tipo</th>
                        <th>Data</th>
                        <th>Ora</th>
                        <th>Durata</th>
                        <th>Costo</th>
                        <th style="text-align: center;">Azioni</th>
                    </tr>
                </thead>
                <tbody id="tbl-body" class="tbl-body">
                    <tr>
                        <td colspan="7" class="loading">Caricamento…</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

</main>

<div id="modal-modifica-overlay" class="modal-overlay" style="display: none;">
    <div class="modal">
        <div class="modal-header">
            <h3>Modifica Telefonata</h3>
            <button type="button" id="mod-close-btn" class="modal-close">&times;</button>
        </div>
        <form id="mod-form">
            <div class="modal-body">
                <div id="mod-errors" class="msg-box msg-error" style="display: none;"></div>
                <input type="hidden" id="mod-id">
                
                <div class="filtro-group" style="padding: 0; margin-bottom: 15px;">
                    <label for="mod-durata">Durata (in secondi)</label>
                    <input type="number" id="mod-durata" required min="1">
                </div>
                
                <div class="filtro-group" style="padding: 0; margin-bottom: 5px;">
                    <label for="mod-costo">Costo (€)</label>
                    <input type="number" id="mod-costo" step="0.01" required min="0">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" id="mod-btn-annulla" class="btn btn-outline">Annulla</button>
                <button type="submit" id="mod-btn-salva" class="btn btn-primary">Salva</button>
            </div>
        </form>
    </div>
</div>

<div id="modal-elimina-overlay" class="modal-overlay" style="display: none;">
    <div class="modal" style="max-width: 450px;">
        <div class="modal-header">
            <h3>Conferma Eliminazione</h3>
            <button type="button" id="del-close-btn" class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <p>Sei sicuro di voler eliminare definitivamente questa telefonata?</p>
            <p><strong id="del-label" style="color: #d65b45;"></strong></p>
        </div>
        <div class="modal-footer">
            <button type="button" id="del-btn-annulla" class="btn btn-outline">No, annulla</button>
            <button type="button" id="del-btn-conferma" class="btn btn-primary">Sì, elimina</button>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="JavaScript/telefonate.js"></script>

<?php include 'footer.php'; ?>