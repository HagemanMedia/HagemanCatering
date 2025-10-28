<?php

if (! defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/templates/content.php';
require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/look-and-feel.php';



/**
 * Genereert de wekelijkse agenda van WooCommerce bestellingen en maatwerk bestellingen.
 *
 * @param array $atts Shortcode attributen. Accepteert 'start_date' (YYYY/MM/DD).
 * @return string De HTML output van de agenda.
 */
function show_wc_orders_week_agenda($atts) {

    // --- Toegangscontrole Logica ---
    // Haal de ID van de huidige post/pagina op waar de shortcode wordt gebruikt
    $current_post_id = get_the_ID();
    // Haal de zichtbaarheidsinstelling voor de agenda op uit de post meta.
    // De verwachte waarden zijn 'interne_medewerkers' of 'alle_werknemers'.
    $agenda_visibility_setting = get_post_meta($current_post_id, '_agenda_visibility_setting', true);

    // Standaard zichtbaarheid als deze niet is ingesteld op de pagina
    if (empty($agenda_visibility_setting)) {
        $agenda_visibility_setting = 'alle_werknemers'; // Standaard instellen op "Alle Werknemers"
    }
	
	// --- ENKEL "In-optie" lijst via ?status=In-optie ---------------------------
if (isset($_GET['status'])) {
    $raw_status = sanitize_text_field($_GET['status']);
    $norm = strtolower(trim($raw_status));
    $norm = str_replace([' ', '_'], '-', $norm);

    // Alleen reageren op "in-optie"
    if ($norm === 'in-optie' || $norm === 'in optie') {

        // Haal alle maatwerk_bestelling met status "in-optie", sorteer op datum/tijd
        $args_in_optie = [
            'post_type'      => 'maatwerk_bestelling',
            'posts_per_page' => -1,
            'post_status'    => ['publish','nieuw','in-optie','in_behandeling','bevestigd','afgerond','geannuleerd','geaccepteerd','afgewezen'],
            'meta_query'     => [
                [
                    'key'     => 'order_status',
                    'value'   => 'in-optie',
                    'compare' => '=',
                ],
            ],
            'orderby'  => 'meta_value',
            'meta_key' => 'datum',
            'order'    => 'ASC',
        ];
        $posts_in_optie = get_posts($args_in_optie);

        // Optioneel: binnen dezelfde datum ook nog op start_tijd sorteren
        usort($posts_in_optie, function($a, $b){
            $da = get_post_meta($a->ID, 'datum', true);
            $db = get_post_meta($b->ID, 'datum', true);
            $ta = get_post_meta($a->ID, 'start_tijd', true);
            $tb = get_post_meta($b->ID, 'start_tijd', true);
            $da_ts = strtotime(str_replace('/', '-', $da));
            $db_ts = strtotime(str_replace('/', '-', $db));
            if ($da_ts === $db_ts) {
                return strcmp($ta, $tb);
            }
            return $da_ts <=> $db_ts;
        });


        if (!empty($posts_in_optie)) {
            foreach ($posts_in_optie as $p) {
                $id            = $p->ID;
                $order_nummer  = get_post_meta($id, 'order_nummer', true);
                $titel         = get_the_title($id);
                $start_tijd    = get_post_meta($id, 'start_tijd', true);
                $eind_tijd     = get_post_meta($id, 'eind_tijd', true);
                $datum         = get_post_meta($id, 'datum', true);
                $bedrijfsnaam  = get_post_meta($id, 'bedrijfsnaam', true);
                $referentie    = get_post_meta($id, 'referentie', true);
                $aantal_pers   = get_post_meta($id, 'aantal_personen', true);
                $opm           = get_post_meta($id, '_maatwerk_bestelling_opmerkingen', true);
                $has_note      = !empty($opm);

                // Tijd + datum tonen zoals in dagweergave
                $tijd_str = trim(($start_tijd ? $start_tijd : '') . ($eind_tijd ? '–' . $eind_tijd : ''));
                $title_text = trim(($order_nummer ? '#' . $order_nummer . ' ' : '') . $titel);

                // Kaart
                $out .= '<div class="wc-weekagenda-item-google wc-weekagenda-item-maatwerk"'
                     .  ' data-order-type="maatwerk"'
                     .  ' data-post-status="in-optie"'
                     .  ' data-has-note="' . ($has_note ? 'true' : 'false') . '"'
                     .  ' data-order-id="' . esc_attr($id) . '">';

                // Badge (oranje of jouw eigen klasse)
                $out .= '  <span class="wc-order-badge status-maatwerk-orange">In-optie</span>';

                // Titel klikbaar (voor je bestaande modal/open gedrag)
                $out .= '  <div>';
                $out .= '      <a href="#" class="wc-weekagenda-title"'
                     .  '         data-order-type="maatwerk"'
                     .  '         data-order-id="' . esc_attr($id) . '">'
                     .  esc_html($title_text) . '</a>';
                $out .= '  </div>';

                // Datum + tijd
                if (!empty($datum)) {
                    $out .= '  <div class="wc-info-text" style="margin-top:2px;">'
                         .  esc_html(date_i18n('j F Y', strtotime(str_replace('/', '-', $datum))))
                         .  '</div>';
                }
                if ($tijd_str) {
                    $out .= '  <span class="wc-time-display wc-weekagenda-time">'
                         .  esc_html($tijd_str) . '</span>';
                }

                // Bedrijf + meta (zoals normaal)
                if ($bedrijfsnaam) {
                    $out .= '  <span class="wc-company-name-display">'
                         .  esc_html($bedrijfsnaam) . '</span>';
                }
                $bits = [];
                if ($referentie)  $bits[] = 'Ref: ' . $referentie;
                if ($aantal_pers) $bits[] = $aantal_pers . ' pers.';
                if (!empty($bits)) {
                    $out .= '  <div class="wc-info-text" style="margin-top:4px;">'
                         .  esc_html(implode(' • ', $bits)) . '</div>';
                }

                $out .= '</div>'; // kaart
            }
        } else {
            $out .= '<div class="wc-weekagenda-leeg-google" style="width:100%;">Geen bestellingen met status In-optie.</div>';
        }

        $out .= '  </div>'; // .wc-weekagenda-google
        $out .= '</div>';   // .wc-weekagenda-wrapper

        // Alleen deze weergave tonen
        return $out;
    }
}
// --- EINDE enkel "In-optie" lijst ------------------------------------------


    $current_user = wp_get_current_user();
    $user_roles = (array) $current_user->roles;

    // Verbeterde styling voor meldingen
    $access_denied_style = 'style="padding: 20px; text-align: center; color: #fff; background-color: #EF5350; border-radius: 12px; box-shadow: 0 4px 8px rgba(0,0,0,0.2); font-size: 1.1em; border: 2px solid #D32F2F;"';
    $access_denied_icon = '<span style="margin-right: 10px; font-size: 1.5em; vertical-align: middle;">&#x26A0;</span>'; // Waarschuwingsicoon

    // 1. Controleer of de gebruiker de rol 'customer' heeft. Klanten mogen deze pagina niet zien.
    if (in_array('customer', $user_roles)) {
        return '<div ' . $access_denied_style . '>' . $access_denied_icon . 'U heeft geen toegang tot deze pagina.</div>';
    }

    // 2. Controleer de zichtbaarheid op basis van de ingestelde waarde en gebruikersrollen
    if ($agenda_visibility_setting === 'interne_medewerkers') {
        // Alleen beheerders mogen de pagina zien als de instelling 'interne_medewerkers' is
        if (!current_user_can('manage_options')) { // 'manage_options' is een capabiliteit die typisch hoort bij beheerders
            return '<div ' . $access_denied_style . '>' . $access_denied_icon . 'Toegang geweigerd. Alleen interne medewerkers hebben toegang.</div>';
        }
    } elseif ($agenda_visibility_setting === 'alle_werknemers') {
        // Alle ingelogde gebruikers (die geen klant zijn, wat hierboven al is gefilterd) mogen de pagina zien
        if (!is_user_logged_in()) {
            return '<div ' . $access_denied_style . '>' . $access_denied_icon . 'U moet ingelogd zijn om deze pagina te bekijken.</div>';
        }
    }
    // Einde toegangscontrolelogica
   	
	// Haal startdatum uit filter_week (indien aanwezig), anders standaard naar maandag deze week
	$start_date_from_url = isset($_GET['filter_week']) ? sanitize_text_field($_GET['filter_week']) : '';
	$start_date_final = !empty($start_date_from_url) ? $start_date_from_url : date('Y/m/d', strtotime('monday this week'));

	$atts = shortcode_atts(
	    array(
	        'start_date' => date('Y/m/d', strtotime('monday this week')),
	    ),
	    $atts,
	    'wc_orders_week_agenda'
	);

	// Als ?filter_week=YYYY-MM-DD in de URL staat, overschrijf de start_date
	if (isset($_GET['filter_week'])) {
	    $filter_week = sanitize_text_field($_GET['filter_week']);
	    $d = DateTime::createFromFormat('Y-m-d', $filter_week);
	    if ($d) {
	        // Gebruik precies die datum als basis
	        $atts['start_date'] = $d->format('Y/m/d');
	    }
	}

	// **TOEGEVOEGD**: dag‐filter vanuit URL (?filter_day=YYYY-MM-DD)
	$day_filter = isset($_GET['filter_day']) 
	    ? sanitize_text_field($_GET['filter_day']) 
	    : '';
	
	
    $current_week_start_timestamp = strtotime($atts['start_date']);
    $week_start = strtotime('monday this week', $current_week_start_timestamp);
    $week_end = strtotime('sunday this week 23:59:59', $current_week_start_timestamp);

    $agenda = array();

    // Standaard statussen voor initiële weergave
    $wc_initial_statuses = array_keys(wc_get_order_statuses());

    // Alle statussen voor maatwerk, inclusief 'geaccepteerd' en 'afgewezen'
    $maatwerk_initial_statuses = array('nieuw', 'in-optie', 'in_behandeling', 'bevestigd', 'afgerond', 'publish', 'geannuleerd', 'geaccepteerd', 'afgewezen');

    // --- 1. Query WooCommerce bestellingen ---
    $args_wc = array(
        'post_type'      => 'shop_order',
        'posts_per_page' => -1,
        'post_status'    => $wc_initial_statuses, // Gebruik alle statussen
        'meta_query'     => array(
            array(
                'key'     => 'pi_system_delivery_date', // Correcte meta key voor leveringsdatum
                'value'   => array(date('Y/m/d', $week_start), date('Y/m/d', $week_end)),
                'compare' => 'BETWEEN',
                'type'    => 'DATE'
            )
        )
    );

    $wc_orders = get_posts($args_wc);

    foreach ($wc_orders as $order_post) {
        $order_id = $order_post->ID;
        $order = wc_get_order($order_id);

        if (!$order) {
            continue;
        }

        $delivery_date = get_post_meta($order_id, 'pi_system_delivery_date', true);
        $delivery_time = get_post_meta($order_id, 'pi_delivery_time', true);
        $eindtijd      = get_post_meta($order_id, 'order_eindtijd', true);
        $locatie       = get_post_meta($order_id, 'order_location', true);
        $personen      = get_post_meta($order_id, 'order_personen', true);
        $order_reference = get_post_meta($order_id, 'order_reference', true);
        $billing_company = $order->get_billing_company();
        $customer_note = $order->get_customer_note(); // Haal klantnotitie op voor indicator

        if (!$delivery_date) continue;
        if (!isset($agenda[$delivery_date])) $agenda[$delivery_date] = array();

		$agenda[$delivery_date][] = array(
		    'type'                      => 'woocommerce',
		    'order_id'                  => $order_id,
		    'sequential_order_number'   => $order->get_order_number(),
		    'tijd'                      => $delivery_time ? $delivery_time : '',
		    'eindtijd'                  => $eindtijd ? $eindtijd : '',
		    'locatie'                   => $locatie,
		    'personen'                  => $personen,
		    'order_reference'           => $order_reference,
		    'post_status'               => $order_post->post_status,
		    'billing_company'           => $billing_company,
		    'has_note'                  => !empty($customer_note),
		    'customer_note'            => $customer_note, // toegevoegd zodat je de tekst kunt tonen
		);
    }
    wp_reset_postdata(); // Reset postdata na WooCommerce query

    // --- 2. Query Maatwerk bestellingen ---
    $args_maatwerk = array(
        'post_type'      => 'maatwerk_bestelling', // Custom Post Type van Maatwerk
        'posts_per_page' => -1,
        'post_status'    => $maatwerk_initial_statuses, // Gebruik alle statussen
        'meta_query'     => array(
            array(
                'key'     => 'datum', // De meta key voor de datum in maatwerk
                'value'   => array(date('Y-m-d', $week_start), date('Y/m/d', $week_end)),
                'compare' => 'BETWEEN',
                'type'    => 'DATE' // Houd type als DATE voor juiste vergelijking
            )
        )
    );

    $maatwerk_orders = get_posts($args_maatwerk);

    foreach ($maatwerk_orders as $m_order_post) {
        $m_order_id = $m_order_post->ID;

        // Haal de maatwerk meta-data op zoals gespecificeerd
        $order_nummer            = get_post_meta($m_order_id, 'order_nummer', true);
        $maatwerk_voornaam       = get_post_meta($m_order_id, 'maatwerk_voornaam', true);
        $maatwerk_achternaam     = get_post_meta($m_order_id, 'maatwerk_achternaam', true);
        $maatwerk_email          = get_post_meta($m_order_id, 'maatwerk_email', true);
        $maatwerk_telefoonnummer = get_post_meta($m_order_id, 'maatwerk_telefoonnummer', true);
        $bedrijfsnaam            = get_post_meta($m_order_id, 'bedrijfsnaam', true);
        $straat_huisnummer       = get_post_meta($m_order_id, 'straat_huisnummer', true);
        $postcode                = get_post_meta($m_order_id, 'postcode', true);
        $plaats                  = get_post_meta($m_order_id, 'plaats', true);
        $referentie              = get_post_meta($m_order_id, 'referentie', true);
        $datum                   = get_post_meta($m_order_id, 'datum', true); // Dit is de leveringsdatum
        $start_tijd              = get_post_meta($m_order_id, 'start_tijd', true);
        $eind_tijd               = get_post_meta($m_order_id, 'eind_tijd', true);
        $aantal_medewerkers      = get_post_meta($m_order_id, 'aantal_medewerkers', true);
        $aantal_personen         = get_post_meta($m_order_id, 'aantal_personen', true);
        $opmerkingen             = get_post_meta($m_order_id, '_maatwerk_bestelling_opmerkingen', true); // Aangepast naar de correcte meta key
        $order_status_maatwerk   = get_post_meta($m_order_id, 'order_status', true); // Haal de daadwerkelijke maatwerk order status op
        $optie_geldig_tot        = get_post_meta($m_order_id, 'optie_geldig_tot', true); // Haal de optie geldig tot datum op
		$important_opmerking 	 = get_post_meta($m_order_id, 'important_opmerking', true);

		// Mappen naar het agenda-formaat
        $delivery_date_maatwerk = $datum; // Gebruik de 'datum' meta als delivery_date

        // Robuuste datum parsing voor de agenda array key, voor het geval de opgeslagen 'datum' niet in Y-MM-DD is
        $parsed_date = DateTime::createFromFormat('Y-m-d', $datum); // Probeer Y-MM-DD
        if (!$parsed_date) {
            $parsed_date = DateTime::createFromFormat('d-m-Y', $datum); // Probeer DD-MM-YYYY
        }
        if (!$parsed_date) {
            $parsed_date = DateTime::createFromFormat('m/d/Y', $datum); // Probeer MM/DD/YYYY (Amerikaans)
        }
        if ($parsed_date) {
            $delivery_date_maatwerk = $parsed_date->format('Y/m/d'); // Zorg voor Y/MM/DD voor de agenda key
        } else {
            continue; // Overslaan als datum niet betrouwbaar geparsed kan worden
        }


        $delivery_time_maatwerk = $start_tijd;
        $eindtijd_maatwerk      = $eind_tijd;
        $locatie_maatwerk       = (!empty($straat_huisnummer) ? $straat_huisnummer . ', ' : '') . $plaats; // Combineer straat en plaats
        $personen_maatwerk      = !empty($aantal_personen) ? $aantal_personen : (!empty($aantal_medewerkers) ? $aantal_medewerkers : ''); // Gebruik personen, anders medewerkers
        $order_reference_maatwerk = $referentie;
        $billing_company_maatwerk = $bedrijfsnaam;
        // Gebruik de daadwerkelijke maatwerk order status indien beschikbaar, anders fallback
        $display_post_status_maatwerk = !empty($order_status_maatwerk) ? $order_status_maatwerk : 'mw-completed';

		if (!$delivery_date_maatwerk) continue;
		if (!isset($agenda[$delivery_date_maatwerk])) $agenda[$delivery_date_maatwerk] = array();

		// NIEUW: haal employee_details op en zorg dat het een array is
		$employee_details = get_post_meta($m_order_id, '_employee_details', true);
		if (!is_array($employee_details)) {
		    $employee_details = array();
		}

		$agenda[$delivery_date_maatwerk][] = array(
		    'type'                      => 'maatwerk',
		    'order_id'                  => $m_order_id,
		    'sequential_order_number'   => !empty($order_nummer) ? $order_nummer : 'MW-' . $m_order_id,
		    'tijd' 						=> $delivery_time_maatwerk ? $delivery_time_maatwerk : '',
		    'eindtijd'                  => $eindtijd_maatwerk ? $eindtijd_maatwerk : '',
		    'locatie'                   => $locatie_maatwerk,
		    'personen'                  => $personen_maatwerk,
		    'order_reference'           => $order_reference_maatwerk,
		    'post_status'               => $display_post_status_maatwerk,
		    'billing_company'           => $billing_company_maatwerk,
		    'maatwerk_voornaam'         => $maatwerk_voornaam,
		    'maatwerk_achternaam'       => $maatwerk_achternaam,
		    'maatwerk_email'            => $maatwerk_email,
		    'maatwerk_telefoonnummer'   => $maatwerk_telefoonnummer,
		    'postcode'                  => $postcode,
		    'opmerkingen'               => $opmerkingen,
		    'important_opmerking'       => $important_opmerking,
		    'aantal_medewerkers'        => $aantal_medewerkers,
		    'aantal_personen_raw'       => $aantal_personen,
		    'optie_geldig_tot'          => $optie_geldig_tot,
		    'has_note'                  => !empty($opmerkingen),
		    'employee_details'          => $employee_details, // <-- toegevoegd
		);

    }
    wp_reset_postdata(); // Reset postdata na Maatwerk query

    // Start HTML output voor de weekagenda container
    
	// --- 3. Query Agenda Events (CPT agenda_event) ---
	$args_events = [
	    'post_type'      => 'agenda_event',
	    'posts_per_page' => -1,
	    'post_status'    => 'publish',
	    'meta_query'     => [
	        [
	            'key'     => 'event_date',
	            'value'   => [
	                date('Y-m-d', $week_start),
	                date('Y-m-d', $week_end)
	            ],
	            'compare' => 'BETWEEN',
	            'type'    => 'DATE',
	        ],
	    ],
	];
	$events = get_posts($args_events);
	foreach ($events as $evt) {
	    $ev_date  = get_post_meta($evt->ID, 'event_date', true);
	    $ev_time  = get_post_meta($evt->ID, 'event_time', true);
	    $ev_color = get_post_meta($evt->ID, 'event_color', true);
	    $title    = $evt->post_title;
	    if (!$ev_date) continue;
	    // Standaard formaat Y-m-d => Y/m/d
	    $key = DateTime::createFromFormat('Y-m-d', $ev_date)->format('Y/m/d');
	    if (!isset($agenda[$key])) $agenda[$key] = [];
	    $agenda[$key][] = [
	        'type'     => 'agenda_event',
	        'event_id' => $evt->ID,
	        'title'    => $title,
	        'tijd'     => $ev_time,
	        'color'    => $ev_color,
	    ];
	}

    // 3. **VERJAARDAGEN TOEVOEGEN**
    $users = get_users([ 'meta_key' => 'birthday', 'meta_compare' => 'EXISTS' ]);
    foreach ($users as $u) {
        $b = get_user_meta($u->ID, 'birthday', true);
        $d = DateTime::createFromFormat('Y-m-d', $b);
        if (!$d) continue;
        $md = $d->format('m-d');
        for ($i = 0; $i < 7; $i++) {
            $day_ts = strtotime("+{$i} days", $week_start);
            $key    = date('Y/m/d', $day_ts);
            if (date('m-d', $day_ts) === $md) {
                $agenda[$key][] = [
                    'type'      => 'birthday',
                    'user_id'   => $u->ID,
                    'user_name' => get_the_author_meta('display_name', $u->ID),
                ];
            }
        }
    }

	
    $header_html = hageman_catering_render_agenda_header($user_roles, $week_start);
    $content_html = hageman_catering_render_agenda_content($agenda, $week_start, $day_filter);

    return hageman_catering_render_agenda_layout(
        $agenda_visibility_setting,
        $header_html,
        $content_html
    );
}

/**
 * Genereert de agenda-inhoud (dagen en items) inclusief de styling.
 * Deze functie wordt aangeroepen door de shortcode en de AJAX-handler.
 *
 * @param array $agenda De georganiseerde bestelgegevens.
 * @param int $week_start_timestamp De timestamp van het begin van de week.
 * @return string De HTML output van de agenda-inhoud en styling.
 */


/**
 * AJAX handler om de agenda-inhoud dynamisch te laden.
 */

function wc_orders_week_agenda_ajax_handler() {
    $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : date('Y/m/d', strtotime('monday this week'));
    $agenda_visibility_setting = isset($_POST['agenda_visibility_setting']) ? sanitize_text_field($_POST['agenda_visibility_setting']) : 'alle_werknemers'; // Haal zichtbaarheidsinstelling op van AJAX

    $current_week_start_timestamp = strtotime($start_date);
    $week_start = strtotime('monday this week', $current_week_start_timestamp);
    $week_end = strtotime('sunday this week 23:59:59', $current_week_start_timestamp);

    $agenda = array();

    // --- 1. Query WooCommerce bestellingen ---
    $wc_post_statuses = array_keys(wc_get_order_statuses());

    $args_wc = array(
        'post_type'      => 'shop_order',
        'posts_per_page' => -1,
        'post_status'    => $wc_post_statuses,
        'meta_query'     => array(
            array(
                'key'     => 'pi_system_delivery_date',
                'value'   => array(date('Y/m/d', $week_start), date('Y/m/d', $week_end)),
                'compare' => 'BETWEEN',
                'type'    => 'DATE'
            )
        )
    );

    $wc_orders = get_posts($args_wc);
    foreach ($wc_orders as $order_post) {
        $order_id = $order_post->ID;
        $order = wc_get_order($order_id);
        if (!$order) continue;
        $delivery_date = get_post_meta($order_id, 'pi_system_delivery_date', true);
        $delivery_time = get_post_meta($order_id, 'pi_delivery_time', true);
        $eindtijd      = get_post_meta($order_id, 'order_eindtijd', true);
        $locatie       = get_post_meta($order_id, 'order_location', true);
        $personen      = get_post_meta($order_id, 'order_personen', true);
        $order_reference = get_post_meta($order_id, 'order_reference', true);
        $billing_company = $order->get_billing_company();
        $customer_note = $order->get_customer_note(); // Haal klantnotitie op voor indicator
        if (!$delivery_date) continue;
        if (!isset($agenda[$delivery_date])) $agenda[$delivery_date] = array();
		$agenda[$delivery_date][] = array(
		    'type'                    => 'woocommerce',
		    'order_id'                => $order_id,
		    'sequential_order_number' => $order->get_order_number(),
		    'tijd'                    => $delivery_time ? $delivery_time : '🕒',
		    'eindtijd'                => $eindtijd ? $eindtijd : '',
		    'locatie'                 => $locatie,
		    'personen'                => $personen,
		    'order_reference'         => $order_reference,
		    'post_status'             => $order_post->post_status,
		    'billing_company'         => $billing_company,
		    'has_note'                => !empty($customer_note),
		    'customer_note'           => $customer_note, // toegevoegd zodat je de tekst kunt tonen
		);
    }
    wp_reset_postdata(); // Reset postdata na WooCommerce query in AJAX

    // --- 2. Query Maatwerk bestellingen ---
    // Alle statussen voor maatwerk, inclusief 'geaccepteerd' en 'afgewezen'
    $maatwerk_post_statuses = array('nieuw', 'in-optie', 'in_behandeling', 'bevestigd', 'afgerond', 'publish', 'geannuleerd', 'geaccepteerd', 'afgewezen');

    $args_maatwerk = array(
        'post_type'      => 'maatwerk_bestelling',
        'posts_per_page' => -1,
        'post_status'    => $maatwerk_post_statuses,
        'meta_query'     => array(
            'relation' => 'AND',
            array(
                'key'     => 'datum',
                'value'   => array(date('Y-m-d', $week_start), date('Y-m-d', $week_end)),
                'compare' => 'BETWEEN',
                'type'    => 'DATE'
            )
        )
    );

    $maatwerk_orders = get_posts($args_maatwerk);

    foreach ($maatwerk_orders as $m_order_post) {
        $m_order_id = $m_order_post->ID;
        $order_nummer            = get_post_meta($m_order_id, 'order_nummer', true);
        $maatwerk_voornaam       = get_post_meta($m_order_id, 'maatwerk_voornaam', true);
        $maatwerk_achternaam     = get_post_meta($m_order_id, 'maatwerk_achternaam', true);
        $maatwerk_email          = get_post_meta($m_order_id, 'maatwerk_email', true);
        $maatwerk_telefoonnummer = get_post_meta($m_order_id, 'maatwerk_telefoonnummer', true);
        $bedrijfsnaam            = get_post_meta($m_order_id, 'bedrijfsnaam', true);
        $straat_huisnummer       = get_post_meta($m_order_id, 'straat_huisnummer', true);
        $postcode                = get_post_meta($m_order_id, 'postcode', true);
        $plaats                  = get_post_meta($m_order_id, 'plaats', true);
        $referentie              = get_post_meta($m_order_id, 'referentie', true);
        $datum                   = get_post_meta($m_order_id, 'datum', true);
        $start_tijd              = get_post_meta($m_order_id, 'start_tijd', true);
        $eind_tijd               = get_post_meta($m_order_id, 'eind_tijd', true);
        $aantal_medewerkers      = get_post_meta($m_order_id, 'aantal_medewerkers', true);
        $aantal_personen         = get_post_meta($m_order_id, 'aantal_personen', true);
		$important_opmerking = get_post_meta($m_order_id, 'important_opmerking', true);
        $opmerkingen             = get_post_meta($m_order_id, '_maatwerk_bestelling_opmerkingen', true); // Aangepast naar de correcte meta key
        $order_status_maatwerk   = get_post_meta($m_order_id, 'order_status', true); // Haal de daadwerkelijke maatwerk order status op
        $optie_geldig_tot        = get_post_meta($m_order_id, 'optie_geldig_tot', true); // Haal de optie geldig tot datum op

        $delivery_date_maatwerk = $datum;
        $delivery_time_maatwerk = $start_tijd;
        $eindtijd_maatwerk      = $eind_tijd;
        $locatie_maatwerk       = (!empty($straat_huisnummer) ? $straat_huisnummer . ', ' : '') . $plaats;
        $personen_maatwerk      = !empty($aantal_personen) ? $aantal_personen : (!empty($aantal_medewerkers) ? $aantal_medewerkers : '');
        $order_reference_maatwerk = $referentie;
        $billing_company_maatwerk = $bedrijfsnaam;
        $display_post_status_maatwerk = !empty($order_status_maatwerk) ? $order_status_maatwerk : 'mw-completed';

        // Robuuste datum parsing voor de agenda array key
        $parsed_date = DateTime::createFromFormat('Y-m-d', $datum);
        if (!$parsed_date) {
            $parsed_date = DateTime::createFromFormat('d-m-Y', $datum);
        }
        if (!$parsed_date) {
            $parsed_date = DateTime::createFromFormat('m/d/Y', $datum);
        }
        if ($parsed_date) {
            $delivery_date_maatwerk = $parsed_date->format('Y/m/d');
        } else {
            continue;
        }

		if (!$delivery_date_maatwerk) continue;
		if (!isset($agenda[$delivery_date_maatwerk])) $agenda[$delivery_date_maatwerk] = array();
		$agenda[$delivery_date_maatwerk][] = array(
		    'type'                      => 'maatwerk',
		    'order_id'                  => $m_order_id,
		    'sequential_order_number'   => !empty($order_nummer) ? $order_nummer : 'MW-' . $m_order_id,
		    'tijd'                      => $delivery_time_maatwerk ? $delivery_time_maatwerk : '🕒',
		    'eindtijd'                  => $eindtijd_maatwerk ? $eindtijd_maatwerk : '',
		    'locatie'                   => $locatie_maatwerk,
		    'personen'                  => $personen_maatwerk,
		    'order_reference'           => $order_reference_maatwerk,
		    'post_status'               => $display_post_status_maatwerk,
		    'billing_company'           => $billing_company_maatwerk,
		    'maatwerk_voornaam'         => $maatwerk_voornaam,
		    'maatwerk_achternaam'       => $maatwerk_achternaam,
		    'maatwerk_email'            => $maatwerk_email,
		    'maatwerk_telefoonnummer'   => $maatwerk_telefoonnummer,
		    'postcode'                  => $postcode,
		    'opmerkingen'               => $opmerkingen,
		    'important_opmerking'       => $important_opmerking,
		    'aantal_medewerkers'        => $aantal_medewerkers,
		    'aantal_personen_raw'       => $aantal_personen,
		    'optie_geldig_tot'          => $optie_geldig_tot,
		    'has_note'                  => !empty($opmerkingen)
		);

    }
    wp_reset_postdata(); // Reset postdata na Maatwerk query in AJAX


    echo hageman_catering_render_agenda_content($agenda, $week_start);
    wp_die();
}

/**
 * AJAX handler om gedetailleerde bestelinformatie op te halen voor de modal.
 */

/* === BEGIN ADD: helper voor logboek weergave (zonder titel, als veld) === */
function build_logboek_html($post_id) {
    // Container als veld: licht achtergrondvlak, afgeronde hoeken, italic, kleine tekst
    $html = '<div class="wc-modal-detail-section" style="margin-top:20px; font-style:italic; color: #bfbfbf; font-size:0.85em; background: rgba(255,255,255,0.03); padding:12px 14px; border-radius:6px; border:1px solid rgba(255,255,255,0.1);">';

    // Oorspronkelijke maker: probeer eerst _created_by, anders oudste revisie, dan als laatste redmiddel post_author
    $created_by_id = get_post_meta($post_id, '_created_by', true);
    if (empty($created_by_id)) {
        $revisions_for_creator = wp_get_post_revisions($post_id);
        if (!empty($revisions_for_creator)) {
            $oldest_rev = end($revisions_for_creator); // oudste revisie
            if ($oldest_rev && $oldest_rev->post_author) {
                $created_by_id = $oldest_rev->post_author;
            }
        }
    }
    if (empty($created_by_id)) {
        $created_by_id = get_post_field('post_author', $post_id);
    }
    $created_by_name = $created_by_id ? esc_html(get_the_author_meta('display_name', $created_by_id)) : 'Onbekend';
    $created_date = get_post_field('post_date', $post_id);
    $html .= '<p style="margin:4px 0;"><strong style="font-weight:600; font-style:normal; color:#d0d0d0;">Aangemaakt door:</strong> ' . $created_by_name . ' op ' . date_i18n('j F Y H:i', strtotime($created_date)) . '</p>';

    // Laatst gewijzigd door: eerst nieuwste revisie, anders _last_edited_by
    $last_editor_name = 'Onbekend';
    $last_modified_date = get_post_field('post_modified', $post_id);
    $revisions = wp_get_post_revisions($post_id);
    if (!empty($revisions)) {
        $latest_rev = reset($revisions); // nieuwste revisie
        if ($latest_rev->post_author) {
            $last_editor_name = esc_html(get_the_author_meta('display_name', $latest_rev->post_author));
            $last_modified_date = $latest_rev->post_modified;
        }
    }
    if ($last_editor_name === 'Onbekend') {
        $last_edited_by_id = get_post_meta($post_id, '_last_edited_by', true);
        if ($last_edited_by_id) {
            $last_editor_name = esc_html(get_the_author_meta('display_name', $last_edited_by_id));
        }
    }
    $html .= '<p style="margin:4px 0;"><strong style="font-weight:600; font-style:normal; color:#d0d0d0;">Laatst gewijzigd door:</strong> ' . $last_editor_name . ' op ' . date_i18n('j F Y H:i', strtotime($last_modified_date)) . '</p>';

    // Optioneel: laatste 5 revisies overzicht (ingeklapt)
    if (!empty($revisions)) {
        $html .= '<details style="margin-top:8px;"><summary style="cursor:pointer;font-weight:600; font-style:normal; color:#c0c0c0; outline:none;">Laatste revisies</summary><ul style="margin:6px 0 0;padding-left:16px; list-style:disc;">';
        $count = 0;
        foreach ($revisions as $rev) {
            if ($count++ >= 5) break;
            $rev_author = $rev->post_author ? get_the_author_meta('display_name', $rev->post_author) : 'Onbekend';
            $rev_date = date_i18n('j F Y H:i', strtotime($rev->post_modified));
            $html .= '<li>' . esc_html($rev_date) . ' door ' . esc_html($rev_author) . '</li>';
        }
        $html .= '</ul></details>';
    }

    $html .= '</div>';
    return $html;
}
/* === END ADD === */


function wc_get_order_details_ajax_handler() {

    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $order_type = isset($_POST['order_type']) ? sanitize_text_field($_POST['order_type']) : ''; // Haal het type op
    $agenda_visibility_setting = isset($_POST['agenda_visibility_setting']) ? sanitize_text_field($_POST['agenda_visibility_setting']) : 'alle_werknemers'; // Haal zichtbaarheidsinstelling op van AJAX

    if ($order_id <= 0) {
        wp_send_json_error('Ongeldig order ID.');
    }

    $output = '';

    if ($order_type === 'woocommerce') {
        $order = wc_get_order($order_id);

        if (!$order) {
            wp_send_json_error('WooCommerce bestelling niet gevonden.');
        }

        // Top Info: Referentie (BQXXX)
        $output .= '<div class="wc-modal-top-info">';
        $order_reference = esc_html(get_post_meta($order_id, 'order_reference', true));
        $order_number = esc_html($order->get_order_number()); // Haal het ordernummer op

        if (!empty($order_reference)) {
            $output .= '' . $order_reference . ' (' . $order_number . ')<br>';
        } else {
            $output .= '(' . $order_number . ')<br>';
        }
        $output .= '</div>';

        // Klantgegevens sectie
        $output .= '<div class="wc-modal-detail-section">';
        $output .= '<p>' . esc_html($order->get_billing_first_name()) . ' ' . esc_html($order->get_billing_last_name()) . '</p>';
        if (!empty($order->get_billing_company())) {
            $output .= '<p>' . esc_html($order->get_billing_company()) . '</p>';
        }
        $output .= '<p><a href="mailto:' . esc_attr($order->get_billing_email()) . '">' . esc_html($order->get_billing_email()) . '</a></p>';
        $output .= '<p><a href="tel:' . esc_attr($order->get_billing_phone()) . '">' . esc_html($order->get_billing_phone()) . '</a></p>';
        $output .= '</div>';

        // Leveringsgegevens sectie (met emojis)
        $output .= '<div class="wc-modal-detail-section">';
        $output .= '<h4>Leveringsgegevens</h4>';

        // Tijd (boven locatie, met emoji)
        $delivery_time = get_post_meta($order_id, 'pi_delivery_time', true);
        $eindtijd      = get_post_meta($order_id, 'order_eindtijd', true);

		
		$time_display = '';
		if (!empty($delivery_time)) {
		    $time_display .= esc_html($delivery_time);
		}
		if (!empty($eindtijd)) {
		    if (!empty($time_display)) {
		        $time_display .= ' - ';
		    }
		    $time_display .= esc_html($eindtijd);
		}

		if (!empty($time_display)) {
		    $output .= '<p>🕒 ' . $time_display . '</p>';
		}

        $output .= '<p>📍 ' . esc_html(get_post_meta($order_id, 'order_location', true)) . '</p>';
        $output .= '<p>👥 ' . esc_html(get_post_meta($order_id, 'order_personen', true)) . ' Personen</p>';
        $output .= '</div>';

        // Notities sectie
        $customer_note = $order->get_customer_note();
        if (!empty($customer_note)) {
            $output .= '<div class="wc-modal-detail-section">';
            $output .= '<h4>Notitie</h4>';
            $output .= '<p class="no-title">' . nl2br(esc_html($customer_note)) . '</p>';
            $output .= '</div>';
        }

        // Bestelde producten sectie (met nieuwe styling)
        $output .= '<div class="wc-modal-detail-section ordered-products">';
        $output .= '<h4>Bestelde producten</h4>';
        $output .= '<ul>';
        foreach ($order->get_items() as $item_id => $item) {
            $product_name = $item->get_name();
            $quantity = $item->get_quantity();
            $output .= '<li><div class="product-name-qty"><span>' . esc_html($product_name) . '</span> <span>x ' . esc_html($quantity) . '</span></div>';

            // Haal en toon product meta data
            $item_meta_data = $item->get_meta_data();
            if ($item_meta_data) {
                $output .= '<ul>';
                foreach ($item_meta_data as $meta) {
                    if (strpos($meta->key, '_') !== 0) { // Sluit verborgen meta keys uit
                        $output .= '<li><strong>' . esc_html(ucfirst(str_replace('_', ' ', $meta->key))) . ':</strong> ' . esc_html($meta->value) . '</li>';
                    }
                }
                $output .= '</ul>';
            }
            $output .= '</li>'; // Belangrijke fix: sluit de <li> tag
        }
        $output .= '</ul>';
        $output .= '</div>';

    } elseif ($order_type === 'maatwerk') {
        // Haal maatwerk order post op
        $m_order_post = get_post($order_id);

        if (!$m_order_post || $m_order_post->post_type !== 'maatwerk_bestelling') {
            wp_send_json_error('Maatwerk bestelling niet gevonden.');
        }

        // Haal alle maatwerk meta-data op
        $order_nummer            = get_post_meta($order_id, 'order_nummer', true);
        $maatwerk_voornaam       = get_post_meta($order_id, 'maatwerk_voornaam', true);
        $maatwerk_achternaam     = get_post_meta($order_id, 'maatwerk_achternaam', true);
        $maatwerk_email          = get_post_meta($order_id, 'maatwerk_email', true);
        $maatwerk_telefoonnummer = get_post_meta($order_id, 'maatwerk_telefoonnummer', true);
        $bedrijfsnaam            = get_post_meta($order_id, 'bedrijfsnaam', true);
        $straat_huisnummer       = get_post_meta($order_id, 'straat_huisnummer', true);
        $postcode                = get_post_meta($order_id, 'postcode', true);
        $plaats                  = get_post_meta($order_id, 'plaats', true);
        $referentie              = get_post_meta($order_id, 'referentie', true);
        $datum                   = get_post_meta($order_id, 'datum', true);
        $start_tijd              = get_post_meta($order_id, 'start_tijd', true);
        $eind_tijd               = get_post_meta($order_id, 'eind_tijd', true);
        $aantal_medewerkers      = get_post_meta($order_id, 'aantal_medewerkers', true);
        $aantal_personen         = get_post_meta($order_id, 'aantal_personen', true);
        // Gebruik de correcte meta key voor opmerkingen
        $opmerkingen             = get_post_meta($order_id, '_maatwerk_bestelling_opmerkingen', true); // Aangepast naar de correcte meta key
        $pdf_uploads             = get_post_meta($order_id, '_pdf_attachments', true); // Haal PDF uploads op
        $order_status_maatwerk   = get_post_meta($order_id, 'order_status', true); // Haal de daadwerkelijke maatwerk order status op
        $optie_geldig_tot        = get_post_meta($order_id, 'optie_geldig_tot', true); // Haal de optie geldig tot datum op

        // Top Info: Referentie (Maatwerk)
        $output .= '<div class="wc-modal-top-info">';
        $maatwerk_display_order_number = !empty($order_nummer) ? $order_nummer : 'MW-' . $order_id;
        if (!empty($referentie)) {
            $output .= ' ' . esc_html($referentie) . ' (' . esc_html($maatwerk_display_order_number) . ')<br>';
        } else {
            $output .= "Maatwerk Bestelling: " . esc_html($maatwerk_display_order_number) . "<br>";
        }

        // Toon "In optie tot" ook in de modal
        if ($order_status_maatwerk === 'in-optie' && !empty($optie_geldig_tot)) {
            $option_date_parsed = null;
            if (DateTime::createFromFormat('Y-m-d', $optie_geldig_tot)) {
                $option_date_parsed = DateTime::createFromFormat('Y-m-d', $optie_geldig_tot);
            } elseif (DateTime::createFromFormat('d-m-Y', $optie_geldig_tot)) {
                $option_date_parsed = DateTime::createFromFormat('d-m-Y', $optie_geldig_tot);
            } elseif (DateTime::createFromFormat('m/d/Y', $optie_geldig_tot)) {
                $option_date_parsed = DateTime::createFromFormat('m/d/Y', $optie_geldig_tot);
            }

            if ($option_date_parsed) {
                $today = new DateTime();
                $interval = $today->diff($option_date_parsed);
                $days_diff = (int)$interval->format('%r%a');

                $option_date_formatted = $option_date_parsed->format('j F Y');
                $output .= '<span>In optie t/m: ' . $option_date_formatted;
                if ($days_diff > 0) {
                    $output .= ' (Nog ' . $days_diff . ' dag' . ($days_diff > 1 ? 'en' : '') . ')';
                } elseif ($days_diff === 0) {
                    $output .= ' (Laatste dag!)';
                } else {
                    $days_elapsed = abs($days_diff);
                    $output .= ' (' . $days_elapsed . ' dag' . ($days_elapsed > 1 ? 'en' : '') . ' verlopen)';
                }
                $output .= '</span>';
            }
        }
        $output .= '</div>'; // Sluit wc-modal-top-info

        // Bewerkknop voor Maatwerk bestellingen (alleen voor admins)
        $current_user = wp_get_current_user();
        $user_roles = (array) $current_user->roles;
        if (in_array('administrator', $user_roles)) {
            $edit_url = 'https://banquetingportaal.nl/alg/maatwerk/?maatwerk_edit=' . $order_id;
            $output .= '<div style="text-align: right; margin-bottom: 15px;">';
            $output .= '<a href="#" onclick="
                var width = 800;
                var height = 800;
                var left = (screen.width / 2) - (width / 2);
                var top = (screen.height / 2) - (height / 2);
                window.open(\'' . esc_url($edit_url) . '\', \'_blank\', \'width=\' + width + \',height=\' + height + \',top=\' + top + \',left=\' + left + \',resizable=yes,scrollbars=yes\');
                return false;
            " class="wc-edit-button" style="background-color: #4CAF50; color: #fff; padding: 8px 12px; border-radius: 5px; text-decoration: none; font-weight: bold;">Bewerken</a>';
            $output .= '</div>';
        }

        // Klantgegevens
        $output .= '<div class="wc-modal-detail-section">';
        $output .= '<h4>Klantgegevens</h4>';
        $output .= '<p>' . esc_html($maatwerk_voornaam) . ' ' . esc_html($maatwerk_achternaam) . '</p>';
        if (!empty($bedrijfsnaam)) {
            $output .= '<p>' . esc_html($bedrijfsnaam) . '</p>';
        }
        $output .= '<p><a href="mailto:' . esc_attr($maatwerk_email) . '">' . esc_html($maatwerk_email) . '</a></p>';
        $output .= '<p><a href="tel:' . esc_attr($maatwerk_telefoonnummer) . '">' . esc_html($maatwerk_telefoonnummer) . '</a></p>';
        $output .= '</div>';

		// Leveringsgegevens
		$output .= '<div class="wc-modal-detail-section">';
		$output .= '<h4>Leveringsgegevens</h4>';

		$time_display_maatwerk = '';
		if (!empty($start_tijd)) {
		    $time_display_maatwerk .= esc_html($start_tijd);
		}
		if (!empty($eind_tijd)) {
		    if (!empty($time_display_maatwerk)) {
		        $time_display_maatwerk .= ' - ';
		    }
		    $time_display_maatwerk .= esc_html($eind_tijd);
		}

		if (!empty($time_display_maatwerk)) {
		    $output .= '<p>🕒 ' . $time_display_maatwerk . '</p>';
		}

		$full_address = '';
		if (!empty($straat_huisnummer)) {
		    $full_address .= esc_html($straat_huisnummer);
		}
		if (!empty($postcode)) {
		    if (!empty($full_address)) $full_address .= ', ';
		    $full_address .= esc_html($postcode);
		}
		if (!empty($plaats)) {
		    if (!empty($full_address)) $full_address .= ', ';
		    $full_address .= esc_html($plaats);
		}
		if (!empty($full_address)) {
		    $output .= '<p>📍 ' . $full_address . '</p>';
		} else {
		    $output .= '<p>📍 Locatie onbekend</p>';
		}

		// Personen/medewerkers
		if (!empty($aantal_personen)) {
		    $output .= '<p>👥 <b>' . esc_html($aantal_personen) . '</b> Personen</p>';
		}
		if (!empty($aantal_medewerkers)) {
		    $count = intval($aantal_medewerkers);
		    $label = $count === 1 ? 'Medewerker' : 'Medewerkers';
		    $output .= '<p>👷 <b>' . esc_html($count) . '</b> ' . esc_html($label) . '</p>';
		}

		// Belangrijke opmerking
		$important_opmerking = get_post_meta($order_id, 'important_opmerking', true);
		if (!empty($important_opmerking)) {
		    $output .= '<div class="wc-modal-detail-section">';
		    $output .= '<p><strong>✏️</strong> ' . esc_html($important_opmerking) . '</p>';
		    $output .= '</div>';
		}

		// Medewerkerdetails
		$employee_details = get_post_meta($order_id, '_employee_details', true);
		if (!is_array($employee_details)) {
		    $employee_details = array();
		}

		$target_count = (!empty($aantal_medewerkers) && intval($aantal_medewerkers) > 0)
		    ? intval($aantal_medewerkers)
		    : (count($employee_details) > 0 ? count($employee_details) : 0);
		if ($target_count === 0 && count($employee_details) > 0) {
		    $target_count = count($employee_details);
		}

		$output .= '<div class="wc-modal-detail-section">';
		$output .= '<br>';
		$output .= '<h4>Medewerker Details</h4>';
		if ($target_count === 0) {
		    $output .= '<p>Geen medewerkergegevens.</p>';
		} else {
		    for ($i = 0; $i < $target_count; $i++) {
		        $emp = isset($employee_details[$i]) ? $employee_details[$i] : array();
		        $name = (isset($emp['name']) && trim($emp['name']) !== '') ? esc_html($emp['name']) : 'Medewerker niet ingevuld';
		        $start = !empty($emp['start']) ? esc_html($emp['start']) : '';
		        $end = !empty($emp['end']) ? esc_html($emp['end']) : '';
		        $output .= '<p>' . $name . ': ' . $start . ' - ' . $end . '</p>';
		    }
		}
		$output .= '</div>';

		// Algemene opmerkingen
		if (!empty($opmerkingen)) {
		    $output .= '<div class="wc-modal-detail-section">';
		    $output .= '<h4>Opmerkingen</h4>';
		    $output .= '<p class="no-title">' . nl2br(esc_html($opmerkingen)) . '</p>';
		    $output .= '</div>';
		}

		// PDF links
		$current_user_is_admin = current_user_can('manage_options');
		$filtered_pdfs = array();

		if (!empty($pdf_uploads) && is_array($pdf_uploads)) {
		    foreach ($pdf_uploads as $pdf_file) {
		        $visibility = isset($pdf_file['visibility']) ? $pdf_file['visibility'] : 'public';
		        if ($visibility === 'private') {
		            if ($current_user_is_admin) {
		                $filtered_pdfs[] = $pdf_file;
		            }
		        } else {
		            $filtered_pdfs[] = $pdf_file;
		        }
		    }

		    if (!empty($filtered_pdfs)) {
		        $output .= '<div class="wc-modal-detail-section ordered-products">';
		        $output .= '<h4>Bijgevoegde PDF\'s</h4>';
		        $output .= '<ul>';
		        foreach ($filtered_pdfs as $pdf_file) {
		            $visibility = isset($pdf_file['visibility']) ? $pdf_file['visibility'] : 'public';
		            if (is_array($pdf_file) && isset($pdf_file['url'])) {
		                $filename = isset($pdf_file['filename']) ? esc_html($pdf_file['filename']) : 'Download PDF';
		                $output .= '<li>';
		                $output .= '<a href="' . esc_url($pdf_file['url']) . '" target="_blank">' . $filename . '</a>';
		                if ($visibility === 'private') {
		                    $output .= ' <span style="color: #aaa; font-size: 0.85em;">(Verborgen voor medewerkers)</span>';
		                }
		                $output .= '</li>';
		            } else {
		                $output .= '<li>';
		                $output .= '<a href="' . esc_url($pdf_file) . '" target="_blank">Download PDF</a>';
		                $output .= '</li>';
		            }
		        }
		        $output .= '</ul>';
		        $output .= '</div>';
		    }
		}

    } else {
        wp_send_json_error('Onbekend order type.');
    }

	// Alleen bij maatwerk orders het logboek tonen
	if ($order_type === 'maatwerk') {
	    $output .= build_logboek_html($order_id);
	}

	wp_send_json_success($output); // Gebruik wp_send_json_success voor consistente AJAX-respons
}



/**
 * AJAX handler om een dagelijks productoverzicht te genereren.
 */

function wc_get_daily_product_summary_handler() {
    $day_date_str = isset($_POST['day_date']) ? sanitize_text_field($_POST['day_date']) : '';

    if (empty($day_date_str)) {
        wp_send_json_error('Geen geldige datum opgegeven voor dagoverzicht.');
    }

    // Zorg ervoor dat de datum in het juiste formaat is voor de meta_query
    $day_date_query_format = date('Y-m-d', strtotime($day_date_str));
    $day_date_display_format = date_i18n('l j F Y', strtotime($day_date_str));

    $product_summary_by_category = []; // Nieuwe structuur: category => [product => quantity]

    $args_wc = array(
        'post_type'      => 'shop_order',
        'posts_per_page' => -1,
        // Sluit 'wc-cancelled' uit van de statussen voor het dagoverzicht
        'post_status'    => array_diff(array_keys(wc_get_order_statuses()), ['wc-cancelled']),
        'meta_query'     => array(
            array(
                'key'     => 'pi_system_delivery_date',
                'value'   => $day_date_query_format,
                'compare' => '=',
                'type'    => 'DATE'
            )
        )
    );

    $wc_orders = get_posts($args_wc);

    foreach ($wc_orders as $order_post) {
        $order = wc_get_order($order_post->ID);
        // Verwerk alleen als de order niet geannuleerd is
        if (!$order || $order->get_status() === 'cancelled') {
            continue;
        }

        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $product = wc_get_product($product_id);
            $product_name = $item->get_name();
            $quantity = $item->get_quantity();

            $category_name = 'Overig'; // Standaard categorie
            if (stripos($product_name, 'Lunch') !== false) {
                $category_name = '<u>Lunch opties</u>'; // Nieuwe specifieke categorie voor lunch
            } else if ($product) {
                $categories = $product->get_category_ids(); // Krijg een array van categorie ID's
                if (!empty($categories)) {
                    $first_category_id = $categories[0];
                    $term = get_term($first_category_id, 'product_cat');
                    if ($term && !is_wp_error($term)) {
                        $category_name = $term->name;
                    }
                }
            }

            // Sla op in de geneste array
            if (!isset($product_summary_by_category[$category_name])) {
                $product_summary_by_category[$category_name] = [];
            }
            if (isset($product_summary_by_category[$category_name][$product_name])) {
                $product_summary_by_category[$category_name][$product_name] += $quantity;
            } else {
                $product_summary_by_category[$category_name][$product_name] = $quantity;
            }
        }
    }
    wp_reset_postdata(); // Reset postdata na WooCommerce query

    $output = '<div class="wc-modal-top-info">';
    $output .= '' . esc_html($day_date_display_format);
    $output .= '</div>';

    if (!empty($product_summary_by_category)) {
        $output .= '<div class="wc-modal-detail-section ordered-products">';
        $output .= '<ul>';

        // Scheid 'Lunch opties' producten
        $lunch_products_display = [];
        if (isset($product_summary_by_category['Lunch opties'])) {
            $lunch_products_raw = $product_summary_by_category['Lunch opties'];
            ksort($lunch_products_raw); // Sorteer producten binnen Lunch alfabetisch
            foreach ($lunch_products_raw as $product_name => $total_quantity) {
                $lunch_products_display[] = ['name' => esc_html($product_name), 'qty' => esc_html($total_quantity)];
            }
            unset($product_summary_by_category['Lunch opties']);
        }

        // Sorteer alle andere categorieën alfabetisch op categorienaam
        ksort($product_summary_by_category);

        // Bereid en sorteer andere producten voor weergave
        $other_products_display = [];
        foreach ($product_summary_by_category as $category_name => $products_in_category) {
            ksort($products_in_category); // Sorteer producten binnen elke andere categorie alfabetisch
            foreach ($products_in_category as $product_name => $total_quantity) {
                $other_products_display[] = ['name' => esc_html($product_name), 'qty' => esc_html($total_quantity)];
            }
        }

        // Toon eerst andere producten
        foreach ($other_products_display as $product_data) {
            $output .= '<li><div class="product-name-qty"><span>' . $product_data['name'] . '</span> <span>x ' . $product_data['qty'] . '</span></div></li>';
        }

        // Toon daarna de "Lunch opties" producten, voorafgegaan door het label als er producten zijn
        if (!empty($lunch_products_display)) {
            $output .= '<li><div class="product-name-qty" style="margin-top: 15px;"><strong>Lunch opties</strong></div></li>'; // Categorielabel voor Lunch
            foreach ($lunch_products_display as $product_data) {
                $output .= '<li><div class="product-name-qty"><span>' . $product_data['name'] . '</span> <span>x ' . $product_data['qty'] . '</span></div></li>';
            }
        }

        $output .= '</ul>';
        $output .= '</div>';
    } else {// --- Status-lijst via ?status=... (VISUEEL als dagweergave) -----------------
if (isset($_GET['status'])) {
    $raw_status = sanitize_text_field($_GET['status']);

    // Normaliseer naar slug
    $norm = strtolower(trim($raw_status));
    $norm = str_replace([' ', '_'], '-', $norm);

    // Aliases toestaan
    $aliases = [
        'datum-in-optie' => 'datum-in-optie',
        'datum in optie' => 'datum-in-optie',
        'in-optie'       => 'in-optie',
        'in optie'       => 'in-optie',
    ];
    $status_key = isset($aliases[$norm]) ? $aliases[$norm] : $norm;

    // Haal ALLE maatwerk-bestellingen met deze status op (geen weekfilter)
    $args_status_list = [
        'post_type'      => 'maatwerk_bestelling',
        'posts_per_page' => -1,
        'post_status'    => ['publish','nieuw','in-optie','in_behandeling','bevestigd','afgerond','geannuleerd','geaccepteerd','afgewezen'],
        'meta_query'     => [
            [
                'key'     => 'order_status',
                'value'   => $status_key,
                'compare' => '=',
            ],
        ],
        // sorteer op datum oplopend
        'orderby'  => 'meta_value',
        'meta_key' => 'datum',
        'order'    => 'ASC',
    ];
    $status_posts = get_posts($args_status_list);
// --- ENKEL "In-optie" lijst via ?status=In-optie ---------------------------
if (isset($_GET['status'])) {
    $raw_status = sanitize_text_field($_GET['status']);
    $norm = strtolower(trim($raw_status));
    $norm = str_replace([' ', '_'], '-', $norm);

    // Alleen reageren op "in-optie"
    if ($norm === 'in-optie' || $norm === 'in optie') {

        // Haal alle maatwerk_bestelling met status "in-optie", sorteer op datum/tijd
        $args_in_optie = [
            'post_type'      => 'maatwerk_bestelling',
            'posts_per_page' => -1,
            'post_status'    => ['publish','nieuw','in-optie','in_behandeling','bevestigd','afgerond','geannuleerd','geaccepteerd','afgewezen'],
            'meta_query'     => [
                [
                    'key'     => 'order_status',
                    'value'   => 'in-optie',
                    'compare' => '=',
                ],
            ],
            'orderby'  => 'meta_value',
            'meta_key' => 'datum',
            'order'    => 'ASC',
        ];
        $posts_in_optie = get_posts($args_in_optie);

        // Optioneel: binnen dezelfde datum ook nog op start_tijd sorteren
        usort($posts_in_optie, function($a, $b){
            $da = get_post_meta($a->ID, 'datum', true);
            $db = get_post_meta($b->ID, 'datum', true);
            $ta = get_post_meta($a->ID, 'start_tijd', true);
            $tb = get_post_meta($b->ID, 'start_tijd', true);
            $da_ts = strtotime(str_replace('/', '-', $da));
            $db_ts = strtotime(str_replace('/', '-', $db));
            if ($da_ts === $db_ts) {
                return strcmp($ta, $tb);
            }
            return $da_ts <=> $db_ts;
        });
 
        if (!empty($posts_in_optie)) {
            foreach ($posts_in_optie as $p) {
                $id            = $p->ID;
                $order_nummer  = get_post_meta($id, 'order_nummer', true);
                $titel         = get_the_title($id);
                $start_tijd    = get_post_meta($id, 'start_tijd', true);
                $eind_tijd     = get_post_meta($id, 'eind_tijd', true);
                $datum         = get_post_meta($id, 'datum', true);
                $bedrijfsnaam  = get_post_meta($id, 'bedrijfsnaam', true);
                $referentie    = get_post_meta($id, 'referentie', true);
                $aantal_pers   = get_post_meta($id, 'aantal_personen', true);
                $opm           = get_post_meta($id, '_maatwerk_bestelling_opmerkingen', true);
                $has_note      = !empty($opm);

                // Tijd + datum tonen zoals in dagweergave
                $tijd_str = trim(($start_tijd ? $start_tijd : '') . ($eind_tijd ? '–' . $eind_tijd : ''));
                $title_text = trim(($order_nummer ? '#' . $order_nummer . ' ' : '') . $titel);

                // Kaart
                $out .= '<div class="wc-weekagenda-item-google wc-weekagenda-item-maatwerk"'
                     .  ' data-order-type="maatwerk"'
                     .  ' data-post-status="in-optie"'
                     .  ' data-has-note="' . ($has_note ? 'true' : 'false') . '"'
                     .  ' data-order-id="' . esc_attr($id) . '">';

                // Badge (oranje of jouw eigen klasse)
                $out .= '  <span class="wc-order-badge status-maatwerk-orange">In-optie</span>';

                // Titel klikbaar (voor je bestaande modal/open gedrag)
                $out .= '  <div>';
                $out .= '      <a href="#" class="wc-weekagenda-title"'
                     .  '         data-order-type="maatwerk"'
                     .  '         data-order-id="' . esc_attr($id) . '">'
                     .  esc_html($title_text) . '</a>';
                $out .= '  </div>';

                // Datum + tijd
                if (!empty($datum)) {
                    $out .= '  <div class="wc-info-text" style="margin-top:2px;">'
                         .  esc_html(date_i18n('j F Y', strtotime(str_replace('/', '-', $datum))))
                         .  '</div>';
                }
                if ($tijd_str) {
                    $out .= '  <span class="wc-time-display wc-weekagenda-time">'
                         .  esc_html($tijd_str) . '</span>';
                }

                // Bedrijf + meta (zoals normaal)
                if ($bedrijfsnaam) {
                    $out .= '  <span class="wc-company-name-display">'
                         .  esc_html($bedrijfsnaam) . '</span>';
                }
                $bits = [];
                if ($referentie)  $bits[] = 'Ref: ' . $referentie;
                if ($aantal_pers) $bits[] = $aantal_pers . ' pers.';
                if (!empty($bits)) {
                    $out .= '  <div class="wc-info-text" style="margin-top:4px;">'
                         .  esc_html(implode(' • ', $bits)) . '</div>';
                }

                $out .= '</div>'; // kaart
            }
        } else {
            $out .= '<div class="wc-weekagenda-leeg-google" style="width:100%;">Geen bestellingen met status In-optie.</div>';
        }

        $out .= '  </div>'; // .wc-weekagenda-google
        $out .= '</div>';   // .wc-weekagenda-wrapper

        // Alleen deze weergave tonen
        return $out;
    }
}
// --- EINDE enkel "In-optie" lijst ------------------------------------------

    // Groepeer per datum (key in Y/m/d voor consistentie met je agenda)
    $by_date = [];
    foreach ($status_posts as $p) {
        $id     = $p->ID;
        $datum  = get_post_meta($id, 'datum', true);
        if (!$datum) { continue; }

        // Robuust parsen en normaliseren naar Y/m/d
        $dt = DateTime::createFromFormat('Y-m-d', $datum);
        if (!$dt) $dt = DateTime::createFromFormat('d-m-Y', $datum);
        if (!$dt) $dt = DateTime::createFromFormat('m/d/Y', $datum);
        if (!$dt) { continue; }

        $key = $dt->format('Y/m/d');
        if (!isset($by_date[$key])) $by_date[$key] = [];
        $by_date[$key][] = $id;
    }
 
    if (!empty($by_date)) {
        // Sorteer datums oplopend
        ksort($by_date);
        foreach ($by_date as $date_key => $ids) {
            // Header per dag (zoals dagweergave)
            $dobj = DateTime::createFromFormat('Y/m/d', $date_key);
            $weekday = $dobj ? date_i18n('l', $dobj->getTimestamp()) : '';
            $date_lbl = $dobj ? date_i18n('j F Y', $dobj->getTimestamp()) : $date_key;

            $out .= '<div class="wc-weekagenda-dag-google">';
            $out .= '<h4>' . esc_html(ucfirst($weekday)) . '</h4>';
            $out .= '<span class="wc-day-date">' . esc_html($date_lbl) . '</span>';

            if (!empty($ids)) {
                foreach ($ids as $oid) {
                    // Velden ophalen (zoals je in agenda doet)
                    $order_nummer   = get_post_meta($oid, 'order_nummer', true);
                    $start_tijd     = get_post_meta($oid, 'start_tijd', true);
                    $eind_tijd      = get_post_meta($oid, 'eind_tijd', true);
                    $bedrijfsnaam   = get_post_meta($oid, 'bedrijfsnaam', true);
                    $referentie     = get_post_meta($oid, 'referentie', true);
                    $aantal_pers    = get_post_meta($oid, 'aantal_personen', true);
                    $opm            = get_post_meta($oid, '_maatwerk_bestelling_opmerkingen', true);
                    $has_note       = !empty($opm);

                    // Titel samenstellen (zoals elders)
                    $title = get_the_title($oid);
                    $title_text = trim(($order_nummer ? '#' . $order_nummer . ' ' : '') . $title);

                    // Tijd-string
                    $tijd_str = trim(($start_tijd ? $start_tijd : '') . ($eind_tijd ? '–' . $eind_tijd : ''));

                    // Badge-kleur voor "datum-in-optie"
                    $badge_class = ($status_key === 'datum-in-optie') ? 'status-maatwerk-purple' : 'status-maatwerk-orange';

                    // Kaart (zelfde classes & data-attributes)
                    $out .= '<div class="wc-weekagenda-item-google wc-weekagenda-item-maatwerk"'
                         . ' data-order-type="maatwerk"'
                         . ' data-post-status="' . esc_attr($status_key) . '"'
                         . ' data-has-note="' . ($has_note ? 'true' : 'false') . '"'
                         . ' data-order-id="' . esc_attr($oid) . '"'
                         . '>';

                    // Badge
                    $out .= '<span class="wc-order-badge ' . esc_attr($badge_class) . '">'
                         . esc_html(ucfirst(str_replace('-', ' ', $status_key)))
                         . '</span> ';

                    // Titel klikbaar (zodat je bestaande modal/open gedrag kan hergebruiken)
                    $out .= '<div>';
                    $out .= '<a href="#" class="wc-weekagenda-title"'
                         . ' data-order-type="maatwerk"'
                         . ' data-order-id="' . esc_attr($oid) . '">'
                         . esc_html($title_text) . '</a>';
                    $out .= '</div>';

                    // Tijd + bedrijf/extra info (zelfde semantiek/klassen)
                    if ($tijd_str) {
                        $out .= '<span class="wc-time-display wc-weekagenda-time">'
                             . esc_html($tijd_str) . '</span>';
                    }
                    if ($bedrijfsnaam) {
                        $out .= '<span class="wc-company-name-display">'
                             . esc_html($bedrijfsnaam) . '</span>';
                    }
                    if ($referentie || $aantal_pers) {
                        $meta_bits = [];
                        if ($referentie)  $meta_bits[] = 'Ref: ' . $referentie;
                        if ($aantal_pers) $meta_bits[] = $aantal_pers . ' pers.';
                        $out .= '<div class="wc-info-text" style="margin-top:4px;">'
                             . esc_html(implode(' • ', $meta_bits)) . '</div>';
                    }

                    $out .= '</div>'; // .wc-weekagenda-item-google
                }
            } else {
                $out .= '<div class="wc-weekagenda-leeg-google">Geen bestellingen voor deze dag.</div>';
            }

            $out .= '</div>'; // .wc-weekagenda-dag-google
        }
    } else {
        $out .= '<div class="wc-weekagenda-leeg-google" style="width:100%;">Geen bestellingen gevonden.</div>';
    }

    $out .= '</div>'; // .wc-weekagenda-google
    $out .= '</div>'; // .wc-weekagenda-wrapper

    // Toon alleen deze statusweergave (geen gewone agenda)
    return $out;
}
// --- EINDE status-lijst ----------------------------------------------------

        $output .= '<div class="wc-modal-detail-section">';
        $output .= '<p style="text-align: center;">Geen banqueting orders vandaag</p>';
        $output .= '</div>';
    }

    wp_send_json_success($output);
}
