/**
 * JavaScript/telefonate.js
 * Logica jQuery per la pagina telefonate.php  –  Layout 1
 * Gestisce: caricamento tabella, filtri multipli (client-side),
 *           modal modifica (durata + costo), modal conferma eliminazione
 */
$(function () {

    const API = 'PHP/telefonate/api_telefonate.php';

    /* ══════════════════════════════════════════════════════════════════════
       UTILITY
    ═════════════════════════════════════════════════════════════════════ */

    /** Formatta data ISO (yyyy-mm-dd) → dd/mm/yyyy */
    function fmtData(iso) {
        if (!iso) return '—';
        const p = iso.split('-');
        return p.length === 3 ? `${p[2]}/${p[1]}/${p[0]}` : iso;
    }

    /** Formatta durata in secondi → mm:ss  (es. 125 → "2:05") */
    function fmtDurata(secondi) {
        if (!secondi && secondi !== 0) return '—';
        const s  = parseInt(secondi, 10);
        const mm = Math.floor(s / 60);
        const ss = String(s % 60).padStart(2, '0');
        return `${mm}:${ss}`;
    }

    /** Formatta costo in euro con 2 decimali */
    function fmtCosto(valore) {
        if (valore === null || valore === undefined || valore === '') return '—';
        return parseFloat(valore).toFixed(2) + ' €';
    }

    /** Badge colorato per il tipo contratto */
    function badgeContratto(tipo) {
        if (!tipo) return '—';
        return `<span class="badge badge-${tipo}">${tipo}</span>`;
    }

    /** Mostra messaggio di errore (auto-dismiss dopo 4 s) */
    function showErr(testo) {
        $('#msg-box')
            .removeClass().addClass('msg-box msg-error')
            .text(testo)
            .slideDown(150);
        clearTimeout(window._msgTimer);
        window._msgTimer = setTimeout(() => $('#msg-box').slideUp(200), 4000);
    }

    /** Mostra messaggio di successo (auto-dismiss dopo 3 s) */
    function showOk(testo) {
        $('#msg-box')
            .removeClass().addClass('msg-box msg-success')
            .text(testo)
            .slideDown(150);
        clearTimeout(window._msgTimer);
        window._msgTimer = setTimeout(() => $('#msg-box').slideUp(200), 3000);
    }

    /* ══════════════════════════════════════════════════════════════════════
       CARICAMENTO E CACHE
    ═════════════════════════════════════════════════════════════════════ */

    let _tutteLeTelefonate = [];

    function caricaERicorda() {
        $('#tbl-body').html('<tr><td colspan="7" class="loading">Caricamento…</td></tr>');

        $.ajax({
            url: API,
            method: 'GET',
            data: { action: 'list' },
            dataType: 'json',
            success: function (r) {
                if (!r.success) { showErr('Errore: ' + r.message); return; }
                _tutteLeTelefonate = r.data;
                applicaFiltri(_tutteLeTelefonate);
            },
            error: function () {
                showErr('Errore di rete durante il caricamento.');
                $('#tbl-body').html('<tr><td colspan="7" class="no-data">Errore di caricamento.</td></tr>');
            }
        });
    }

    /* ══════════════════════════════════════════════════════════════════════
       FILTRI CLIENT-SIDE
    ═════════════════════════════════════════════════════════════════════ */

    function applicaFiltri(righe) {
        const contratto = $('#search-contratto').val().trim().toLowerCase();
        const tipoContr = $('#search-tipo-contratto').val();
        const dataDa    = $('#search-data-da').val();
        const dataA     = $('#search-data-a').val();
        const costoMax  = $('#search-costo-max').val();

        if (contratto) righe = righe.filter(r =>
            (r.effettuataDa || '').toString().toLowerCase().includes(contratto)
        );
        if (tipoContr) righe = righe.filter(r => r.tipoContratto === tipoContr);
        if (dataDa)    righe = righe.filter(r => r.data >= dataDa);
        if (dataA)     righe = righe.filter(r => r.data <= dataA);
        if (costoMax)  righe = righe.filter(r => parseFloat(r.costo) <= parseFloat(costoMax));

        if (righe.length === 0) {
            $('#tbl-body').html('<tr><td colspan="7" class="no-data">Nessuna telefonata trovata.</td></tr>');
            return;
        }

        const html = righe.map(r => `
            <tr data-id="${r.id}">
                <td><strong>${r.effettuataDa}</strong></td>
                <td>${badgeContratto(r.tipoContratto)}</td>
                <td>${fmtData(r.data)}</td>
                <td>${r.ora ?? '—'}</td>
                <td class="cell-durata">${fmtDurata(r.durata)}</td>
                <td class="cell-costo">${fmtCosto(r.costo)}</td>
                <td style="text-align:center; white-space:nowrap;">
                    <button class="btn btn-info btn-modifica"
                            data-id="${r.id}"
                            data-durata="${r.durata}"
                            data-costo="${r.costo}"
                            title="Modifica durata e costo"></button>
                            
                    <button class="btn btn-info btn-elimina"
                            data-id="${r.id}"
                            data-label="${r.effettuataDa} – ${fmtData(r.data)} ${r.ora ?? ''}"
                            title="Elimina telefonata"></button>
                </td>
            </tr>`).join('');

        $('#tbl-body').html(html);
    }

    // Primo caricamento
    caricaERicorda();

    /* ══════════════════════════════════════════════════════════════════════
       EVENTI FILTRO SIDEBAR
    ═════════════════════════════════════════════════════════════════════ */

    $('#btn-cerca').on('click', () => applicaFiltri(_tutteLeTelefonate));

    $('#search-contratto, #search-tipo-contratto, #search-data-da, #search-data-a, #search-costo-max')
        .on('keydown', function (e) {
            if (e.key === 'Enter') applicaFiltri(_tutteLeTelefonate);
        });

    $('#btn-reset').on('click', function () {
        $('#search-contratto, #search-tipo-contratto, #search-data-da, #search-data-a, #search-costo-max').val('');
        applicaFiltri(_tutteLeTelefonate);
    });

    /* ══════════════════════════════════════════════════════════════════════
       MODAL MODIFICA  –  durata (secondi) e costo
    ═════════════════════════════════════════════════════════════════════ */

    /** Apre il modal di modifica pre-compilato con i valori attuali */
    function apriModifica(id, durata, costo) {
        $('#mod-id').val(id);
        $('#mod-durata').val(durata);
        $('#mod-costo').val(parseFloat(costo).toFixed(2));
        $('#mod-errors').hide().text('');
        $('#modal-modifica-overlay').fadeIn(150);
        $('#mod-durata').trigger('focus');
    }

    function chiudiModifica() {
        $('#modal-modifica-overlay').fadeOut(150);
    }

    // Apri modifica al click sul pulsante ✏️
    $('#tbl-body').on('click', '.btn-modifica', function () {
        apriModifica(
            $(this).data('id'),
            $(this).data('durata'),
            $(this).data('costo')
        );
    });

    // Chiusura modal modifica
    $('#mod-close-btn, #mod-btn-annulla').on('click', chiudiModifica);
    $('#modal-modifica-overlay').on('click', function (e) {
        if ($(e.target).is('#modal-modifica-overlay')) chiudiModifica();
    });

    /** Salva le modifiche via POST */
    $('#mod-form').on('submit', function (e) {
        e.preventDefault();

        const id     = $('#mod-id').val();
        const durata = parseInt($('#mod-durata').val(), 10);
        const costo  = parseFloat($('#mod-costo').val());

        // Validazione client-side
        if (isNaN(durata) || durata < 1) {
            $('#mod-errors').text('La durata deve essere un numero intero positivo (in secondi).').show();
            return;
        }
        if (isNaN(costo) || costo < 0) {
            $('#mod-errors').text('Il costo deve essere un numero positivo.').show();
            return;
        }

        $('#mod-btn-salva').prop('disabled', true).text('Salvataggio…');

        $.ajax({
            url: API,
            method: 'POST',
            data: { action: 'update', id, durata, costo: costo.toFixed(4) },
            dataType: 'json',
            success: function (r) {
                $('#mod-btn-salva').prop('disabled', false).text('Salva');
                if (!r.success) {
                    $('#mod-errors').text('Errore: ' + r.message).show();
                    return;
                }
                chiudiModifica();
                // Aggiorna la cache e ridisegna senza ricaricare dal server
                const rec = _tutteLeTelefonate.find(t => String(t.id) === String(id));
                if (rec) { rec.durata = durata; rec.costo = costo.toFixed(4); }
                applicaFiltri(_tutteLeTelefonate);
                showOk('Telefonata #' + id + ' aggiornata con successo.');
            },
            error: function () {
                $('#mod-btn-salva').prop('disabled', false).text('Salva');
                $('#mod-errors').text('Errore di rete. Riprova.').show();
            }
        });
    });

    /* ══════════════════════════════════════════════════════════════════════
       MODAL CONFERMA ELIMINAZIONE
    ═════════════════════════════════════════════════════════════════════ */

    let _idDaEliminare = null;

    /** Apre il pop-up di conferma eliminazione */
    function apriConfermaElimina(id, label) {
        _idDaEliminare = id;
        $('#del-label').text(label);
        $('#modal-elimina-overlay').fadeIn(150);
    }

    function chiudiConfermaElimina() {
        _idDaEliminare = null;
        $('#modal-elimina-overlay').fadeOut(150);
    }

    // Apri conferma al click sul pulsante 🗑️
    $('#tbl-body').on('click', '.btn-elimina', function () {
        apriConfermaElimina(
            $(this).data('id'),
            $(this).data('label')
        );
    });

    // Chiusura senza eliminare
    $('#del-close-btn, #del-btn-annulla').on('click', chiudiConfermaElimina);
    $('#modal-elimina-overlay').on('click', function (e) {
        if ($(e.target).is('#modal-elimina-overlay')) chiudiConfermaElimina();
    });

    // Conferma eliminazione
    $('#del-btn-conferma').on('click', function () {
        if (!_idDaEliminare) return;

        const id = _idDaEliminare;
        $('#del-btn-conferma').prop('disabled', true).text('Eliminazione…');

        $.ajax({
            url: API,
            method: 'POST',
            data: { action: 'delete', id },
            dataType: 'json',
            success: function (r) {
                $('#del-btn-conferma').prop('disabled', false).text('Sì, elimina');
                if (!r.success) {
                    chiudiConfermaElimina();
                    showErr('Errore nell\'eliminazione: ' + r.message);
                    return;
                }
                chiudiConfermaElimina();
                // Rimuove dalla cache e ridisegna
                _tutteLeTelefonate = _tutteLeTelefonate.filter(t => String(t.id) !== String(id));
                applicaFiltri(_tutteLeTelefonate);
                showOk('Telefonata #' + id + ' eliminata con successo.');
            },
            error: function () {
                $('#del-btn-conferma').prop('disabled', false).text('Sì, elimina');
                chiudiConfermaElimina();
                showErr('Errore di rete durante l\'eliminazione.');
            }
        });
    });

    /* ══════════════════════════════════════════════════════════════════════
       CHIUSURA GENERICA CON TASTO ESCAPE
    ═════════════════════════════════════════════════════════════════════ */

    $(document).on('keydown', function (e) {
        if (e.key !== 'Escape') return;
        if ($('#modal-modifica-overlay').is(':visible'))  chiudiModifica();
        if ($('#modal-elimina-overlay').is(':visible'))   chiudiConfermaElimina();
    });

}); // fine $(function)
