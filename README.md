# The Events Calendar OSM 
**Versione:** 2.0.0  
**Autore:** BuddyActivist  
**Richiede:** WordPress 5.0+, The Events Calendar  

Un plugin leggero e potente per WordPress che sostituisce nativamente Google Maps con **OpenStreetMap (tramite Leaflet.js)** all'interno del plugin *The Events Calendar*. Migliora la privacy degli utenti (100% GDPR compliant), elimina la necessità di chiavi API a pagamento e aggiunge funzionalità avanzate di mappatura.

## ✨ Caratteristiche Principali

* **Sostituzione Automatica:** Sostituisce l'iframe di Google Maps nelle pagine dei singoli Eventi e Luoghi (Venues) con una mappa OpenStreetMap interattiva.
* **Geocoding Automatico (Nominatim):** Calcola e salva automaticamente le coordinate GPS (Latitudine e Longitudine) ogni volta che crei o aggiorni un Luogo, partendo dall'indirizzo testuale.
* **Mappa Globale (Shortcode):** Tramite lo shortcode `[mappa_eventi_osm]` puoi mostrare tutti i tuoi eventi futuri in un'unica grande mappa interattiva.
* **Marker Personalizzati per Categoria:** Aggiunge un campo nelle "Categorie Evento" per caricare un'icona (Pin) personalizzata (es. un'icona per la musica, una per il teatro).
* **Clustering Avanzato:** Raggruppa automaticamente i pin troppo vicini tra loro con un indicatore numerico elegante (tramite *Leaflet.markercluster*), esplodendo il gruppo al click o allo zoom.
* **Ricerca in Tempo Reale:** La mappa globale include una barra di ricerca *live* per filtrare istantaneamente i pin in base al nome dell'evento.
* **Pannello di Amministrazione:** Pagina di impostazioni dedicata sotto il menu "Eventi" per configurare Latitudine, Longitudine, Zoom di partenza e altezza della mappa.
* **Translation Ready:** Predisposto per la traduzione in più lingue (include file `.pot`).

## 🚀 Installazione

1. Scarica o clona questo repository.
2. Assicurati che la cartella si chiami `the-events-calendar-osm`.
3. Carica la cartella all'interno della directory `/wp-content/plugins/` del tuo sito WordPress.
4. Vai nel menu **Plugin** del pannello di amministrazione di WordPress e attiva "The Events Calendar OSM".

## ⚙️ Configurazione e Utilizzo

### 1. Impostazioni di Base
Subito dopo l'attivazione, vai su **Eventi > Mappa OSM** nel tuo pannello WordPress per impostare:
* Coordinate di partenza (Centro della mappa)
* Livello di Zoom iniziale
* Altezza della mappa globale

### 2. Generare le Coordinate
Se hai già dei Luoghi (Venues) salvati prima di installare questo plugin, Nominatim ha bisogno di calcolarne le coordinate:
* Vai su **Eventi > Luoghi**.
* Apri ogni luogo e clicca su **Aggiorna**. Il plugin farà il resto in background.

### 3. Assegnare Icone alle Categorie
* Vai su **Eventi > Categorie Evento**.
* Crea una nuova categoria o modificane una esistente.
* Nel nuovo campo "URL Icona Mappa", incolla il link di un'immagine (preferibilmente 32x32px PNG) caricata precedentemente nella tua Libreria Media.

### 4. Mostrare la Mappa Globale
Incolla questo shortcode in qualsiasi Pagina, Articolo o Widget del tuo sito per far comparire la mappa generale con tutti gli eventi e la barra di ricerca:
```text
[mappa_eventi_osm]
```
## 📝 Changelog

2.0.0
Integrazione shortcode globale [mappa_eventi_osm].

Aggiunta ricerca testuale in tempo reale (Live Filter).

Aggiunta raggruppamento Marker (Clustering).

Aggiunta pagina di amministrazione (Settings API).

Supporto per file lingua .pot.

1.0.0
Rilascio iniziale.

Sostituzione Google Maps con Leaflet.js.

Integrazione Nominatim API per il geocoding dei Luoghi.

Custom fields per icone categoria.

## ⚖️ Licenza
Questo progetto è rilasciato sotto licenza GPLv2 o successiva (come WordPress). Leaflet.js e MarkerCluster appartengono ai rispettivi autori.

