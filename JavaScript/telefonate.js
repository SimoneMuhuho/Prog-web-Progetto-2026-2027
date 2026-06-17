$(function () {

    const API = 'PHP/telefonate/api_telefonate.php';

    /* ══════════════════════════════════════════════════════════════════════
       UTILITY DI FORMATTAZIONE
    ═════════════════════════════════════════════════════════════════════ */
    function fmtData(iso) {
        if (!iso) return '—';
        const p = iso.split('-');
        return p.length === 3 ? `${p[2]}/${p[1]}/${p[0]}` : iso;
    }

    function fmtDurata(secondi) {
        if (!secondi && secondi !== 0) return '—';
        const s  = parseInt(secondi, 10);
        const mm = Math.floor(s / 60);
        const ss = String(s % 60).padStart(2, '0');
        return `${mm}:${ss}`;
    }

    function fmtCosto(valore) {
        if (valore === null || valore === undefined || valore === '') return '—';
        return parseFloat(valore).toFixed(2) + ' €';
    }

    function badgeContratto(tipo) {
        if (!tipo) return '—';
        return `<span class="badge badge-${tipo}">${tipo}</span>`;
    }

    /* ══════════════════════════════════════════════════════════════════════
       CARICAMENTO TABELLA E FILTRI
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
                if (!r.success) return;
                _tutteLeTelefonate = r.data;
                applicaFiltri(_tutteLeTelefonate);
            },
            error: function () {
                $('#tbl-body').html('<tr><td colspan="7" class="no-data">Errore di caricamento.</td></tr>');
            }
        });
    }

    function applicaFiltri(righe) {
        const contratto = $('#search-contratto').val().trim().toLowerCase();
        const tipoContr = $('#search-tipo-contratto').val();
        const dataDa    = $('#search-data-da').val();
        const dataA     = $('#search-data-a').val();
        const costoMax  = $('#search-costo-max').val();

        if (contratto) righe = righe.filter(r => (r.effettuataDa || '').toString().toLowerCase().includes(contratto));
        if (tipoContr) righe = righe.filter(r => r.tipoContratto === tipoContr);
        if (dataDa)    righe = righe.filter(r => r.data >= dataDa);
        if (dataA)     righe = righe.filter(r => r.data <= dataA);
        if (costoMax)  righe = righe.filter(r => parseFloat(r.costo) <= parseFloat(costoMax));

        $('#contatore-risultati').text(righe.length);

        if (righe.length === 0) {
            $('#tbl-body').html('<tr><td colspan="7" class="no-data">Nessuna telefonata trovata.</td></tr>');
            return;
        }

        const html = righe.map(r => `
            <tr data-id="${r.id}">
                <td class="text-left"><strong>${r.effettuataDa}</strong></td>
                <td class="text-left">${badgeContratto(r.tipoContratto)}</td>
                <td class="text-right">${fmtData(r.data)}</td>
                <td class="text-right">${r.ora ?? '—'}</td>
                <td class="cell-durata text-right">${fmtDurata(r.durata)}</td>
                <td class="cell-costo text-right">${fmtCosto(r.costo)}</td>
                <td class="text-center" style="white-space:nowrap;">
                    <button type="button" class="btn-modifica" data-id="${r.id}">Modifica</button>
                    <button type="button" class="btn-elimina" data-id="${r.id}" data-label="${r.effettuataDa} – ${fmtData(r.data)}">Elimina</button>
                </td>
            </tr>`).join('');
        $('#tbl-body').html(html);
    }

    caricaERicorda();

    $('#btn-cerca').on('click', () => applicaFiltri(_tutteLeTelefonate));
    $('#btn-reset').on('click', function () {
        $('#search-contratto, #search-tipo-contratto, #search-data-da, #search-data-a, #search-costo-max').val('');
        applicaFiltri(_tutteLeTelefonate);
    });

   /* ══════════════════════════════════════════════════════════════════════
       MODAL CREAZIONE
    ═════════════════════════════════════════════════════════════════════ */
    let _tipoContrattoRilevato = null;

    $('#btn-apri-crea').on('click', function () {
        $('#crea-form')[0].reset();
        $('#crea-data').val(new Date().toISOString().split('T')[0]);
        $('#crea-ora').val(new Date().toTimeString().split(' ')[0]);
        $('#crea-errors').hide().text('');
        $('#crea-contratto-info').hide().text('');
        $('#wrapper-crea-costo').show();
        $('#crea-costo').prop('required', true);
        _tipoContrattoRilevato = null;
        $('#modal-crea-overlay').fadeIn(150);
        $('#crea-effettuataDa').trigger('focus');
    });

    function chiudiCrea() { $('#modal-crea-overlay').fadeOut(150); }
    $('#crea-close-btn, #crea-btn-annulla').on('click', chiudiCrea);

    // Controllo asincrono puramente visivo durante la digitazione
    $('#crea-effettuataDa').on('blur change input', function () {
        const numero = $(this).val().trim();
        if (numero.length < 3) {
            _tipoContrattoRilevato = null;
            $('#crea-contratto-info').hide();
            return;
        }
        $.ajax({
            url: API,
            method: 'GET',
            data: { action: 'get_tipo_contratto', numero: numero },
            dataType: 'json',
            success: function (r) {
                if (r.success && r.tipo) {
                    _tipoContrattoRilevato = r.tipo;
                    $('#crea-contratto-info').css('color', r.tipo === 'consumo' ? 'orange' : 'green')
                        .text('Contratto valido: ' + r.tipo.toUpperCase()).show();
                    if (r.tipo === 'consumo') {
                        $('#wrapper-crea-costo').slideUp(150);
                        $('#crea-costo').val('').prop('required', false);
                    } else {
                        $('#wrapper-crea-costo').slideDown(150);
                        $('#crea-costo').prop('required', true);
                    }
                } else {
                    _tipoContrattoRilevato = null;
                    $('#crea-contratto-info').css('color', 'red').text('Errore: Contratto o SIM inesistente.').show();
                }
            }
        });
    });
    
    /** SUBMIT CREAZIONE DIRETTA */
    $('#crea-form').on('submit', function (e) {
        e.preventDefault();
        $('#crea-errors').hide().text('');

        const effettuataDa = $('#crea-effettuataDa').val().trim();
        const data         = $('#crea-data').val();
        const ora          = $('#crea-ora').val();
        const stringaMinuti = $('#durata').val(); 
        const minuti        = parseInt(stringaMinuti ? stringaMinuti.trim() : '', 10);
        
        if (!effettuataDa) { $('#crea-errors').text('Inserire un numero di contratto valido.').show(); return; }
        if (!stringaMinuti || isNaN(minuti) || minuti <= 0) { $('#crea-errors').text('La durata deve essere maggiore di 0.').show(); return; }

        const durataSecondi = minuti * 60;
        let costoInviato = $('#crea-costo').val();

        if (_tipoContrattoRilevato === 'ricarica') {
            const costoNum = parseFloat(costoInviato);
            if (isNaN(costoNum) || costoNum < 0) {
                $('#crea-errors').text('Il costo è obbligatorio per i contratti ricarica.').show();
                return;
            }
            costoInviato = costoNum.toFixed(4);
        } else if (_tipoContrattoRilevato === 'consumo') {
            costoInviato = null;
        }

        $('#crea-btn-salva').prop('disabled', true).text('Salvataggio…');

        $.ajax({
            url: API,
            method: 'POST',
            data: { action: 'create', effettuataDa, data, ora, durata: durataSecondi, costo: costoInviato },
            dataType: 'json',
            success: function (r) {
                $('#crea-btn-salva').prop('disabled', false).text('Salva');
                
                // Se il PHP ha bloccato l'inserimento, mostriamo l'errore restituito dal server
                if (!r.success) { 
                    $('#crea-errors').text(r.message).show(); 
                    return; 
                }
                
                chiudiCrea();
                
                _tutteLeTelefonate.unshift({
                    id: r.id,
                    effettuataDa: effettuataDa,
                    data: data,
                    ora: ora,
                    durata: durataSecondi,
                    costo: costoInviato,
                    tipoContratto: r.tipoContratto
                });
                
                applicaFiltri(_tutteLeTelefonate);
            },
            error: function () {
                $('#crea-btn-salva').prop('disabled', false).text('Salva');
                $('#crea-errors').text('Errore di rete durante il salvataggio.').show();
            }
        });
    });
    
    /* ══════════════════════════════════════════════════════════════════════
       MODAL MODIFICA
    ═════════════════════════════════════════════════════════════════════ */
    function apriModifica(id, durataSecondi, costo, tipoContratto) {
        $('#mod-id').val(id);
        const minutiInteri = Math.floor(parseInt(durataSecondi, 10) / 60) || 1;
        $('#mod-durata').val(minutiInteri);
        
        if (tipoContratto === 'consumo') {
            $('#wrapper-mod-costo').hide();
            $('#mod-costo').val('').prop('required', false);
        } else {
            $('#wrapper-mod-costo').show();
            $('#mod-costo').val(parseFloat(costo).toFixed(2)).prop('required', true);
        }
        $('#mod-errors').hide().text('');
        $('#modal-modifica-overlay').fadeIn(150);
        $('#mod-durata').trigger('focus');
    }

    $('#tbl-body').on('click', '.btn-modifica', function () {
        const idSelezionato = $(this).data('id');
        const record = _tutteLeTelefonate.find(t => String(t.id) === String(idSelezionato));
        if (record) apriModifica(record.id, record.durata, record.costo, record.tipoContratto);
    });

    function chiudiModifica() { $('#modal-modifica-overlay').fadeOut(150); }
    $('#mod-close-btn, #mod-btn-annulla').on('click', chiudiModifica);

    $('#mod-form').on('submit', function (e) {
        e.preventDefault();
        $('#mod-errors').hide().text('');

        const id = $('#mod-id').val();
        const stringaMinuti = $('#mod-durata').val();
        const minuti = parseInt(stringaMinuti ? stringaMinuti.trim() : '', 10);
        const record = _tutteLeTelefonate.find(t => String(t.id) === String(id));
        let costoInviato = $('#mod-costo').val();

        if (!stringaMinuti || isNaN(minuti) || minuti <= 0) {
            $('#mod-errors').text('Inserire un numero di minuti valido.').show();
            return;
        }

        const durataSecondi = minuti * 60;

        if (record && record.tipoContratto === 'consumo') {
            costoInviato = null;
        } else {
            const costoNum = parseFloat(costoInviato);
            if (isNaN(costoNum) || costoNum < 0) {
                $('#mod-errors').text('Il costo deve essere un numero valido.').show();
                return;
            }
            costoInviato = costoNum.toFixed(4);
        }

        $('#mod-btn-salva').prop('disabled', true).text('Salvataggio…');

        $.ajax({
            url: API,
            method: 'POST',
            data: { action: 'update', id, durata: durataSecondi, costo: costoInviato },
            dataType: 'json',
            success: function (r) {
                $('#mod-btn-salva').prop('disabled', false).text('Salva');
                if (!r.success) { $('#mod-errors').text(r.message).show(); return; }
                chiudiModifica();
                if (record) { 
                    record.durata = durataSecondi; 
                    record.costo = costoInviato;
                }
                applicaFiltri(_tutteLeTelefonate);
            },
            error: function () {
                $('#mod-btn-salva').prop('disabled', false).text('Salva');
                $('#mod-errors').text('Errore di rete.').show();
            }
        });
    });

    /* ══════════════════════════════════════════════════════════════════════
       MODAL CONFERMA ELIMINAZIONE
    ═════════════════════════════════════════════════════════════════════ */
    let _idDaEliminare = null;

    $('#tbl-body').on('click', '.btn-elimina', function () {
        _idDaEliminare = $(this).data('id');
        $('#del-label').text($(this).data('label'));
        $('#modal-elimina-overlay').fadeIn(150);
    });

    function chiudiConfermaElimina() { $('#modal-elimina-overlay').fadeOut(150); _idDaEliminare = null; }
    $('#del-close-btn, #del-btn-annulla').on('click', chiudiConfermaElimina);

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
                chiudiConfermaElimina();
                if (!r.success) return;
                _tutteLeTelefonate = _tutteLeTelefonate.filter(t => String(t.id) !== String(id));
                applicaFiltri(_tutteLeTelefonate);
            },
            error: function () {
                $('#del-btn-conferma').prop('disabled', false).text('Sì, elimina');
                chiudiConfermaElimina();
            }
        });
    });

    $(document).on('keydown', function (e) {
        if (e.key !== 'Escape') return;
        chiudiCrea();
        chiudiModifica();
        chiudiConfermaElimina();
    });
});