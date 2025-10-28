<?php

if (! defined('ABSPATH')) {
    exit;
}

function hageman_catering_render_agenda_content($agenda, $week_start_timestamp, $day_filter = '') {

    $output = '';

    $dagen_data = array(
        'maandag'   => array('emoji' => ''),
        'dinsdag'   => array('emoji' => ''),
        'woensdag'  => array('emoji' => ''),
        'donderdag' => array('emoji' => ''),
        'vrijdag'   => array('emoji' => ''),
        'zaterdag'  => array('emoji' => ''),
        'zondag'    => array('emoji' => '')
    );
    $dagen_keys = array_keys($dagen_data);

    for ($i = 0; $i < 7; $i++) {
        $day_ts = strtotime("+$i day", $week_start_timestamp);
        $day_key = date('Y/m/d', $day_ts);
		$day_link = date('Y-m-d', $day_ts);
        $dagnaam = $dagen_keys[$i];
		if ($day_filter && $day_filter !== $day_link) {
				continue;
		}
        $datum   = date('d-m-Y', $day_ts);
        $emoji   = $dagen_data[$dagnaam]['emoji'];

		// Bouw eerst de huidige URL (path + query) en verwijder oude 'day'
		$current_url = ( is_ssl() ? 'https://' : 'http://' )
		             . $_SERVER['HTTP_HOST']
		             . $_SERVER['REQUEST_URI'];
		$base_url    = remove_query_arg( 'filter_day', $current_url );
		$link        = esc_url( add_query_arg( 'filter_day', $day_link, $base_url ) );

		
		// Render klikbare dagnaam
		$output .= '<div class="wc-weekagenda-dag-google" data-day-index="'. $i .'">';
		$output .= '<h4 style="display:flex; align-items:center; gap:6px; margin:0;">'
		         . '<a href="'. $link .'" style="flex-grow:1; text-decoration:none; color:#099641;">'
		         . ucfirst($dagnaam)
		         . '</a>';

		if ( current_user_can('manage_options') ) {
		    // Popup-variant voor “Maatwerk toevoegen” zonder underline
		    $url = 'https://banquetingportaal.nl/alg/maatwerk/?add_date=' . $day_link;
		    $output .= '<a href="#" class="wc-add-maatwerk-day-button" style="text-decoration:none;" '
		             . 'onclick="window.open(\'' . esc_url($url) . '\', \'maatwerk_popup\', \'width=800,height=800,top=\'+((screen.height/2)-(800/2))+\',left=\'+((screen.width/2)-(800/2))); return false;" '
		             . 'aria-label="Maatwerk toevoegen op '. esc_attr($day_link) .'" '
		             . 'title="Maatwerk toevoegen op '. esc_attr($day_link) .'">'
		             . '<span style="font-weight:bold;">+</span>'
		             . '</a>';

		}

		$output .= '</h4>';

        $output .= '<p class="wc-day-date">' . $datum . '</p>'; // Datum onder de dagnaam

        $has_woocommerce_orders = false; // Vlag om te controleren of er WC orders zijn voor deze dag

        if (isset($agenda[$day_key])) {
            // Sorteer bestellingen op tijd, ongeacht type
            usort($agenda[$day_key], function($a, $b) {
				$time_a = !empty($a['tijd']) ? $a['tijd'] : '00:00';
				$time_b = !empty($b['tijd']) ? $b['tijd'] : '00:00';
				return strcmp($time_a, $time_b);
            });

			
            foreach ($agenda[$day_key] as $item) {
                if ($item['type'] === 'woocommerce') {
                    $has_woocommerce_orders = true; // Stel de vlag in als er een WC order is
                }

				// 🎨 Eigen Agenda Events renderen
				if ($item['type'] === 'agenda_event') {
				    $output .= '<div class="wc-weekagenda-item-google" '
				             .  'style="border-left:4px solid ' . esc_attr($item['color']) . ';">'
				             .    '<span class="wc-time-display">' . esc_html($item['tijd']) . '</span> '
				             .    '<b>' . esc_html($item['title']) . '</b>'
				             .  '</div>';
				    continue;
				}
				

				// 🎉 verjaardagen renderen
				if ($item['type'] === 'birthday') {
				    $output .= '<div class="wc-weekagenda-item-google birthday-item">'
				             .   '<span class="wc-info-text">'
				             .       '🎉 <b>' . esc_html($item['user_name']) . ' is jarig!</b>'
				             .   '</span>'
				             . '</div>';
				    continue;
				}

                // Bepaal de badge klasse op basis van de orderstatus EN het type
                $badge_class = 'wc-order-badge';
                $item_type_class = ''; // Nieuwe variabele voor de item type klasse
                $order_number_display = esc_html($item['sequential_order_number']); // Standaard weergave
                $option_info_badge = ''; // Variabele voor "In optie tot" informatie in badge

                if ($item['type'] === 'woocommerce') {
                    $item_type_class = 'wc-weekagenda-item-woocommerce'; // Blauwe streep
                    if ($item['post_status'] === 'wc-processing' || $item['post_status'] === 'wc-completed') { // Processing en Completed zijn groen
                        $badge_class .= ' status-wc-green';
                    } else if ($item['post_status'] === 'wc-on-hold') { // On-hold blijft oranje
                        $badge_class .= ' status-wc-orange';
                    } else if ($item['post_status'] === 'wc-cancelled') {
                        $badge_class .= ' status-wc-red';
                    }
                } else if ($item['type'] === 'maatwerk') {
                    $item_type_class = 'wc-weekagenda-item-maatwerk'; // Paarse streep
                    $maatwerk_status = $item['post_status'];
                    $optie_geldig_tot = isset($item['optie_geldig_tot']) ? $item['optie_geldig_tot'] : '';

                    // DEBUG LOG: Log de maatwerk status en optie_geldig_tot

                    // Formatteer de maatwerk status voor weergave
                    $formatted_maatwerk_status = ucfirst(str_replace('_', ' ', $maatwerk_status));

                    // Nieuwe logica voor maatwerk statussen: 'geaccepteerd' en 'afgewezen' worden nu ook groen/rood
                    if ($maatwerk_status === 'in_behandeling' || $maatwerk_status === 'bevestigd' || $maatwerk_status === 'afgerond' || $maatwerk_status === 'geaccepteerd') {
                        $badge_class .= ' status-maatwerk-green'; // Groen voor in behandeling, bevestigd, afgerond en geaccepteerd
                    } else if ($maatwerk_status === 'geannuleerd' || $maatwerk_status === 'afgewezen') {
                        $badge_class .= ' status-maatwerk-red'; // Rood voor geannuleerd en afgewezen
                    } else if ($maatwerk_status === 'in-optie' || $maatwerk_status === 'nieuw') {
                        $badge_class .= ' status-maatwerk-orange';
} else if ($maatwerk_status === 'datum-in-optie') {
    $badge_class .= ' status-maatwerk-purple'; // Paars voor datum-in-optie


                        // Toon "in optie tot" informatie in de badge
                        if ($maatwerk_status === 'in-optie' && !empty($optie_geldig_tot)) {
                            $option_date_parsed = null;
                            // Probeer verschillende datumformaten
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
                                $days_diff = (int)$interval->format('%r%a'); // %r voor teken, %a voor dagen zonder teken

                                if ($days_diff > 0) {
                                    $option_info_badge = ' (Nog ' . $days_diff . ' dag' . ($days_diff > 1 ? 'en' : '') . ')';
                                } elseif ($days_diff === 0) {
                                    $option_info_badge = ' (Laatste dag)';
                                } else {
                                    $days_elapsed = abs($days_diff);
                                    $option_info_badge = ' (' . $days_elapsed . ' dag' . ($days_elapsed > 1 ? 'en' : '') . ' verlopen)';
                                }
                            }
                        }
                    } else {
                        // Fallback voor andere onbekende maatwerk statussen (standaard oranje)
                        $badge_class .= ' status-maatwerk-orange';
                    }
                    // Ordernummer en eventuele optie info voor de badge
                    $order_number_display = esc_html($item['sequential_order_number']) . $option_info_badge;
                }

                // Tijdopmaaklogica (Vroegste Start - Laatste Eind)
                $all_times = [];
                if (!empty($item['tijd']) && $item['tijd'] !== '🕒') {
                    $parts = explode(' - ', $item['tijd']);
                    foreach ($parts as $part) {
                        $part = trim($part);
                        if (strpos($part, ':') !== false) {
                            $all_times[] = $part;
                        }
                    }
                }
                if (!empty($item['eindtijd'])) {
                    $parts = explode(' - ', $item['eindtijd']);
                    foreach ($parts as $part) {
                        $part = trim($part);
                        if (strpos($part, ':') !== false) {
                            $all_times[] = $part;
                        }
                    }
                }
                $all_times = array_unique($all_times);
                usort($all_times, function($a, $b) {
                    return strtotime($a) - strtotime($b);
                });

                $display_time_range = '';
                if (!empty($all_times)) {
                    $earliest_time = $all_times[0];
                    $latest_time = end($all_times);
                    $display_time_range = esc_html($earliest_time) . ' - ' . esc_html($latest_time);
                } else if ($item['tijd'] === '🕒') {
                    $display_time_range = '🕒';
                }

                // Belangrijk: Voeg data-order-type en data-has-note toe aan het item div
				$output .= '<div class="wc-weekagenda-item-google ' . esc_attr($item_type_class) . '" '
				         . 'data-order-id="'     . esc_attr($item['order_id'])     . '" '
				         . 'data-order-type="'   . esc_attr($item['type'])         . '" '
				         . 'data-post-status="'  . esc_attr($item['post_status'])  . '"'
				         . (!empty($item['has_note']) ? ' data-has-note="true"' : '')
				         . '>';

                // Ordernummer in badge bovenaan (gebruik sequential_order_number en optie info)
                $output .= '<div class="'.esc_attr($badge_class).'">' . $order_number_display . '</div>';

				if (!empty($item['billing_company'])) {
				    $output .= '<span class="wc-company-name-display">' . esc_html($item['billing_company']) . '</span>';
				}

				$output .= '<span class="wc-time-display">🕒 ' . $display_time_range . '</span>';

				if ( ! empty( $item['locatie'] ) ) {
				    $parts   = explode( ', ', $item['locatie'] );
				    $address = reset( $parts );
				} else {
				    $address = 'Niet opgegeven';
				}
				$output .= '<span class="wc-info-text"> 📍 ' . esc_html( $address ) . '</span>';

				// Personen op eigen regel - verbergt als de waarde 0 of leeg is
				if (!empty($item['personen']) && (int)$item['personen'] > 0) {
				    $output .= '<br><span class="wc-info-text">👥 <b>' . esc_html($item['personen']) . '</b> Personen</span>';
				}

				// Medewerkers / namen tonen
				if ($item['type'] === 'maatwerk') {
				    $target_count = (!empty($item['aantal_medewerkers']) && intval($item['aantal_medewerkers']) > 0)
				        ? intval($item['aantal_medewerkers'])
				        : (is_array($item['employee_details']) ? count($item['employee_details']) : 0);
				    $label = $target_count === 1 ? '' : '';

				    $names = [];
				    if (!empty($item['employee_details']) && is_array($item['employee_details'])) {
				        foreach ($item['employee_details'] as $e) {
				            if (isset($e['name']) && trim($e['name']) !== '') {
				                $names[] = $e['name'];
				            } else {
				                $names[] = 'Onbekend';
				            }
				        }
				    }

				    while (count($names) < $target_count) {
				        $names[] = 'Onbekend';
				    }

				    if (empty($names) && $target_count > 0) {
				        $output .= '<br><span class="wc-info-text">👷 <b>' . esc_html($target_count) . '</b> ' . esc_html($label) . '</span>';
				    } else {
				        $output .= '<br><span class="wc-info-text">👷 ' . esc_html(implode(', ', $names));
				        if ($target_count > 0) {
				            $output .= ' (' . $target_count . '' . esc_html($label) . ')';
				        }
				        $output .= '</span>';
				    }
				} else {
				    if (! empty( $item['aantal_medewerkers'] ) && intval( $item['aantal_medewerkers'] ) > 0 ) {
				        $count = intval( $item['aantal_medewerkers'] );
				        $label = $count === 1 ? 'Medewerker' : 'Medewerkers';
				        $output .= '<br><span class="wc-info-text">👷 <b>' . esc_html( $count ) . '</b> ' . esc_html( $label ) . '</span>';
				    }
				}

				// Belangrijke Opmerking (alleen bij maatwerk)
				if ( $item['type'] === 'maatwerk' && !empty($item['important_opmerking']) ) {
				    $output .= '<br><div class="wc-info-text"><strong>✏️</strong> '
				             . esc_html( $item['important_opmerking'] )
				             . '</div>';
				}
				
				// ✅ Turflijst-link tonen met tijd/rol-voorwaarden (alleen maatwerk)
if ($item['type'] === 'maatwerk' && !empty($item['order_id'])) {
    global $wpdb;
    $turftable = $wpdb->prefix . 'custom_form_entries';

    // Bestaat er een turflijst voor dit event?
    $has_turflijst = (int) $wpdb->get_var(
        $wpdb->prepare("SELECT 1 FROM {$turftable} WHERE event_id = %d LIMIT 1", (int) $item['order_id'])
    );

    if ($has_turflijst) {
        $is_admin     = current_user_can('administrator');
        $before_eight = (int) current_time('G') < 8; // 0..23

        $turflijst_url = home_url('/alg/turf/?turflijst_edit=' . (int) $item['order_id']);
        $label_prefix  = '✅ Turflijst ingevuld';

        if ($is_admin || $before_eight) {
            // Popup 'systeemvenster'
            $js_open = "window.open('" . esc_url($turflijst_url) . "', 'turflijstWin', 'width=1000,height=800,scrollbars=yes,resizable=yes'); return false;";
            $output .= '<div class="wc-info-text" style="margin-top:6px;">'
                     . $label_prefix . ' '
                     . '(<a href="#" onclick="' . esc_attr($js_open) . '" style="text-decoration:underline;">bekijk/wijzig</a>)'
                     . '</div>';
        } else {
            // Na 08:00 voor niet-beheerders: alleen melding
            $output .= '<div class="wc-info-text" style="margin-top:6px;">' . $label_prefix . '</div>';
        }
    }
    // Geen turflijst gevonden: niets tonen
}


                $output .= '<div class="wc-pakbon-button" style="margin:8px 0;">'
                         . do_shortcode(
                             '[wcpdf_download_pdf document_type="packing-slip" '
                           . 'order_id="' . esc_attr( $item['order_id'] ) . '" '
                           . 'link_text="📄 keuken order bekijken"]'
                         )
                         . '</div>';
                $output .= '</div>';
            }
        } else {
            $output .= '<div class="wc-weekagenda-leeg-google">Geen bestellingen vandaag! </div>';
        }

        $output .= '</div>';
    }
    return $output;
}
