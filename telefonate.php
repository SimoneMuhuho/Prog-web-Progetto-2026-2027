<?php
    $pagina_corrente = 'telefonate';
    include 'header.php';
?>

<main class="layout-1">

    <aside class="sidebar-filtro">
        <h3>Ricerca & Filtri</h3>
        
        <div id="form-filtri-telefonate">
            <div class="filtro-group">
                <label for="search-contratto">N° Contratto</label>
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

            <div class="sidebar-results-footer">
                <div class="results-num">
                    <span class="label">Risultati</span>
                    <span class="count" id="contatore-risultati">0</span>
                </div>
            </div>
            
        </div>
    </aside>

    <section class="contenuto-risultati">
        
        <div class="results-header">
            <h2>Registro Telefonate</h2>
            <button type="button" id="btn-apri-crea" class="btn btn-crea">Nuova Telefonata</button>
        </div>
        
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th class="text-left">N° Contratto</th>
                        <th class="text-left">Tipo</th>
                        <th class="text-right">Data</th>
                        <th class="text-right">Ora</th>
                        <th class="text-right">Durata</th>
                        <th class="text-right">Costo</th>
                        <th class="text-center">Azioni</th>
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

<div id="modal-crea-overlay" class="modal-overlay" style="display: none;">
    <div class="modal">
        <div class="modal-header">
            <h3>Registra Nuova Telefonata</h3>
            <button type="button" id="crea-close-btn" class="modal-close">&times;</button>
        </div>
        <form id="crea-form">
            <div class="modal-body">
                <div id="crea-errors" class="msg-box msg-error" style="display: none;"></div>
                
                <div class="filtro-group" style="padding: 0; margin-bottom: 15px;">
                    <label for="crea-effettuataDa">N° Contratto</label>
                    <input type="text" id="crea-effettuataDa" required placeholder="es. 3605188442" autocomplete="off">
                    <small id="crea-contratto-info" style="display:none; margin-top: 5px; font-weight: bold;"></small>
                </div>

                <div class="filtro-group-inline" style="margin-bottom: 15px;">
                    <div class="filtro-group-half">
                        <label for="crea-data">Data Chiamata</label>
                        <input type="date" id="crea-data" required>
                    </div>
                    <div class="filtro-group-half">
                        <label for="crea-ora">Ora</label>
                        <input type="time" id="crea-ora" step="1" required>
                    </div>
                </div>
                
                <div class="filtro-group" style="padding: 0; margin-bottom: 15px;">
                    <label for="crea-durata">Durata (in secondi)</label>
                    <input type="number" id="crea-durata" required min="1" placeholder="es. 120">
                </div>
                
                <div class="filtro-group" id="wrapper-crea-costo" style="padding: 0; margin-bottom: 5px;">
                    <label for="crea-costo">Costo (€)</label>
                    <input type="number" id="crea-costo" step="0.01" required min="0" placeholder="es. 1.50">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" id="crea-btn-annulla" class="btn btn-outline">Annulla</button>
                <button type="submit" id="crea-btn-salva" class="btn btn-primary">Salva</button>
            </div>
        </form>
    </div>
</div>

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
                
                <div class="filtro-group" id="wrapper-mod-costo" style="padding: 0; margin-bottom: 5px;">
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
            <p><strong id="del-label" style="color: var(--red);"></strong></p>
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