/**
 * JavaScript/sim_non_attive.js
 * Gestisce: caricamento tabella, filtri multipli (client-side),
 *           modal dettaglio, modal attivazione SIM
 */
$(function () {

    const API = 'PHP/sim_non_attive/api_sim_non_attive.php';

    /* ══════════════════════════════════════════════════════════════════════
       UTILITY
    ═════════════════════════════════════════════════════════════════════ */

    function badgeSIM(tipo) {
        const cls = {
            'nano':     'badge-nano',
            'micro':    'badge-micro',
            'standard': 'badge-standard',
            'eSIM':     'badge-esim',
        };
        return `<span class="badge ${cls[tipo] ?? ''}">${tipo ?? '—'}</span>`;
    }

    function showErr(testo) {
        $('#msg-box')
            .removeClass().addClass('msg-box msg-error')
            .text(testo)
            .slideDown(150);
        clearTimeout(window._msgTimer);
        window._msgTimer = setTimeout(() => $('#msg-box').slideUp(200), 4000);
    }

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

    let _tutteLeSIM = [];

    function caricaERicorda() {
        $('#tbl-body').html('<tr><td colspan="3" class="loading">Caricamento…</td></tr>');

        $.ajax({
            url: API,
            method: 'GET',
            data: { action: 'list' },
            dataType: 'json',
            success: function (r) {
                if (!r.success) { showErr('Errore: ' + r.message); return; }
                _tutteLeSIM = r.data;
                applicaFiltri(_tutteLeSIM);
            },
            error: function () {
                showErr('Errore di rete durante il caricamento.');
                $('#tbl-body').html('<tr><td colspan="3" class="no-data">Errore di caricamento.</td></tr>');
            }
        });
    }

    /* ══════════════════════════════════════════════════════════════════════
       FILTRI CLIENT-SIDE
    ═════════════════════════════════════════════════════════════════════ */

    function applicaFiltri(righe) {
        const codice  = $('#search-codice').val().trim().toLowerCase();
        const tipoSIM = $('#search-tipo-sim').val();

        if (codice)  righe = righe.filter(r => (r.codice || '').toString().toLowerCase().includes(codice));
        if (tipoSIM) righe = righe.filter(r => r.tipoSIM === tipoSIM);

        if (righe.length === 0) {
            $('#tbl-body').html('<tr><td colspan="3" class="no-data">Nessuna SIM non attiva trovata.</td></tr>');
            return;
        }

        const html = righe.map(r => `
            <tr>
                <td><code>${r.codice}</code></td>
                <td>${badgeSIM(r.tipoSIM)}</td>
                <td style="text-align:center; white-space:nowrap;">
                    <button class="btn btn-info btn-sm btn-dettaglio"
                            data-codice="${r.codice}"
                            title="Visualizza dettaglio">Dettaglio</button>
                    <button class="btn btn-info btn-sm btn-attiva"
                            data-codice="${r.codice}"
                            data-tipo="${r.tipoSIM}"
                            title="Attiva questa SIM"
                            style="background-color: var(--green); margin-left:6px;">Attiva</button>
                </td>
            </tr>`).join('');

        $('#tbl-body').html(html);
    }

    caricaERicorda();

    /* ══════════════════════════════════════════════════════════════════════
       EVENTI FILTRO SIDEBAR
    ═════════════════════════════════════════════════════════════════════ */

    $('#btn-cerca').on('click', () => applicaFiltri(_tutteLeSIM));

    $('#search-codice, #search-tipo-sim').on('keydown', function (e) {
        if (e.key === 'Enter') applicaFiltri(_tutteLeSIM);
    });

    $('#btn-reset').on('click', function () {
        $('#search-codice, #search-tipo-sim').val('');
        applicaFiltri(_tutteLeSIM);
    });

    /* ══════════════════════════════════════════════════════════════════════
       MODAL DETTAGLIO  –  apertura / chiusura
    ═════════════════════════════════════════════════════════════════════ */

    function apriDettaglio(codice) {
        $('#modal-title').text('Dettaglio SIM non attiva');
        $('#d-codice, #d-tipo-sim').text('—');
        $('#modal-overlay').fadeIn(150);
        caricaDettaglioSIM(codice);
    }

    function chiudiModal() {
        $('#modal-overlay').fadeOut(150);
    }

    $('#tbl-body').on('click', '.btn-dettaglio', function () {
        apriDettaglio($(this).data('codice'));
    });

    $('#modal-close-btn, #btn-chiudi').on('click', chiudiModal);
    $('#modal-overlay').on('click', function (e) {
        if ($(e.target).is('#modal-overlay')) chiudiModal();
    });

    function caricaDettaglioSIM(codice) {
        $.ajax({
            url: API,
            method: 'GET',
            data: { action: 'get', codice: codice },
            dataType: 'json',
            success: function (r) {
                if (!r.success) {
                    showErr('Impossibile caricare il dettaglio: ' + r.message);
                    chiudiModal();
                    return;
                }
                const d = r.data;
                $('#modal-title').text('Dettaglio SIM: ' + d.codice);
                $('#d-codice').text(d.codice);
                $('#d-tipo-sim').html(badgeSIM(d.tipoSIM));
            },
            error: function () {
                showErr('Errore di rete nel caricamento del dettaglio.');
                chiudiModal();
            }
        });
    }

    /* ══════════════════════════════════════════════════════════════════════
       MODAL ATTIVAZIONE SIM
    ═════════════════════════════════════════════════════════════════════ */

    function apriAttivazione(codice, tipoSIM) {
        // Pre-popola il riepilogo
        $('#att-codice-display').text(codice);
        $('#att-tipo-display').html(badgeSIM(tipoSIM));

        // Reset form
        $('#att-codice').val(codice);
        $('#att-contratto').val('');
        $('#att-data').val(new Date().toISOString().split('T')[0]); // oggi come default
        $('#att-errors').hide().text('');

        $('#modal-attiva-overlay').fadeIn(150);
        $('#att-contratto').trigger('focus');
    }

    function chiudiAttivazione() {
        $('#modal-attiva-overlay').fadeOut(150);
    }

    // Apri modal attivazione al click sul tasto "Attiva"
    $('#tbl-body').on('click', '.btn-attiva', function () {
        apriAttivazione($(this).data('codice'), $(this).data('tipo'));
    });

    // Chiusura modal attivazione
    $('#att-close-btn, #att-btn-annulla').on('click', chiudiAttivazione);
    $('#modal-attiva-overlay').on('click', function (e) {
        if ($(e.target).is('#modal-attiva-overlay')) chiudiAttivazione();
    });

    // Submit: invio dati attivazione al server
    $('#att-form').on('submit', function (e) {
        e.preventDefault();

        const codice          = $('#att-codice').val();
        const associataA      = $('#att-contratto').val().trim();
        const dataAttivazione = $('#att-data').val();

        if (!associataA) {
            $('#att-errors').text('Inserisci il numero di contratto.').show();
            return;
        }
        if (!dataAttivazione) {
            $('#att-errors').text('Inserisci la data di attivazione.').show();
            return;
        }

        $('#att-btn-conferma').prop('disabled', true).text('Attivazione…');

        $.ajax({
            url: API,
            method: 'POST',
            data: {
                action: 'activate',
                codice: codice,
                associataA: associataA,
                dataAttivazione: dataAttivazione
            },
            dataType: 'json',
            success: function (r) {
                $('#att-btn-conferma').prop('disabled', false).text('Conferma Attivazione');
                if (!r.success) {
                    $('#att-errors').text('Errore: ' + r.message).show();
                    return;
                }
                chiudiAttivazione();
                // Rimuovi dalla cache locale e ridisegna senza ricaricare dal server
                _tutteLeSIM = _tutteLeSIM.filter(s => s.codice !== codice);
                applicaFiltri(_tutteLeSIM);
                showOk('SIM ' + codice + ' attivata con successo!');
            },
            error: function () {
                $('#att-btn-conferma').prop('disabled', false).text('Conferma Attivazione');
                $('#att-errors').text('Errore di rete. Riprova.').show();
            }
        });
    });

    /* ══════════════════════════════════════════════════════════════════════
       TASTO ESCAPE  –  chiude qualsiasi modal aperta
    ═════════════════════════════════════════════════════════════════════ */

    $(document).on('keydown', function (e) {
        if (e.key !== 'Escape') return;
        if ($('#modal-overlay').is(':visible'))        chiudiModal();
        if ($('#modal-attiva-overlay').is(':visible')) chiudiAttivazione();
    });

}); // fine $(function)
