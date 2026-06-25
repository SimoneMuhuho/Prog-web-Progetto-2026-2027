Progetto Programmazione Web A.A 2026/2027 Gruppo SauceCode

Progetto : Piattaforma gestione telefonate

SIM Salabim è un'applicazione web intuitiva che simula il gestionale di un operatore telefonico. Sviluppato a scopo didattico, permette di amministrare l'intero ecosistema di una compagnia telefonica: dai contratti dei clienti allo storico delle chiamate, fino al magazzino fisico delle SIM.

Cosa puoi fare nell'app? Dashboard: Una panoramica immediata con statistiche in tempo reale sui clienti e lo stato del magazzino.

Contratti: Trova rapidamente un cliente, scopri la sua SIM attuale e verifica quanto credito o minuti gli restano.

Telefonate: Registra nuove chiamate o consulta lo storico applicando filtri per data e numero.

sim Ciclo di vita SIM: Naviga tra il Magazzino (SIM Non Attive), i clienti attuali (SIM Attive) e l'archivio storico (SIM Disattivate).

La Logica (Come funziona davvero) Il vero cuore di SIM Salabim non è solo l'estetica, ma la sicurezza delle operazioni sui dati. Qualche esempio pratico:

Addebiti Intelligenti: I contratti si dividono in Ricarica (a soldi) e Consumo (a minuti). Se inserisci una chiamata per un contratto a Ricarica, l'app scala automaticamente l'importo dal credito residuo. Se il cliente non ha abbastanza soldi, la chiamata viene bloccata!

Storni Automatici (Rimborsi): Hai registrato una chiamata per sbaglio? Nessun problema. Se elimini una telefonata, il sistema riconosce il tipo di contratto e restituisce in automatico i minuti o i soldi scalati in precedenza.

Magazzino Coerente: Il percorso di una SIM è rigoroso. Prendi una SIM dal magazzino per attivarla a un cliente; quando quel cliente disdice, la SIM finisce nello storico e non può mai più tornare in magazzino. Ogni passaggio è tracciato con date e orari.

Come è stato costruito? L'applicazione offre un'esperienza utente fluida, senza mai ricaricare la pagina durante le ricerche o i salvataggi:

Frontend: Interfaccia dinamica realizzata in HTML, CSS e JavaScript (jQuery) per intercettare ogni click o filtro di ricerca.

Backend: Una serie di API scritte in PHP che comunicano in modo asincrono con l'interfaccia.

Database: MySQL interrogato tramite PDO, utilizzando Transazioni SQL per garantire che i soldi scalati e le telefonate registrate avvengano sempre nello stesso istante, senza errori a metà operazione.

Si tiene a precisare che il progetto è a scopo didattico. Realizzato secondo le specifiche del Progetto d'Esame PW. © 2026 Università degli Studi di Bergamo.

Si specifica che ai fini della parteciapazione gli utenti iltroll114 e SimoneMuhuho sono la stessa persona
