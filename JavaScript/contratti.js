/**
 * JavaScript/contratti.js
 * Logica jQuery per la pagina contratti.php  –  Layout 1
 * Gestisce: caricamento tabella, filtri multipli, modal dettaglio,
 *           modal attivazione SIM da contratto
 */
$(function () {

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('numero')) {
        $('#search-numero').val(urlParams.get('numero'));
    }

    const API = 'PHP/contratti/api_contratti.php';

    /* ══════════════════════════════════════════════════════════════════════
       UTILITY
    ═════════════════════════════════════════════════════════════════════ */

    function fmtData(iso) {
        if (!iso) return '—';
        const p = iso.split('-');
        return p.length === 3 ? `${p[2]}/${p[1]}/${p[0]}` : iso;
    }

    function badge(tipo) {
        return `<span class="badge badge-${tipo}">${tipo}</span>`;
    }

    function badgeSIM(tipo) {
        const cls = { 'nano': 'badge-nano', 'micro': 'badge-micro', 'standard': 'badge-standard', 'eSIM': 'badge-esim' };
        return `<span class="badge ${cls[tipo] ?? ''}">${tipo ?? '—'}</span>`;
    }

    function showErr(testo) {
        $('#msg-box').removeClass().addClass('msg-box msg-error').text(testo).slideDown(150);
        clearTimeout(window._msgTimer);
        window._msgTimer = setTimeout(() => $('#msg-box').slideUp(200), 4000);
    }

    function showOk(testo) {
        $('#msg-box').removeClass().addClass('msg-box msg-success').text(testo).slideDown(150);
        clearTimeout(window._msgTimer);
        window._msgTimer = setTimeout(() => $('#msg-box').slideUp(200), 3000);
    }

    /* ══════════════════════════════════════════════════════════════════════
       CARICAMENTO E CACHE
    ═════════════════════════════════════════════════════════════════════ */

    let _tuttiIContratti = [];

    function caricaERicorda() {
        $('#tbl-body').html('<tr><td colspan="9" class="loading">Caricamento…</td></tr>');
        $.ajax({
            url: API, method: 'GET',
            data: { action: 'list' },
            dataType: 'json',
            success: function (r) {
                if (!r.success) { showErr('Errore: ' + r.message); return; }
                _tuttiIContratti = r.data;
                applicaFiltri(_tuttiIContratti);
            },
            error: function () {
                showErr('Errore di rete.');
                $('#tbl-body').html('<tr><td colspan="9" class="no-data">Errore.</td></tr>');
            }
        });
    }

    /* ══════════════════════════════════════════════════════════════════════
       FILTRI CLIENT-SIDE
    ═════════════════════════════════════════════════════════════════════ */

    function applicaFiltri(righe) {
        const numero = $('#search-numero').val().trim().toLowerCase();
        const tipo   = $('#search-tipo').val();
        const dataDa = $('#search-data-da').val();
        const dataA  = $('#search-data-a').val();

        if (numero) righe = righe.filter(r => (r.numero || '').toString().toLowerCase().includes(numero));
        if (tipo)   righe = righe.filter(r => r.tipo === tipo);
        if (dataDa) righe = righe.filter(r => r.dataAttivazione >= dataDa);
        if (dataA)  righe = righe.filter(r => r.dataAttivazione <= dataA);

        $('#contatore-risultati').text(righe.length);

        if (righe.length === 0) {
            $('#tbl-body').html('<tr><td colspan="9" class="no-data">Nessun contratto trovato.</td></tr>');
            return;
        }

        const html = righe.map(r => {
            const minuti  = r.tipo === 'consumo'  ? r.minutiResidui : '—';
            const credito = r.tipo === 'ricarica' ? parseFloat(r.creditoResiduo).toFixed(2) + ' €' : '—';

            // Colonna SIM attiva
            let sim = '';
            if (r.simAttiva) {
                sim = `<a href="sim_attive.php?codice=${encodeURIComponent(r.simAttiva)}"
                          style="color:var(--red);font-weight:bold;text-decoration:none;"
                          title="Vai alla SIM attiva">${r.simAttiva}</a>`;
            } else if (parseInt(r.numDisattive) > 0) {
                sim = `<a href="sim_disattivate.php?contratto=${encodeURIComponent(r.numero)}"
                          style="color:var(--text-gray);font-style:italic;text-decoration:underline;"
                          title="Vedi storico disattivazioni">Nessuna</a>`;
            } else {
                sim = `<span style="color:#bbb;font-style:italic;" title="Nessuno storico SIM">Nessuna</span>`;
            }

            // Pulsante "Attiva SIM": abilitato solo se NON c'è già una SIM attiva
            const btnAttiva = r.simAttiva
                ? `<button class="btn btn-info btn-sm btn-attiva-sim"
                           data-numero="${r.numero}"
                           title="Questo contratto ha già una SIM attiva"
                           disabled
                           style="opacity:0.35;cursor:not-allowed;background-color:var(--green);">
                       Attiva SIM
                   </button>`
                : `<button class="btn btn-info btn-sm btn-attiva-sim"
                           data-numero="${r.numero}"
                           title="Attiva una SIM per questo contratto"
                           style="background-color:var(--green);">
                       Attiva SIM
                   </button>`;

            return `
                <tr>
                    <td><strong>${r.numero}</strong></td>
                    <td>${fmtData(r.dataAttivazione)}</td>
                    <td>${badge(r.tipo)}</td>
                    <td>${minuti}</td>
                    <td>${credito}</td>
                    <td>${r.numTelefonate}</td>
                    <td>${sim}</td>
                    <td style="text-align:center;">${btnAttiva}</td>
                    <td style="text-align:center;">
                        <button class="btn btn-info btn-sm btn-dettaglio"
                                data-numero="${r.numero}"
                                title="Visualizza dettaglio">Apri</button>
                    </td>
                </tr>`;
        }).join('');

        $('#tbl-body').html(html);
    }

    caricaERicorda();

    /* ══════════════════════════════════════════════════════════════════════
       EVENTI FILTRO SIDEBAR
    ═════════════════════════════════════════════════════════════════════ */

    $('#btn-cerca').on('click', () => applicaFiltri(_tuttiIContratti));

    $('#search-numero, #search-tipo, #search-data-da, #search-data-a').on('keydown', function (e) {
        if (e.key === 'Enter') applicaFiltri(_tuttiIContratti);
    });

    $('#btn-reset').on('click', function () {
        $('#search-numero, #search-tipo, #search-data-da, #search-data-a').val('');
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

    function chiudiModal() { $('#modal-overlay').fadeOut(150); }

    $('#tbl-body').on('click', '.btn-dettaglio', function () {
        apriDettaglio($(this).data('numero'));
    });

    $('#modal-close-btn, #btn-chiudi').on('click', chiudiModal);
    $('#modal-overlay').on('click', function (e) {
        if ($(e.target).is('#modal-overlay')) chiudiModal();
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
            error: () => $('#body-telefonate').html('<tr><td colspan="4" class="no-data">Errore di caricamento.</td></tr>')
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
                        <td>${badgeSIM(s.tipoSIM)}</td>
                        <td>${fmtData(s.dataAttivazione)}</td>
                    </tr>`).join(''));
            },
            error: () => $('#body-sim-attiva').html('<tr><td colspan="3" class="no-data">Errore di caricamento.</td></tr>')
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
                        <td>${badgeSIM(s.tipoSIM)}</td>
                        <td>${fmtData(s.dataAttivazione)}</td>
                        <td>${fmtData(s.dataDisattivazione)}</td>
                    </tr>`).join(''));
            },
            error: () => $('#body-sim-disattive').html('<tr><td colspan="4" class="no-data">Errore di caricamento.</td></tr>')
        });
    }

    /* ══════════════════════════════════════════════════════════════════════
       MODAL ATTIVAZIONE SIM  –  da pagina contratti
    ═════════════════════════════════════════════════════════════════════ */

    function apriAttivaSIM(numeroContratto) {
        $('#att-numero-contratto').val(numeroContratto);
        $('#att-contratto-display').text(numeroContratto);
        $('#att-tipo-sim').val('');
        $('#att-data').val(new Date().toISOString().split('T')[0]);
        $('#att-errors').hide().text('');
        $('#modal-attiva-overlay').fadeIn(150);
        $('#att-tipo-sim').trigger('focus');
    }

    function chiudiAttivaSIM() { $('#modal-attiva-overlay').fadeOut(150); }

    // Apri modal al click su "Attiva SIM" nella tabella
    $('#tbl-body').on('click', '.btn-attiva-sim', function () {
        apriAttivaSIM($(this).data('numero'));
    });

    $('#att-close-btn, #att-btn-annulla').on('click', chiudiAttivaSIM);
    $('#modal-attiva-overlay').on('click', function (e) {
        if ($(e.target).is('#modal-attiva-overlay')) chiudiAttivaSIM();
    });

    // Submit attivazione
    $('#att-form').on('submit', function (e) {
        e.preventDefault();

        const numeroContratto = $('#att-numero-contratto').val();
        const tipoSIM         = $('#att-tipo-sim').val();
        const dataAttivazione = $('#att-data').val();

        if (!tipoSIM) {
            $('#att-errors').text('Seleziona il tipo di SIM desiderato.').show();
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
                action: 'activate_sim',
                numeroContratto: numeroContratto,
                tipoSIM: tipoSIM,
                dataAttivazione: dataAttivazione
            },
            dataType: 'json',
            success: function (r) {
                $('#att-btn-conferma').prop('disabled', false).text('Conferma Attivazione');
                if (!r.success) {
                    $('#att-errors').text(r.message).show();
                    return;
                }
                chiudiAttivaSIM();

                // Aggiorna la cache locale: imposta simAttiva sul contratto appena attivato
                const contratto = _tuttiIContratti.find(c => c.numero === numeroContratto);
                if (contratto) contratto.simAttiva = r.codice;

                applicaFiltri(_tuttiIContratti);
                showOk('SIM ' + r.tipoSIM + ' (' + r.codice + ') attivata sul contratto ' + numeroContratto + '.');
            },
            error: function () {
                $('#att-btn-conferma').prop('disabled', false).text('Conferma Attivazione');
                $('#att-errors').text('Errore di rete. Riprova.').show();
            }
        });
    });

    /* ══════════════════════════════════════════════════════════════════════
       TASTO ESCAPE
    ═════════════════════════════════════════════════════════════════════ */

    $(document).on('keydown', function (e) {
        if (e.key !== 'Escape') return;
        if ($('#modal-overlay').is(':visible'))        chiudiModal();
        if ($('#modal-attiva-overlay').is(':visible')) chiudiAttivaSIM();
    });

}); // fine $(function)
