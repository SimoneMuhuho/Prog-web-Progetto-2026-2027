/**
 * js/contratti.js
 * Logica jQuery per la pagina contratti.php
 * Gestisce: caricamento tabella, ricerca, modal dettaglio
 */

$(function () {

    const API = 'api_contratti.php';

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
       CARICAMENTO TABELLA CONTRATTI
    ═════════════════════════════════════════════════════════════════════ */

    function caricaContratti(filtro) {
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

                let righe = risposta.data;

                // Filtro client-side per numero
                if (filtro) {
                    const q = filtro.toLowerCase();
                    righe = righe.filter(r => r.numero.toLowerCase().includes(q));
                }

                if (righe.length === 0) {
                    $('#tbl-body').html('<tr><td colspan="8" class="no-data">Nessun contratto trovato.</td></tr>');
                    return;
                }

                const html = righe.map(r => {
                    const minuti  = r.tipo === 'consumo'  ? r.minutiResidui                               : '—';
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
            },
            error: function () {
                showErr('Errore di rete durante il caricamento.');
                $('#tbl-body').html('<tr><td colspan="8" class="no-data">Errore di caricamento.</td></tr>');
            }
        });
    }

    // Primo caricamento all'avvio della pagina
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
       MODAL DETTAGLIO  –  apertura / chiusura
    ═════════════════════════════════════════════════════════════════════ */

    function apriDettaglio(numero) {
        // Intestazione modal
        $('#modal-title').text('Dettaglio Contratto: ' + numero);

        // Reset sezioni
        $('#d-numero, #d-data, #d-tipo, #d-residuo').text('—');
        $('#body-telefonate').html('<tr><td colspan="4" class="loading">Caricamento…</td></tr>');
        $('#body-sim-attiva').html('<tr><td colspan="3" class="loading">Caricamento…</td></tr>');
        $('#body-sim-disattive').html('<tr><td colspan="4" class="loading">Caricamento…</td></tr>');

        $('#modal-overlay').fadeIn(150);

        // Carica tutti i dati in parallelo
        caricaDatiContratto(numero);
        caricaTelefonate(numero);
        caricaSimAttiva(numero);
        caricaSimDisattive(numero);
    }

    function chiudiModal() {
        $('#modal-overlay').fadeOut(150);
    }

    // Click sul bottone dettaglio (delegazione eventi su tbody dinamico)
    $('#tbl-body').on('click', '.btn-dettaglio', function () {
        apriDettaglio($(this).data('numero'));
    });

    // Chiusura modal
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

    /** Dati principali del contratto */
    function caricaDatiContratto(numero) {
        $.ajax({
            url: API,
            method: 'GET',
            data: { action: 'get', numero: numero },
            dataType: 'json',
            success: function (r) {
                if (!r.success) return;
                const d = r.data;
                $('#d-numero').text(d.numero);
                $('#d-data').text(fmtData(d.dataAttivazione));
                $('#d-tipo').html(badge(d.tipo));
                if (d.tipo === 'ricarica') {
                    $('#d-residuo').text('€ ' + parseFloat(d.creditoResiduo).toFixed(2));
                } else {
                    $('#d-residuo').text(d.minutiResidui + ' minuti');
                }
            }
        });
    }

    /** Telefonate associate al contratto */
    function caricaTelefonate(numero) {
        $.ajax({
            url: API,
            method: 'GET',
            data: { action: 'telefonate', numero: numero },
            dataType: 'json',
            success: function (r) {
                if (!r.success || r.data.length === 0) {
                    $('#body-telefonate').html('<tr><td colspan="4" class="no-data">Nessuna telefonata registrata.</td></tr>');
                    return;
                }
                const html = r.data.map(t => `
                    <tr>
                        <td>${fmtData(t.data)}</td>
                        <td>${t.ora}</td>
                        <td>${t.durata}</td>
                        <td>${parseFloat(t.costo).toFixed(2)} €</td>
                    </tr>`).join('');
                $('#body-telefonate').html(html);
            },
            error: function () {
                $('#body-telefonate').html('<tr><td colspan="4" class="no-data">Errore di caricamento.</td></tr>');
            }
        });
    }

    /** SIM attiva associata al contratto */
    function caricaSimAttiva(numero) {
        $.ajax({
            url: API,
            method: 'GET',
            data: { action: 'sim_attiva', numero: numero },
            dataType: 'json',
            success: function (r) {
                if (!r.success || r.data.length === 0) {
                    $('#body-sim-attiva').html('<tr><td colspan="3" class="no-data">Nessuna SIM attiva associata.</td></tr>');
                    return;
                }
                const html = r.data.map(s => `
                    <tr>
                        <td>${s.codice}</td>
                        <td>${s.tipoSIM}</td>
                        <td>${fmtData(s.dataAttivazione)}</td>
                    </tr>`).join('');
                $('#body-sim-attiva').html(html);
            },
            error: function () {
                $('#body-sim-attiva').html('<tr><td colspan="3" class="no-data">Errore di caricamento.</td></tr>');
            }
        });
    }

    /** SIM disattivate associate al contratto */
    function caricaSimDisattive(numero) {
        $.ajax({
            url: API,
            method: 'GET',
            data: { action: 'sim_disattive', numero: numero },
            dataType: 'json',
            success: function (r) {
                if (!r.success || r.data.length === 0) {
                    $('#body-sim-disattive').html('<tr><td colspan="4" class="no-data">Nessuna SIM disattivata.</td></tr>');
                    return;
                }
                const html = r.data.map(s => `
                    <tr>
                        <td>${s.codice}</td>
                        <td>${s.tipoSIM}</td>
                        <td>${fmtData(s.dataAttivazione)}</td>
                        <td>${fmtData(s.dataDisattivazione)}</td>
                    </tr>`).join('');
                $('#body-sim-disattive').html(html);
            },
            error: function () {
                $('#body-sim-disattive').html('<tr><td colspan="4" class="no-data">Errore di caricamento.</td></tr>');
            }
        });
    }

}); // fine $(function)
