/**
 * JavaScript/sim_attive.js
 * Gestisce: caricamento tabella, filtri multipli (client-side),
 *           modal dettaglio, modal disattivazione SIM
 */
$(function () {

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('codice')) {
        $('#search-codice').val(urlParams.get('codice'));
    }

    const API = 'PHP/sim_attive/api_sim_attive.php';

    /* ══════════════════════════════════════════════════════════════════════
       UTILITY
    ═════════════════════════════════════════════════════════════════════ */

    function fmtData(iso) {
        if (!iso) return '—';
        const p = iso.split('-');
        return p.length === 3 ? `${p[2]}/${p[1]}/${p[0]}` : iso;
    }

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
        $('#tbl-body').html('<tr><td colspan="5" class="loading">Caricamento…</td></tr>');

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
                $('#tbl-body').html('<tr><td colspan="5" class="no-data">Errore di caricamento.</td></tr>');
            }
        });
    }

    /* ══════════════════════════════════════════════════════════════════════
       FILTRI CLIENT-SIDE
    ═════════════════════════════════════════════════════════════════════ */

    function applicaFiltri(righe) {
        const codice  = $('#search-codice').val().trim().toLowerCase();
        const tipoSIM = $('#search-tipo-sim').val();
        const numero  = $('#search-numero').val().trim().toLowerCase();
        const dataDa  = $('#search-data-da').val();
        const dataA   = $('#search-data-a').val();

        if (codice)  righe = righe.filter(r => (r.codice || '').toString().toLowerCase().includes(codice));
        if (tipoSIM) righe = righe.filter(r => r.tipoSIM === tipoSIM);
        if (numero)  righe = righe.filter(r => (r.numero || '').toString().toLowerCase().includes(numero));
        if (dataDa)  righe = righe.filter(r => r.dataAttivazione >= dataDa);
        if (dataA)   righe = righe.filter(r => r.dataAttivazione <= dataA);

        // Aggiorna lo span col numero di elementi filtrati
        $('#contatore-risultati').text(righe.length);

        if (righe.length === 0) {
            $('#tbl-body').html('<tr><td colspan="5" class="no-data">Nessuna SIM attiva trovata.</td></tr>');
            return;
        }

        const html = righe.map(r => `
            <tr>
                <td><code>${r.codice}</code></td>
                <td><strong>
                    <a href="contratto-telefonico.php?numero=${encodeURIComponent(r.numero)}"
                       style="color: #de5543; font-weight: bold; text-decoration: underline;"
                       title="Vai al contratto">
                       ${r.numero}
                    </a>
                </strong></td>
                <td>${badgeSIM(r.tipoSIM)}</td>
                <td>${fmtData(r.dataAttivazione)}</td>
                <td style="text-align:center; white-space:nowrap;">
                    <button class="btn btn-info btn-sm btn-dettaglio"
                            data-codice="${r.codice}"
                            title="Visualizza dettaglio">Dettaglio</button>
                    <button class="btn btn-info btn-sm btn-disattiva"
                            data-codice="${r.codice}"
                            data-tipo="${r.tipoSIM}"
                            data-numero="${r.numero}"
                            data-attivazione="${r.dataAttivazione}"
                            title="Disattiva questa SIM"
                            style="background-color: #e07b35; margin-left:6px;">Disattiva</button>
                </td>
            </tr>`).join('');

        $('#tbl-body').html(html);
    }

    caricaERicorda();

    /* ══════════════════════════════════════════════════════════════════════
       EVENTI FILTRO SIDEBAR
    ═════════════════════════════════════════════════════════════════════ */

    $('#btn-cerca').on('click', () => applicaFiltri(_tutteLeSIM));

    $('#search-codice, #search-tipo-sim, #search-numero, #search-data-da, #search-data-a')
        .on('keydown', function (e) {
            if (e.key === 'Enter') applicaFiltri(_tutteLeSIM);
        });

    $('#btn-reset').on('click', function () {
        $('#search-codice, #search-tipo-sim, #search-numero, #search-data-da, #search-data-a').val('');
        applicaFiltri(_tutteLeSIM);
    });

    /* ══════════════════════════════════════════════════════════════════════
       MODAL DETTAGLIO  –  apertura / chiusura
    ═════════════════════════════════════════════════════════════════════ */

    function apriDettaglio(codice) {
        $('#modal-title').text('Dettaglio SIM Attiva');
        $('#d-codice, #d-numero, #d-tipo-sim, #d-data').text('—');
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
                $('#d-numero').text(d.numero);
                $('#d-tipo-sim').html(badgeSIM(d.tipoSIM));
                $('#d-data').text(fmtData(d.dataAttivazione));
            },
            error: function () {
                showErr('Errore di rete nel caricamento del dettaglio.');
                chiudiModal();
            }
        });
    }

    /* ══════════════════════════════════════════════════════════════════════
       MODAL DISATTIVAZIONE SIM
    ═════════════════════════════════════════════════════════════════════ */

    function apriDisattivazione(codice, tipoSIM, numero, dataAttivazione) {
        // Popola il riepilogo informativo
        $('#dis-codice-display').text(codice);
        $('#dis-tipo-display').html(badgeSIM(tipoSIM));
        $('#dis-numero-display').text(numero);
        $('#dis-att-display').text(fmtData(dataAttivazione));

        // Reset form: data minima = data attivazione, default = oggi
        $('#dis-codice').val(codice);
        $('#dis-data-attivazione').val(dataAttivazione); // campo nascosto
        const oggi = new Date().toISOString().split('T')[0];
        $('#dis-data').val(oggi);
        $('#dis-data').attr('min', dataAttivazione);
        $('#dis-errors').hide().text('');

        $('#modal-disattiva-overlay').fadeIn(150);
        $('#dis-data').trigger('focus');
    }

    function chiudiDisattivazione() {
        $('#modal-disattiva-overlay').fadeOut(150);
    }

    // Apri modal disattivazione
    $('#tbl-body').on('click', '.btn-disattiva', function () {
        apriDisattivazione(
            $(this).data('codice'),
            $(this).data('tipo'),
            $(this).data('numero'),
            $(this).data('attivazione')
        );
    });

    // Chiusura
    $('#dis-close-btn, #dis-btn-annulla').on('click', chiudiDisattivazione);
    $('#modal-disattiva-overlay').on('click', function (e) {
        if ($(e.target).is('#modal-disattiva-overlay')) chiudiDisattivazione();
    });

    // Submit: invio dati disattivazione al server
    $('#dis-form').on('submit', function (e) {
        e.preventDefault();

        const codice             = $('#dis-codice').val();
        const dataDisattivazione = $('#dis-data').val();
        const dataAttivazione    = $('#dis-data-attivazione').val();

        if (!dataDisattivazione) {
            $('#dis-errors').text('Inserisci la data di disattivazione.').show();
            return;
        }
        if (dataDisattivazione < dataAttivazione) {
            $('#dis-errors').text('La data di disattivazione non può essere precedente alla data di attivazione.').show();
            return;
        }

        $('#dis-btn-conferma').prop('disabled', true).text('Disattivazione…');

        $.ajax({
            url: API,
            method: 'POST',
            data: {
                action: 'deactivate',
                codice: codice,
                dataDisattivazione: dataDisattivazione
            },
            dataType: 'json',
            success: function (r) {
                $('#dis-btn-conferma').prop('disabled', false).text('Conferma Disattivazione');
                if (!r.success) {
                    $('#dis-errors').text('Errore: ' + r.message).show();
                    return;
                }
                chiudiDisattivazione();
                // Rimuovi dalla cache locale e ridisegna senza ricaricare dal server
                _tutteLeSIM = _tutteLeSIM.filter(s => s.codice !== codice);
                applicaFiltri(_tutteLeSIM);
                showOk('SIM ' + codice + ' disattivata con successo!');
            },
            error: function () {
                $('#dis-btn-conferma').prop('disabled', false).text('Conferma Disattivazione');
                $('#dis-errors').text('Errore di rete. Riprova.').show();
            }
        });
    });

    /* ══════════════════════════════════════════════════════════════════════
       TASTO ESCAPE  –  chiude qualsiasi modal aperta
    ═════════════════════════════════════════════════════════════════════ */

    $(document).on('keydown', function (e) {
        if (e.key !== 'Escape') return;
        if ($('#modal-overlay').is(':visible'))           chiudiModal();
        if ($('#modal-disattiva-overlay').is(':visible')) chiudiDisattivazione();
    });

}); // fine $(function)
