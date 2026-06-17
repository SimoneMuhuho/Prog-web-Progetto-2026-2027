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

        // Aggiorna lo span col numero di elementi filtrati
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
                    <button class="btn-modifica"
                            data-id="${r.id}"
                            data-durata="${r.durata}"
                            data-costo="${r.costo}"
                            title="Modifica durata e costo">Modifica</button>
                            
                    <button class="btn-elimina"
                            data-id="${r.id}"
                            data-label="${r.effettuataDa} – ${fmtData(r.data)} ${r.ora ?? ''}"
                            title="Elimina telefonata">Elimina</button>
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
       MODAL CREAZIONE  –  Aggiunta nuova telefonata con controllo contratto
    ═════════════════════════════════════════════════════════════════════ */

    // Apre il modal compilando data e ora corrente per facilitare l'inserimento
    $('#btn-apri-crea').on('click', function () {
        $('#crea-form')[0].reset();
        
        const oggi = new Date().toISOString().split('T')[0];
        const oraCorrente = new Date().toTimeString().split(' ')[0]; // Formato hh:mm:ss
        
        $('#crea-data').val(oggi);
        $('#crea-ora').val(oraCorrente);
        $('#crea-errors').hide().text('');
        $('#modal-crea-overlay').fadeIn(150);
        $('#crea-effettuataDa').trigger('focus');
    });

    function chiudiCrea() {
        $('#modal-crea-overlay').fadeOut(150);
    }

    // Chiusura modal creazione
    $('#crea-close-btn, #crea-btn-annulla').on('click', chiudiCrea);
    $('#modal-crea-overlay').on('click', function (e) {
        if ($(e.target).is('#modal-crea-overlay')) chiudiCrea();
    });

