<?php include('header.php'); ?>

<!-- ═══════════════════════════════════════════════════════════════════════════
     contratti.php  –  Gestione CRUD ContrattoTelefonico
     Dipende da: jQuery 3.x (caricato in questo file), api_contratti.php
════════════════════════════════════════════════════════════════════════════ -->

<!-- jQuery (caricato solo se non già presente) -->
<script>
    if (typeof jQuery === 'undefined') {
        document.write('<scr' + 'ipt src="https://code.jquery.com/jquery-3.7.1.min.js"><\/scr' + 'ipt>');
    }
</script>

<main>
    <h2>Contratti Telefonici</h2>

    <!-- ── Barra azioni ───────────────────────────────────────────────────── -->
    <div class="toolbar">
        <button id="btn-nuovo" class="btn btn-primary">＋ Nuovo Contratto</button>
        <div class="search-wrap">
            <input type="text" id="search-input" placeholder="Cerca per numero..." autocomplete="off">
            <button id="btn-cerca" class="btn btn-secondary">Cerca</button>
            <button id="btn-reset" class="btn btn-outline">Tutti</button>
        </div>
    </div>

    <!-- ── Messaggio di stato ─────────────────────────────────────────────── -->
    <div id="msg-box" class="msg-box" style="display:none;"></div>

    <!-- ── Tabella risultati ─────────────────────────────────────────────── -->
    <div class="table-wrap">
        <table id="tbl-contratti">
            <thead>
                <tr>
                    <th>Numero</th>
                    <th>Data Attivazione</th>
                    <th>Tipo</th>
                    <th>Minuti Residui</th>
                    <th>Credito Residuo (€)</th>
                    <th>Telefonate</th>
                    <th>SIM Attiva</th>
                    <th class="col-azioni">Azioni</th>
                </tr>
            </thead>
            <tbody id="tbl-body">
                <tr><td colspan="8" class="loading">Caricamento…</td></tr>
            </tbody>
        </table>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════
         MODAL  –  Crea / Modifica Contratto
    ═══════════════════════════════════════════════════════════════════════ -->
    <div id="modal-overlay" class="modal-overlay" style="display:none;">
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-title">

            <div class="modal-header">
                <h3 id="modal-title">Nuovo Contratto</h3>
                <button class="modal-close" id="modal-close-btn" aria-label="Chiudi">&times;</button>
            </div>

            <form id="form-contratto" novalidate>

                <!-- Numero (readonly in edit) -->
                <div class="form-group">
                    <label for="f-numero">Numero di Telefono <span class="req">*</span></label>
                    <input type="text" id="f-numero" name="numero"
                           placeholder="+39 333 1234567" maxlength="20">
                    <span class="field-err" id="err-numero"></span>
                </div>

                <!-- Data attivazione -->
                <div class="form-group">
                    <label for="f-data">Data di Attivazione <span class="req">*</span></label>
                    <input type="date" id="f-data" name="dataAttivazione">
                    <span class="field-err" id="err-data"></span>
                </div>

                <!-- Tipo -->
                <div class="form-group">
                    <label for="f-tipo">Tipo Contratto <span class="req">*</span></label>
                    <select id="f-tipo" name="tipo">
                        <option value="">— seleziona —</option>
                        <option value="ricarica">Ricarica</option>
                        <option value="consumo">Consumo</option>
                    </select>
                    <span class="field-err" id="err-tipo"></span>
                </div>

                <!-- Credito residuo (ricarica) -->
                <div class="form-group" id="row-credito" style="display:none;">
                    <label for="f-credito">Credito Residuo (€) <span class="req">*</span></label>
                    <input type="number" id="f-credito" name="creditoResiduo"
                           step="0.01" min="0" placeholder="es. 12.50">
                    <span class="field-err" id="err-credito"></span>
                </div>

                <!-- Minuti residui (consumo) -->
                <div class="form-group" id="row-minuti" style="display:none;">
                    <label for="f-minuti">Minuti Residui <span class="req">*</span></label>
                    <input type="number" id="f-minuti" name="minutiResidui"
                           step="1" min="0" placeholder="es. 300">
                    <span class="field-err" id="err-minuti"></span>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" id="btn-annulla">Annulla</button>
                    <button type="submit" class="btn btn-primary" id="btn-salva">Salva</button>
                </div>

            </form>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════
         MODAL  –  Conferma Eliminazione
    ═══════════════════════════════════════════════════════════════════════ -->
    <div id="modal-delete-overlay" class="modal-overlay" style="display:none;">
        <div class="modal modal-small" role="dialog" aria-modal="true">
            <div class="modal-header">
                <h3>Conferma Eliminazione</h3>
                <button class="modal-close" id="del-close-btn">&times;</button>
            </div>
            <p id="del-msg" style="margin:1rem 1.5rem;">
                Sei sicuro di voler eliminare il contratto <strong id="del-numero"></strong>?<br>
                <small>L'operazione è irreversibile.</small>
            </p>
            <div class="modal-footer">
                <button class="btn btn-outline" id="del-cancel">Annulla</button>
                <button class="btn btn-danger"  id="del-confirm">Elimina</button>
            </div>
        </div>
    </div>

