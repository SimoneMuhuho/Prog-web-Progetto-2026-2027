/**
 * JavaScript/sim_disattive.js
 * Logica jQuery per la pagina sim-disattivate.php  –  Layout 1
 * Gestisce: caricamento tabella, filtri multipli (client-side), modal dettaglio
 */
$(function () {

    const API = 'PHP/sim_disattive/api_sim_disattive.php';

    /* ══════════════════════════════════════════════════════════════════════
       UTILITY
    ═════════════════════════════════════════════════════════════════════ */

    /** Formatta data ISO (yyyy-mm-dd) → dd/mm/yyyy */
    function fmtData(iso) {
        if (!iso) return '—';
        const p = iso.split('-');
        return p.length === 3 ? `${p[2]}/${p[1]}/${p[0]}` : iso;
    }

    /** Badge colorato per il tipo SIM */
    function badgeSIM(tipo) {
        const cls = {
            'nano':     'badge-nano',
            'micro':    'badge-micro',
            'standard': 'badge-standard',
            'eSIM':     'badge-esim',
        };
        return `<span class="badge ${cls[tipo] ?? ''}">${tipo ?? '—'}</span>`;
    }

    /** Badge colorato per il tipo contratto */
    function badgeContratto(tipo) {
        if (!tipo) return '—';
        return `<span class="badge badge-${tipo}">${tipo}</span>`;
    }

    /** Mostra messaggio di errore (auto-dismiss dopo 4s) */
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
        $('#tbl-body').html('<tr><td colspan="6" class="loading">Caricamento…</td></tr>');

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
                $('#tbl-body').html('<tr><td colspan="6" class="no-data">Errore di caricamento.</td></tr>');
            }
        });
    }

    /* ══════════════════════════════════════════════════════════════════════
       FILTRI CLIENT-SIDE
    ═════════════════════════════════════════════════════════════════════ */

    function applicaFiltri(righe) {
        const codice    = $('#search-codice').val().trim().toLowerCase();
        const tipoSIM   = $('#search-tipo-sim').val();
        const contratto = $('#search-contratto').val().trim().toLowerCase();
        const dataDa    = $('#search-data-disatt-da').val();   // data disattivazione dal
        const dataA     = $('#search-data-disatt-a').val();    // data disattivazione al

        if (codice)    righe = righe.filter(r => r.codice.toLowerCase().includes(codice));
        if (tipoSIM)   righe = righe.filter(r => r.tipoSIM === tipoSIM);
        if (contratto) righe = righe.filter(r => r.eraAssociataA.toLowerCase().includes(contratto));
        if (dataDa)    righe = righe.filter(r => r.dataDisattivazione >= dataDa);
        if (dataA)     righe = righe.filter(r => r.dataDisattivazione <= dataA);

        if (righe.length === 0) {
            $('#tbl-body').html('<tr><td colspan="6" class="no-data">Nessuna SIM disattivata trovata.</td></tr>');
            return;
        }

        const html = righe.map(r => `
            <tr>
                <td><code>${r.codice}</code></td>
                <td>${badgeSIM(r.tipoSIM)}</td>
                <td><strong>${r.eraAssociataA}</strong></td>
                <td>${badgeContratto(r.tipoContratto)}</td>
                <td>${fmtData(r.dataAttivazione)}</td>
                <td>${fmtData(r.dataDisattivazione)}</td>
                <td style="text-align:center;">
                    <button class="btn btn-info btn-sm btn-dettaglio"
                            data-codice="${r.codice}"
                            title="Visualizza dettaglio">🔍 Apri</button>
                </td>
            </tr>`).join('');

        $('#tbl-body').html(html);
    }

    // Primo caricamento
    caricaERicorda();

    /* ══════════════════════════════════════════════════════════════════════
       EVENTI FILTRO SIDEBAR
    ═════════════════════════════════════════════════════════════════════ */

    $('#btn-cerca').on('click', function () {
        applicaFiltri(_tutteLeSIM);
    });

    // Cerca anche premendo Invio su qualsiasi campo del filtro
    $('#search-codice, #search-tipo-sim, #search-contratto, #search-data-disatt-da, #search-data-disatt-a')
        .on('keydown', function (e) {
            if (e.key === 'Enter') applicaFiltri(_tutteLeSIM);
        });

    $('#btn-reset').on('click', function () {
        $('#search-codice').val('');
        $('#search-tipo-sim').val('');
        $('#search-contratto').val('');
        $('#search-data-disatt-da').val('');
        $('#search-data-disatt-a').val('');
        applicaFiltri(_tutteLeSIM);
    });

    /* ══════════════════════════════════════════════════════════════════════
       MODAL DETTAGLIO  –  apertura / chiusura
    ═════════════════════════════════════════════════════════════════════ */

    function apriDettaglio(codice) {
        // Resetta campi
        $('#modal-title').text('Dettaglio SIM Disattivata');
        $('#d-codice, #d-tipo-sim, #d-contratto, #d-tipo-contratto, #d-data-att, #d-data-disatt').text('—');

        $('#modal-overlay').fadeIn(150);
        caricaDettaglioSIM(codice);
    }

    function chiudiModal() {
        $('#modal-overlay').fadeOut(150);
    }

    // Delegazione: click su qualsiasi .btn-dettaglio nella tabella
    $('#tbl-body').on('click', '.btn-dettaglio', function () {
        apriDettaglio($(this).data('codice'));
    });

    $('#modal-close-btn, #btn-chiudi').on('click', chiudiModal);

    // Click fuori dal modale
    $('#modal-overlay').on('click', function (e) {
        if ($(e.target).is('#modal-overlay')) chiudiModal();
    });

    // Tasto Escape
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
                $('#d-contratto').text(d.eraAssociataA);
                $('#d-tipo-contratto').html(badgeContratto(d.tipoContratto));
                $('#d-data-att').text(fmtData(d.dataAttivazione));
                $('#d-data-disatt').text(fmtData(d.dataDisattivazione));
            },
            error: function () {
                showErr('Errore di rete nel caricamento del dettaglio.');
                chiudiModal();
            }
        });
    }

}); // fine $(function)