// Variabile per salvare temporaneamente il tipo contratto rilevato nella modale crea
    let _tipoContrattoRilevato = null;

    /** Rileva il tipo di contratto inserito digitando il numero SIM */
    $('#crea-effettuataDa').on('blur change input', function () {
        const numero = $(this).val().trim();
        
        // Se il numero è troppo corto (es. meno di 9 cifre), svuota lo stato e mostra il costo
        if (numero.length < 9) {
            _tipoContrattoRilevato = null;
            $('#crea-contratto-info').hide().text('');
            $('#wrapper-crea-costo').slideDown(150);
            $('#crea-costo').prop('required', true);
            return;
        }

        // Interroga l'API per verificare il tipo di contratto in tempo reale
        $.ajax({
            url: API,
            method: 'GET',
            data: { action: 'get_tipo_contratto', numero: numero },
            dataType: 'json',
            success: function (r) {
                if (r.success && r.tipo) {
                    _tipoContrattoRilevato = r.tipo;
                    
                    // Mostra un feedback all'utente
                    $('#crea-contratto-info').css('color', r.tipo === 'consumo' ? 'var(--orange)' : 'var(--green)')
                        .text('Contratto rilevato: ' + r.tipo.toUpperCase()).show();

                    if (r.tipo === 'consumo') {
                        // Nasconde il campo costo, azzera il valore e toglie il required
                        $('#wrapper-crea-costo').slideUp(150);
                        $('#crea-costo').val('').prop('required', false);
                    } else {
                        // Ripristina il campo costo se è ricarica
                        $('#wrapper-crea-costo').slideDown(150);
                        $('#crea-costo').prop('required', true);
                    }
                } else {
                    // Numero non trovato o errore
                    _tipoContrattoRilevato = null;
                    $('#crea-contratto-info').css('color', 'var(--red)').text('Numero SIM non censito nel sistema.').show();
                    $('#wrapper-crea-costo').slideDown(150);
                    $('#crea-costo').prop('required', true);
                }
            }
        });
    });

    // Modifica anche il reset all'apertura della modale per ripulire lo stato precedente
    $('#btn-apri-crea').on('click', function () {
        // ... tuo codice di reset esistente ...
        _tipoContrattoRilevato = null;
        $('#crea-contratto-info').hide().text('');
        $('#wrapper-crea-costo').show();
        $('#crea-costo').prop('required', true);
    });
    
    /** Salva la nuova telefonata via POST */
    $('#crea-form').on('submit', function (e) {
        e.preventDefault();

        const effettuataDa = $('#crea-effettuataDa').val().trim();
        const data         = $('#crea-data').val();
        const ora          = $('#crea-ora').val();
        const durata       = parseInt($('#crea-durata').val(), 10);
        
        let costoInviato = $('#crea-costo').val();

        // Validazione client-side rapida
        if (!effettuataDa) {
            $('#crea-errors').text('Inserire un numero SIM valido.').show();
            return;
        }
        if (isNaN(durata) || durata < 1) {
            $('#crea-errors').text('La durata deve essere maggiore di zero.').show();
            return;
        }

        // Se il contratto è a consumo il costo viene forzato a null, altrimenti si valida
        if (_tipoContrattoRilevato === 'consumo') {
            costoInviato = null; 
        } else {
            const costoNum = parseFloat(costoInviato);
            if (isNaN(costoNum) || costoNum < 0) {
                $('#crea-errors').text('Il costo deve essere maggiore o uguale a zero per i contratti ricarica.').show();
                return;
            }
            costoInviato = costoNum.toFixed(4);
        }

        $('#crea-btn-salva').prop('disabled', true).text('Salvataggio…');

        $.ajax({
            url: API,
            method: 'POST',
            data: { action: 'create', effettuataDa, data, ora, durata, costo: costoInviato },
            dataType: 'json',
            success: function (r) {
                $('#crea-btn-salva').prop('disabled', false).text('Salva');
                if (!r.success) {
                    $('#crea-errors').text(r.message).show();
                    return;
                }
                chiudiCrea();

                // Costruiamo l'oggetto includendo il costo (che sarà null o stringa formattata)
                const nuovaChiamata = {
                    id: r.id,
                    effettuataDa: effettuataDa,
                    data: data,
                    ora: ora,
                    durata: durata,
                    costo: costoInviato, // Mantiene il valore null o il float formattato
                    tipoContratto: r.tipoContratto
                };

                // Inseriamo in cima alla cache locale e ri-applichiamo i filtri
                _tutteLeTelefonate.unshift(nuovaChiamata);
                applicaFiltri(_tutteLeTelefonate);
                showOk('Nuova telefonata registrata con successo.');
            },
            error: function () {
                $('#crea-btn-salva').prop('disabled', false).text('Salva');
                $('#crea-errors').text('Errore di rete. Riprova.').show();
            }
        });
    });
    
    /* ══════════════════════════════════════════════════════════════════════
       MODAL MODIFICA  –  durata (secondi) e costo
    ═════════════════════════════════════════════════════════════════════ */

    /** Apre il modal di modifica pre-compilato con i valori attuali */
    function apriModifica(id, durata, costo, tipoContratto) {
        $('#mod-id').val(id);
        $('#mod-durata').val(durata);
        
        // Controllo speculare: se il contratto è a consumo, nascondi il campo costo
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

    // Apri modifica al click sul pulsante ✏️ (Aggiornato per passare anche il tipoContratto)
    $('#tbl-body').on('click', '.btn-modifica', function () {
        // Recuperiamo la riga cliccata per estrarre le info dalla cache locale
        const idSelezionato = $(this).data('id');
        const record = _tutteLeTelefonate.find(t => String(t.id) === String(idSelezionato));
        
        if (record) {
            apriModifica(record.id, record.durata, record.costo, record.tipoContratto);
        }
    });

    function chiudiModifica() {
        $('#modal-modifica-overlay').fadeOut(150);
    }

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
        
        // Troviamo il record in cache per capire se è a consumo o ricarica
        const record = _tutteLeTelefonate.find(t => String(t.id) === String(id));
        let costoInviato = $('#mod-costo').val();

        // Validazione client-side della durata
        if (isNaN(durata) || durata < 1) {
            $('#mod-errors').text('La durata deve essere un numero intero positivo (in secondi).').show();
            return;
        }

        // Se il contratto è a consumo il costo è forzato a null, altrimenti si valida
        if (record && record.tipoContratto === 'consumo') {
            costoInviato = null;
        } else {
            const costoNum = parseFloat(costoInviato);
            if (isNaN(costoNum) || costoNum < 0) {
                $('#mod-errors').text('Il costo deve essere un numero positivo per i contratti ricarica.').show();
                return;
            }
            costoInviato = costoNum.toFixed(4);
        }

        $('#mod-btn-salva').prop('disabled', true).text('Salvataggio…');

        $.ajax({
            url: API,
            method: 'POST',
            data: { action: 'update', id, durata, costo: costoInviato },
            dataType: 'json',
            success: function (r) {
                $('#mod-btn-salva').prop('disabled', false).text('Salva');
                if (!r.success) {
                    $('#mod-errors').text('Errore: ' + r.message).show();
                    return;
                }
                chiudiModifica();
                
                // Aggiorna la cache locale mantenendo la consistenza dell'interfaccia al volo
                if (record) { 
                    record.durata = durata; 
                    record.costo = costoInviato; // Sarà null o stringa formattata
                }
                
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
        if ($('#modal-aggiungi-overlay').is(':visible'))  chiudiAggiungi();
        if ($('#modal-modifica-overlay').is(':visible'))  chiudiModifica();
        if ($('#modal-elimina-overlay').is(':visible'))   chiudiConfermaElimina();
    });

}); // fine $(function)
