/**
 * JavaScript/sim_non_attive.js
 * Gestisce: caricamento tabella, filtri multipli (client-side), modal dettaglio
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
                <td style="text-align:center;">
                    <button class="btn btn-info btn-sm btn-dettaglio"
                            data-codice="${r.codice}"
                            title="Visualizza dettaglio">Apri</button>
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

    function chiudiModal() { $('#modal-overlay').fadeOut(150); }

    $('#tbl-body').on('click', '.btn-dettaglio', function () {
        apriDettaglio($(this).data('codice'));
    });

    $('#modal-close-btn, #btn-chiudi').on('click', chiudiModal);
    $('#modal-overlay').on('click', function (e) {
        if ($(e.target).is('#modal-overlay')) chiudiModal();
    });
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') chiudiModal();
    });

    /* ══════════════════════════════════════════════════════════════════════
       CHIAMATA API PER IL DETTAGLIO
    ═════════════════════════════════════════════════════════════════════ */

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

}); // fine $(function)
