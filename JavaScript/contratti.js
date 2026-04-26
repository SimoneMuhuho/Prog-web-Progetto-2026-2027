/**
 * js/contratti.js
 * Logica jQuery per la pagina contratti.php  –  Layout 1
 * Gestisce: caricamento tabella, filtri multipli, modal dettaglio
 */
$(function () {
    const API = '../PHP/contratti/api_contratti.php'

    /* ══════════════════════════════════════════════════════════════════════
       UTILITY
    ═════════════════════════════════════════════════════════════════════ */

    /** Formatta data ISO (yyyy-mm-dd) → dd/mm/yyyy */
    function fmtData(iso) {
        if (!iso) return '—';
        const p = iso.split('-');
        return p.length === 3 ? `${p[2]}/${p[1]}/${p[0]}` : iso;
    }

    /** Badge colorato per il tipo contratto */
    function badge(tipo) {
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
       CARICAMENTO E FILTRO TABELLA CONTRATTI
    ═════════════════════════════════════════════════════════════════════ */

    function caricaContratti() {
        $('#tbl-body').html('<tr><td colspan="8" class="loading">Caricamento…</td></tr>');

        $.ajax({
            url: API,
            method: 'GET',
            data: { action: 'list' },
            dataType: 'json',
            success: function (risposta) {
                if (!risposta.success) {
                    showErr('Errore nel caricamento: ' + risposta.message);
                    return;
                }
                applicaFiltri(risposta.data);
            },
            error: function () {
                showErr('Errore di rete durante il caricamento.');
                $('#tbl-body').html('<tr><td colspan="8" class="no-data">Errore di caricamento.</td></tr>');
            }
        });
    }

    /** Filtra client-side in base ai valori della sidebar e ridisegna la tabella */
    function applicaFiltri(righe) {
        const numero = $('#search-numero').val().trim().toLowerCase();
        const tipo   = $('#search-tipo').val();
        const dataDa = $('#search-data-da').val();
        const dataA  = $('#search-data-a').val();

        if (numero) righe = righe.filter(r => r.numero.toLowerCase().includes(numero));
        if (tipo)   righe = righe.filter(r => r.tipo === tipo);
        if (dataDa) righe = righe.filter(r => r.dataAttivazione >= dataDa);
        if (dataA)  righe = righe.filter(r => r.dataAttivazione <= dataA);

        if (righe.length === 0) {
            $('#tbl-body').html('<tr><td colspan="8" class="no-data">Nessun contratto trovato.</td></tr>');
            return;
        }

        const html = righe.map(r => {
            const minuti  = r.tipo === 'consumo'  ? r.minutiResidui : '—';
            const credito = r.tipo === 'ricarica' ? parseFloat(r.creditoResiduo).toFixed(2) + ' €' : '—';
            const sim     = r.simAttiva ? r.simAttiva : '<em>nessuna</em>';

            return `
                <tr>
                    <td><strong>${r.numero}</strong></td>
                    <td>${fmtData(r.dataAttivazione)}</td>
                    <td>${badge(r.tipo)}</td>
                    <td>${minuti}</td>
                    <td>${credito}</td>
                    <td>${r.numTelefonate}</td>
                    <td>${sim}</td>
                    <td style="text-align:center;">
                        <button class="btn btn-info btn-sm btn-dettaglio"
                                data-numero="${r.numero}"
                                title="Visualizza dettaglio">🔍 Apri</button>
                    </td>
                </tr>`;
        }).join('');

        $('#tbl-body').html(html);
    }

    // Cache dei dati per filtrare senza rifetching
    let _tuttiIContratti = [];

    function caricaERicorda() {
        $('#tbl-body').html('<tr><td colspan="8" class="loading">Caricamento…</td></tr>');
        $.ajax({
            url: API,
            method: 'GET',
            data: { action: 'list' },
            dataType: 'json',
            success: function (r) {
                if (!r.success) { showErr('Errore: ' + r.message); return; }
                _tuttiIContratti = r.data;
                applicaFiltri(_tuttiIContratti);
            },
            error: function () {
                showErr('Errore di rete.');
                $('#tbl-body').html('<tr><td colspan="8" class="no-data">Errore.</td></tr>');
            }
        });
    }

    // Primo caricamento
    caricaERicorda();

    /* ══════════════════════════════════════════════════════════════════════
       EVENTI FILTRO SIDEBAR
    ═════════════════════════════════════════════════════════════════════ */

    $('#btn-cerca').on('click', function () {
        applicaFiltri(_tuttiIContratti);
    });

    // Cerca anche premendo Invio su qualsiasi campo del filtro
    $('#search-numero, #search-tipo, #search-data-da, #search-data-a').on('keydown', function (e) {
        if (e.key === 'Enter') applicaFiltri(_tuttiIContratti);
    });

    $('#btn-reset').on('click', function () {
        $('#search-numero').val('');
        $('#search-tipo').val('');
        $('#search-data-da').val('');
        $('#search-data-a').val('');
        applicaFiltri(_tuttiIContratti);
    });

    /* ══════════════════════════════════════════════════════════════════════
       MODAL DETTAGLIO  –  apertura / chiusura
    ═════════════════════════════════════════════════════════════════════ */

    function apriDettaglio(numero) {
        $('#modal-title').text('Dettaglio Contratto: ' + numero);
        $('#d-numero, #d-data, #d-tipo, #d-residuo').text('—');
        $('#body-telefonate').html('<tr><td colspan="4" class="loading">Caricamento…</td></tr>');
        $('#body-sim-attiva').html('<tr><td colspan="3" class="loading">Caricamento…</td></tr>');
        $('#body-sim-disattive').html('<tr><td colspan="4" class="loading">Caricamento…</td></tr>');

        $('#modal-overlay').fadeIn(150);

        caricaDatiContratto(numero);
        caricaTelefonate(numero);
        caricaSimAttiva(numero);
        caricaSimDisattive(numero);
    }

    function chiudiModal() {
        $('#modal-overlay').fadeOut(150);
    }

    $('#tbl-body').on('click', '.btn-dettaglio', function () {
        apriDettaglio($(this).data('numero'));
    });

    $('#modal-close-btn, #btn-chiudi').on('click', chiudiModal);
    $('#modal-overlay').on('click', function (e) {
        if ($(e.target).is('#modal-overlay')) chiudiModal();
    });
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') chiudiModal();
    });

    /* ══════════════════════════════════════════════════════════════════════
       CHIAMATE API PER IL DETTAGLIO
    ═════════════════════════════════════════════════════════════════════ */

    function caricaDatiContratto(numero) {
        $.ajax({
            url: API, method: 'GET',
            data: { action: 'get', numero: numero },
            dataType: 'json',
            success: function (r) {
                if (!r.success) return;
                const d = r.data;
                $('#d-numero').text(d.numero);
                $('#d-data').text(fmtData(d.dataAttivazione));
                $('#d-tipo').html(badge(d.tipo));
                $('#d-residuo').text(
                    d.tipo === 'ricarica'
                        ? '€ ' + parseFloat(d.creditoResiduo).toFixed(2)
                        : d.minutiResidui + ' minuti'
                );
            }
        });
    }

    function caricaTelefonate(numero) {
        $.ajax({
            url: API, method: 'GET',
            data: { action: 'telefonate', numero: numero },
            dataType: 'json',
            success: function (r) {
                if (!r.success || r.data.length === 0) {
                    $('#body-telefonate').html('<tr><td colspan="4" class="no-data">Nessuna telefonata registrata.</td></tr>');
                    return;
                }
                $('#body-telefonate').html(r.data.map(t => `
                    <tr>
                        <td>${fmtData(t.data)}</td>
                        <td>${t.ora}</td>
                        <td>${t.durata}</td>
                        <td>${parseFloat(t.costo).toFixed(2)} €</td>
                    </tr>`).join(''));
            },
            error: function () {
                $('#body-telefonate').html('<tr><td colspan="4" class="no-data">Errore di caricamento.</td></tr>');
            }
        });
    }

    function caricaSimAttiva(numero) {
        $.ajax({
            url: API, method: 'GET',
            data: { action: 'sim_attiva', numero: numero },
            dataType: 'json',
            success: function (r) {
                if (!r.success || r.data.length === 0) {
                    $('#body-sim-attiva').html('<tr><td colspan="3" class="no-data">Nessuna SIM attiva associata.</td></tr>');
                    return;
                }
                $('#body-sim-attiva').html(r.data.map(s => `
                    <tr>
                        <td>${s.codice}</td>
                        <td>${s.tipoSIM}</td>
                        <td>${fmtData(s.dataAttivazione)}</td>
                    </tr>`).join(''));
            },
            error: function () {
                $('#body-sim-attiva').html('<tr><td colspan="3" class="no-data">Errore di caricamento.</td></tr>');
            }
        });
    }

    function caricaSimDisattive(numero) {
        $.ajax({
            url: API, method: 'GET',
            data: { action: 'sim_disattive', numero: numero },
            dataType: 'json',
            success: function (r) {
                if (!r.success || r.data.length === 0) {
                    $('#body-sim-disattive').html('<tr><td colspan="4" class="no-data">Nessuna SIM disattivata.</td></tr>');
                    return;
                }
                $('#body-sim-disattive').html(r.data.map(s => `
                    <tr>
                        <td>${s.codice}</td>
                        <td>${s.tipoSIM}</td>
                        <td>${fmtData(s.dataAttivazione)}</td>
                        <td>${fmtData(s.dataDisattivazione)}</td>
                    </tr>`).join(''));
            },
            error: function () {
                $('#body-sim-disattive').html('<tr><td colspan="4" class="no-data">Errore di caricamento.</td></tr>');
            }
        });
    }

}); // fine $(function)