</main>

<!-- ═══════════════════════════════════════════════════════════════════════════
     STILI  (scoped alla pagina contratti)
════════════════════════════════════════════════════════════════════════════ -->
<style>
/* ── Toolbar ── */
.toolbar {
    display: flex;
    align-items: center;
    gap: .75rem;
    flex-wrap: wrap;
    margin: 1.25rem 0;
}
.search-wrap {
    display: flex;
    gap: .5rem;
    flex-wrap: wrap;
    margin-left: auto;
}
.search-wrap input {
    padding: .45rem .75rem;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: .95rem;
    width: 220px;
}

/* ── Bottoni ── */
.btn {
    cursor: pointer;
    padding: .45rem 1rem;
    border-radius: 4px;
    font-size: .92rem;
    font-weight: 600;
    transition: background .15s, opacity .15s;
    border: 2px solid transparent;
}
.btn-primary  { background:#0066cc; color:#fff; border-color:#0066cc; }
.btn-primary:hover { background:#0052a3; border-color:#0052a3; }
.btn-secondary{ background:#6c757d; color:#fff; border-color:#6c757d; }
.btn-secondary:hover { opacity:.85; }
.btn-outline  { background:transparent; color:#0066cc; border-color:#0066cc; }
.btn-outline:hover { background:#e8f0fc; }
.btn-danger   { background:#cc2200; color:#fff; border-color:#cc2200; }
.btn-danger:hover { background:#a31b00; }
.btn-sm { padding:.28rem .6rem; font-size:.82rem; }

/* ── Messaggio ── */
.msg-box {
    padding: .75rem 1rem;
    border-radius: 4px;
    margin-bottom: 1rem;
    font-weight: 500;
}
.msg-success { background:#d4edda; color:#155724; border:1px solid #c3e6cb; }
.msg-error   { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }

/* ── Tabella ── */
.table-wrap {
    overflow-x: auto;
}
#tbl-contratti {
    width: 100%;
    border-collapse: collapse;
    font-size: .93rem;
}
#tbl-contratti th {
    background: #0066cc;
    color: #fff;
    padding: .65rem .85rem;
    text-align: left;
    white-space: nowrap;
}
#tbl-contratti td {
    padding: .55rem .85rem;
    border-bottom: 1px solid #e0e0e0;
    vertical-align: middle;
}
#tbl-contratti tbody tr:hover { background:#f0f6ff; }
#tbl-contratti .col-azioni { width: 130px; text-align:center; }
#tbl-contratti .loading, #tbl-contratti .no-data {
    text-align: center;
    color: #666;
    padding: 2rem;
}

/* Badge tipo */
.badge {
    display: inline-block;
    padding: .2rem .6rem;
    border-radius: 12px;
    font-size: .78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
}
.badge-ricarica { background:#fff3cd; color:#856404; }
.badge-consumo  { background:#cce5ff; color:#004085; }

/* ── Modal overlay ── */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.5);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: fadeIn .15s ease;
}
@keyframes fadeIn { from{opacity:0} to{opacity:1} }

.modal {
    background: #fff;
    border-radius: 8px;
    width: 100%;
    max-width: 480px;
    box-shadow: 0 8px 32px rgba(0,0,0,.22);
    animation: slideUp .18s ease;
}
.modal-small { max-width: 360px; }
@keyframes slideUp { from{transform:translateY(24px);opacity:0} to{transform:none;opacity:1} }

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e0e0e0;
}
.modal-header h3 { margin: 0; font-size: 1.1rem; }
.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #666;
    line-height: 1;
    padding: 0 .25rem;
}
.modal-close:hover { color:#000; }

/* ── Form ── */
.form-group {
    padding: .75rem 1.5rem 0;
}
.form-group label {
    display: block;
    font-size: .88rem;
    font-weight: 600;
    margin-bottom: .3rem;
    color: #333;
}
.form-group input,
.form-group select {
    width: 100%;
    padding: .48rem .75rem;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: .95rem;
    box-sizing: border-box;
    transition: border-color .15s;
}
.form-group input:focus,
.form-group select:focus {
    border-color: #0066cc;
    outline: none;
    box-shadow: 0 0 0 2px rgba(0,102,204,.2);
}
.form-group input.is-invalid,
.form-group select.is-invalid { border-color: #cc2200; }
.field-err { display:block; color:#cc2200; font-size:.78rem; margin-top:.25rem; min-height:.9rem; }
.req { color:#cc2200; }

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: .75rem;
    padding: 1rem 1.5rem;
    margin-top:.5rem;
    border-top: 1px solid #e0e0e0;
}
</style>

<!-- ═══════════════════════════════════════════════════════════════════════════
     JAVASCRIPT / JQUERY  –  Logica CRUD
════════════════════════════════════════════════════════════════════════════ -->
<script>
$(function () {

    /* ── Configurazione ─────────────────────────────────────────────────── */
    const API = 'api_contratti.php';   // percorso relativo all'API PHP
    let editMode   = false;            // true = stiamo modificando
    let deleteNum  = null;             // numero da eliminare (modal confirm)

    /* ══════════════════════════════════════════════════════════════════════
       UTILITY
    ═════════════════════════════════════════════════════════════════════ */

    /** Mostra messaggio di stato (successo o errore) */
    function showMsg(testo, tipo) {
        const cls = tipo === 'success' ? 'msg-success' : 'msg-error';
        $('#msg-box')
            .removeClass('msg-success msg-error')
            .addClass(cls)
            .text(testo)
            .slideDown(150);
        clearTimeout(window._msgTimer);
        window._msgTimer = setTimeout(() => $('#msg-box').slideUp(200), 4000);
    }

    /** Formatta data ISO → dd/mm/yyyy */
    function fmtData(iso) {
        if (!iso) return '—';
        const p = iso.split('-');
        return p.length === 3 ? `${p[2]}/${p[1]}/${p[0]}` : iso;
    }

    /** Genera il badge HTML per il tipo contratto */
    function badge(tipo) {
        return `<span class="badge badge-${tipo}">${tipo}</span>`;
    }

    /* ══════════════════════════════════════════════════════════════════════
       LETTURA  –  carica tabella
    ═════════════════════════════════════════════════════════════════════ */

    function caricaContratti(filtroNumero) {
        $('#tbl-body').html('<tr><td colspan="8" class="loading">Caricamento…</td></tr>');

        $.ajax({
            url: API,
            method: 'GET',
            data: { action: 'list' },
            dataType: 'json',
            success: function (risposta) {
                if (!risposta.success) {
                    showMsg('Errore nel caricamento: ' + risposta.message, 'error');
                    return;
                }
                let righe = risposta.data;

                // Filtro client-side per numero
                if (filtroNumero) {
                    const q = filtroNumero.toLowerCase();
                    righe = righe.filter(r => r.numero.toLowerCase().includes(q));
                }

                if (righe.length === 0) {
                    $('#tbl-body').html('<tr><td colspan="8" class="no-data">Nessun contratto trovato.</td></tr>');
                    return;
                }

                const html = righe.map(r => {
                    const minuti  = r.tipo === 'consumo' ? r.minutiResidui  : '—';
                    const credito = r.tipo === 'ricarica'
                        ? parseFloat(r.creditoResiduo).toFixed(2) + ' €'
                        : '—';
                    const sim = r.simAttiva
                        ? `<a href="sim_attive.php?codice=${encodeURIComponent(r.simAttiva)}">${r.simAttiva}</a>`
                        : '<em>nessuna</em>';
                    const tel = r.numTelefonate > 0
                        ? `<a href="telefonate.php?contratto=${encodeURIComponent(r.numero)}">${r.numTelefonate}</a>`
                        : '0';

                    return `
                        <tr data-numero="${r.numero}">
                            <td><strong>${r.numero}</strong></td>
                            <td>${fmtData(r.dataAttivazione)}</td>
                            <td>${badge(r.tipo)}</td>
                            <td>${minuti}</td>
                            <td>${credito}</td>
                            <td>${tel}</td>
                            <td>${sim}</td>
                            <td style="text-align:center;">
                                <button class="btn btn-outline btn-sm btn-modifica"
                                        data-numero="${r.numero}"
                                        title="Modifica">✏️</button>
                                <button class="btn btn-danger btn-sm btn-elimina"
                                        data-numero="${r.numero}"
                                        title="Elimina">🗑️</button>
                            </td>
                        </tr>`;
                }).join('');

                $('#tbl-body').html(html);
            },
            error: function () {
                showMsg('Errore di rete durante il caricamento.', 'error');
                $('#tbl-body').html('<tr><td colspan="8" class="no-data">Errore di caricamento.</td></tr>');
            }
        });
    }

    // Primo caricamento
    caricaContratti();

    /* ══════════════════════════════════════════════════════════════════════
       RICERCA
    ═════════════════════════════════════════════════════════════════════ */

    $('#btn-cerca').on('click', function () {
        caricaContratti($('#search-input').val().trim());
    });

    $('#search-input').on('keydown', function (e) {
        if (e.key === 'Enter') $('#btn-cerca').trigger('click');
    });

    $('#btn-reset').on('click', function () {
        $('#search-input').val('');
        caricaContratti();
    });

    /* ══════════════════════════════════════════════════════════════════════
       MODAL FORM  –  apertura / chiusura
    ═════════════════════════════════════════════════════════════════════ */

    /** Apre il modal (crea o modifica) */
    function apriModal(dati) {
        editMode = !!dati;
        resetForm();

        $('#modal-title').text(editMode ? 'Modifica Contratto' : 'Nuovo Contratto');
        $('#f-numero').prop('readonly', editMode);

        if (editMode) {
            $('#f-numero').val(dati.numero);
            $('#f-data').val(dati.dataAttivazione);
            $('#f-tipo').val(dati.tipo).trigger('change');
            if (dati.tipo === 'ricarica') {
                $('#f-credito').val(dati.creditoResiduo);
            } else {
                $('#f-minuti').val(dati.minutiResidui);
            }
        }

        $('#modal-overlay').fadeIn(150);
        (editMode ? $('#f-data') : $('#f-numero')).focus();
    }

    function chiudiModal() {
        $('#modal-overlay').fadeOut(150);
        resetForm();
    }

    /** Azzera il form e rimuove gli errori */
    function resetForm() {
        $('#form-contratto')[0].reset();
        $('#row-credito, #row-minuti').hide();
        $('.field-err').text('');
        $('input, select', '#form-contratto').removeClass('is-invalid');
    }

    // Pulsante "Nuovo"
    $('#btn-nuovo').on('click', () => apriModal(null));

    // Chiudi modal
    $('#modal-close-btn, #btn-annulla').on('click', chiudiModal);
    $('#modal-overlay').on('click', function (e) {
        if ($(e.target).is('#modal-overlay')) chiudiModal();
    });

    // ESC per chiudere
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') {
            chiudiModal();
            chiudiModalDelete();
        }
    });

    /* ── Mostra/nascondi campo in base al tipo ── */
    $('#f-tipo').on('change', function () {
        const v = $(this).val();
        if (v === 'ricarica') {
            $('#row-credito').show();
            $('#row-minuti').hide();
            $('#f-minuti').val('');
        } else if (v === 'consumo') {
            $('#row-minuti').show();
            $('#row-credito').hide();
            $('#f-credito').val('');
        } else {
            $('#row-credito, #row-minuti').hide();
        }
    });

    /* ══════════════════════════════════════════════════════════════════════
       VALIDAZIONE CLIENT-SIDE
    ═════════════════════════════════════════════════════════════════════ */

    function validaForm() {
        let ok = true;

        function err(id, msg) {
            $('#' + id).text(msg).closest('.form-group').find('input,select').addClass('is-invalid');
            ok = false;
        }
        function ok_(id) {
            $('#' + id).text('');
            $('#' + id).closest('.form-group').find('input,select').removeClass('is-invalid');
        }

        const numero = $('#f-numero').val().trim();
        const data   = $('#f-data').val();
        const tipo   = $('#f-tipo').val();

        if (!numero)   err('err-numero', 'Campo obbligatorio');
        else if (!/^\+?[\d\s\-]{7,20}$/.test(numero)) err('err-numero', 'Formato non valido (es. +39 333 1234567)');
        else           ok_('err-numero');

        if (!data)     err('err-data',   'Campo obbligatorio');
        else           ok_('err-data');

        if (!tipo)     err('err-tipo',   'Seleziona un tipo');
        else           ok_('err-tipo');

        if (tipo === 'ricarica') {
            const credito = $('#f-credito').val();
            if (credito === '' || isNaN(credito) || +credito < 0)
                err('err-credito', 'Valore non valido');
            else ok_('err-credito');
        }
        if (tipo === 'consumo') {
            const minuti = $('#f-minuti').val();
            if (minuti === '' || isNaN(minuti) || +minuti < 0 || !Number.isInteger(+minuti))
                err('err-minuti', 'Inserire un numero intero ≥ 0');
            else ok_('err-minuti');
        }

        return ok;
    }

    /* ══════════════════════════════════════════════════════════════════════
       SALVATAGGIO  (CREATE / UPDATE)
    ═════════════════════════════════════════════════════════════════════ */

    $('#form-contratto').on('submit', function (e) {
        e.preventDefault();
        if (!validaForm()) return;

        const tipo = $('#f-tipo').val();
        const payload = {
            numero:          $('#f-numero').val().trim(),
            dataAttivazione: $('#f-data').val(),
            tipo:            tipo,
            creditoResiduo:  tipo === 'ricarica' ? $('#f-credito').val() : null,
            minutiResidui:   tipo === 'consumo'  ? $('#f-minuti').val()  : null
        };

        const action = editMode ? 'update' : 'create';
        $('#btn-salva').prop('disabled', true).text('Salvataggio…');

        $.ajax({
            url: API + '?action=' + action,
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            dataType: 'json',
            success: function (risposta) {
                if (risposta.success) {
                    chiudiModal();
                    showMsg(risposta.message, 'success');
                    caricaContratti();
                } else {
                    showMsg(risposta.message, 'error');
                }
            },
            error: function () {
                showMsg('Errore di rete. Riprovare.', 'error');
            },
            complete: function () {
                $('#btn-salva').prop('disabled', false).text('Salva');
            }
        });
    });

    /* ══════════════════════════════════════════════════════════════════════
       MODIFICA  –  click su riga
    ═════════════════════════════════════════════════════════════════════ */

    // Delegazione eventi su tbody (elementi creati dinamicamente)
    $('#tbl-body').on('click', '.btn-modifica', function () {
        const numero = $(this).data('numero');
        // Recupera i dati aggiornati dal server prima di aprire il form
        $.ajax({
            url: API,
            method: 'GET',
            data: { action: 'get', numero: numero },
            dataType: 'json',
            success: function (risposta) {
                if (risposta.success) apriModal(risposta.data);
                else showMsg('Impossibile caricare il contratto: ' + risposta.message, 'error');
            },
            error: function () { showMsg('Errore di rete.', 'error'); }
        });
    });

    /* ══════════════════════════════════════════════════════════════════════
       ELIMINAZIONE
    ═════════════════════════════════════════════════════════════════════ */

    function chiudiModalDelete() {
        $('#modal-delete-overlay').fadeOut(150);
        deleteNum = null;
    }

    // Apri modal conferma
    $('#tbl-body').on('click', '.btn-elimina', function () {
        deleteNum = $(this).data('numero');
        $('#del-numero').text(deleteNum);
        $('#modal-delete-overlay').fadeIn(150);
    });

    // Chiudi
    $('#del-close-btn, #del-cancel').on('click', chiudiModalDelete);
    $('#modal-delete-overlay').on('click', function (e) {
        if ($(e.target).is('#modal-delete-overlay')) chiudiModalDelete();
    });

    // Conferma eliminazione
    $('#del-confirm').on('click', function () {
        if (!deleteNum) return;
        $(this).prop('disabled', true).text('Eliminazione…');

        $.ajax({
            url: API + '?action=delete',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ numero: deleteNum }),
            dataType: 'json',
            success: function (risposta) {
                chiudiModalDelete();
                if (risposta.success) {
                    showMsg(risposta.message, 'success');
                    caricaContratti();
                } else {
                    showMsg(risposta.message, 'error');
                }
            },
            error: function () {
                chiudiModalDelete();
                showMsg('Errore di rete durante l\'eliminazione.', 'error');
            },
            complete: function () {
                $('#del-confirm').prop('disabled', false).text('Elimina');
            }
        });
    });

}); // fine $(function)
</script>

<?php include('footer.php'); ?>
