# HagemanCatering

WordPress plugin voor het plan- en maatwerksysteem van Hageman Catering.

## Structuur

- `hageman-catering.php`: plugin bootstrap die de functionaliteit laadt en gedeelde constanten definieert.
- `includes/assets.php`: registreert en laadt de gedeelde CSS- en JavaScript-bestanden.
- `includes/agenda/agenda.php`: bevat de agenda shortcodes en AJAX-handlers en koppelt de weergavetemplates.
- `includes/agenda/templates/`: opdeling van de agendaweergave in `header.php`, `content.php` en `look-and-feel.php` voor eenvoudige aanpassingen.
- `includes/maatwerk/form.php`: bevat het maatwerk bestelformulier, gerelateerde shortcodes en AJAX-handlers.
- `assets/css/agenda-look-and-feel.css`: basisopmaak, lettertypen en kleuren voor de agenda.
- `assets/css/agenda-header.css`: styling voor de navigatiebalk en knoppen boven de agenda.
- `assets/css/agenda-content.css`: opmaak van de agenda-inhoud, kaarten en modals.
- `assets/js/agenda-header.js`: logica voor de navigatieknoppen, weekselectie en hulpfuncties.
- `assets/js/agenda-content.js`: logica voor modals, filters en agenda-inhoud.
- `assets/js/agenda.js`: initialiseert de afzonderlijke modules zodra de pagina geladen is.

Plaats deze map in `wp-content/plugins/` en activeer de plugin via het WordPress dashboard.
