<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Custom Maatwerk Functionaliteit (Zonder WooCommerce)
 *
 * Dit bestand bevat de shortcodes voor een maatwerk bestelformulier,
 * een bedankpagina, en de admin-instellingen voor het formulier.
 * De bestellingen worden opgeslagen als een Custom Post Type.
 */

/*
|--------------------------------------------------------------------------
| 2. Maatwerk Bestelformulier Shortcode
|--------------------------------------------------------------------------
*/

function display_maatwerk_bestelformulier($atts = []) {
    ob_start(); // Start output buffering

    $feedback_message = '';
    $edit_post_id = 0; // Standaard, geen bestelling wordt bewerkt
    $is_updating = false; // <-- TOEGEVOEGD: Initieel is het geen update
    $current_post_id = 0; // <-- TOEGEVOEGD: Initieel is er geen huidige post ID
    $should_auto_close = false;

    $form_data = array(
        'order_nummer'           => '',
        'maatwerk_voornaam'      => '',
        'maatwerk_achternaam'    => '',
        'maatwerk_email'         => '',
        'maatwerk_telefoonnummer' => '',
        'bedrijfsnaam'           => '',
        'straat_huisnummer'      => '',
        'postcode'               => '',
        'plaats'                 => '',
        'referentie'             => '',
        'datum'                  => '',
        'start_tijd'             => '',
        'eind_tijd'              => '',
        'aantal_medewerkers'     => '',
        'aantal_personen'        => '',
        'opmerkingen'            => '',
        'important_opmerking'    => '',
        'order_status'           => 'in-optie', // Standaard status
        'optie_geldig_tot'       => '',
        'user_id'                => '', // Nieuw veld voor gekoppelde gebruiker ID
        'employee_details'       => array(), // Nieuwe structuur voor medewerkers (naam + start/eind)
    );
    $pdf_attachments = array(); // Array om geüploade PDF's met hun zichtbaarheidsstatus op te slaan
    $copied_message = '';

	// Voor-ingevulde datum via add_date parameter in URL
    if (isset($_GET['add_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['add_date'])) {
        $form_data['datum'] = sanitize_text_field($_GET['add_date']);
    }

    $copied_message = '';
    if (isset($_GET['maatwerk_copy'])) {
        $copy_id   = absint($_GET['maatwerk_copy']);
        $copy_post = get_post($copy_id);
        if ($copy_post && $copy_post->post_type === 'maatwerk_bestelling') {
            foreach ($form_data as $key => $default) {
                if ($key === 'opmerkingen') {
                    $form_data[$key] = $copy_post->post_content;
                } elseif ($key === 'employee_details') {
                    $existing = get_post_meta($copy_id, '_employee_details', true);
                    $form_data[$key] = is_array($existing) ? $existing : array();
                } else {
                    $val = get_post_meta($copy_id, $key, true);
                    $form_data[$key] = $val !== '' ? $val : $default;
                }
            }
            $pdf_attachments = get_post_meta($copy_id, '_pdf_attachments', true) ?: array();
            $submit_button_text = 'Aanmaken';
            $edit_post_id = 0; // Zorg dat het altijd een nieuwe wordt!
            $is_updating = false; // <-- TOEGEVOEGD: Een kopie is geen update van het origineel
            $copied_message = '<div class="form-success">Bestelling succesvol gekopieerd! Pas eventueel aan en klik op <b>Aanmaken</b> om kopie op te slaan.</div>';
        }
    }

	
    // Bestaande “edit”-logica begint hier
    // Controleer of een bestelling wordt bewerkt
    if (isset($_GET['maatwerk_edit']) && $edit_post_id === 0) { // <-- AANGEPAST: Voorkom overschrijven als maatwerk_copy al gezet is
        $edit_query_val = sanitize_text_field($_GET['maatwerk_edit']);
        $edit_post = null;

        // Probeer eerst te zoeken op intern_order_nummer
        $args = array(
            'post_type'  => 'maatwerk_bestelling',
            'meta_key'   => 'order_nummer',
            'meta_value' => $edit_query_val,
            'posts_per_page' => 1,
            'post_status' => 'any',
        );
        $query = new WP_Query($args);
        if ($query->have_posts()) {
            $edit_post = $query->posts[0];
        } else {
            // Als niet gevonden op order_nummer, probeer op CPT ID
            $edit_post = get_post(absint($edit_query_val));
        }

        if ($edit_post && $edit_post->post_type === 'maatwerk_bestelling') {
            $edit_post_id = $edit_post->ID;
            $is_updating = true; // <-- TOEGEVOEGD: Dit is een update
            $current_post_id = $edit_post->ID; // <-- TOEGEVOEGD: Stel current_post_id in

            // Vul form_data met bestaande waarden
            foreach ($form_data as $key => $default_value) {
                if ($key === 'opmerkingen') {
                    // Post content voor het opmerkingen-veld
                    $form_data[$key] = $edit_post->post_content;
                } elseif ($key === 'important_opmerking') {
                    // Haal de belangrijke opmerking uit post meta
                    $meta_value = get_post_meta($edit_post_id, 'important_opmerking', true);
                    $form_data[$key] = $meta_value !== '' ? $meta_value : $default_value;
                } elseif ($key === 'employee_details') {
                    $existing_employees = get_post_meta($edit_post_id, '_employee_details', true);
                    $form_data[$key] = is_array($existing_employees) ? $existing_employees : array();
                } else {
                    // Alle andere velden uit de meta
                    $meta_value = get_post_meta($edit_post_id, $key, true);
                    $form_data[$key] = $meta_value !== '' ? $meta_value : $default_value;
                }
            }
            // Haal bestaande PDF bijlagen op
            $existing_pdfs = get_post_meta($edit_post_id, '_pdf_attachments', true);
            if (is_array($existing_pdfs)) {
                $pdf_attachments = $existing_pdfs;
            }

            // Pas de titel van het formulier aan
            $submit_button_text = 'Opslaan';
        }
    }

    if (!isset($submit_button_text)) {
        $submit_button_text = 'Aanmaken'; // Standaardtekst als er niet wordt bewerkt
    }

    // VERPLAATST EN AANGEPAST: Verwerk POST-gegevens hier
    if (isset($_POST['submit_bestelling']) && wp_verify_nonce($_POST['_wpnonce_maatwerk_form'], 'maatwerk_form_nonce')) {

        // Bepaal of we een bestaande post updaten
        if (isset($_POST['edit_post_id']) && absint($_POST['edit_post_id']) > 0) {
            $current_post_id = absint($_POST['edit_post_id']);
            $is_updating = true;
        } else {
            $is_updating = false;
        }

        // Vul form_data array met de geposte waarden om ze weer te geven als het formulier opnieuw geladen wordt
        foreach ($form_data as $key => $value) {
            if (isset($_POST[$key]) && $key !== 'employee_details') {
                if ($key === 'maatwerk_email') {
                    $form_data[$key] = sanitize_email($_POST[$key]);
                } elseif ($key === 'user_id') {
                    $form_data[$key] = absint($_POST[$key]);
                } elseif ($key === 'aantal_medewerkers' || $key === 'aantal_personen') {
                    $form_data[$key] = $_POST[$key] !== '' ? absint($_POST[$key]) : '';
                } else {
                    $form_data[$key] = sanitize_text_field($_POST[$key]);
                }
            }
        }
        $form_data['opmerkingen'] = isset($_POST['opmerkingen']) ? sanitize_textarea_field($_POST['opmerkingen']) : '';
        $form_data['employee_details'] = array();

        // Verwerk medewerker detail velden (naam + start/eind per medewerker)
        if (isset($_POST['employee_name']) && is_array($_POST['employee_name'])) {
            $names = array_map('sanitize_text_field', $_POST['employee_name']);
            $starts = isset($_POST['employee_start']) && is_array($_POST['employee_start']) ? $_POST['employee_start'] : array();
            $ends = isset($_POST['employee_end']) && is_array($_POST['employee_end']) ? $_POST['employee_end'] : array();

            $employee_details = array();
            for ($i = 0; $i < count($names); $i++) {
                $name = $names[$i];
                $start = isset($starts[$i]) ? sanitize_text_field($starts[$i]) : '';
                $end = isset($ends[$i]) ? sanitize_text_field($ends[$i]) : '';
                if ($name !== '' || $start !== '' || $end !== '') {
                    $employee_details[] = array(
                        'name' => $name,
                        'start' => $start,
                        'end' => $end,
                    );
                }
            }
            $form_data['employee_details'] = $employee_details;
        }

        $errors = array();

        // Verwerk nieuwe PDF uploads
        $new_pdf_attachments = array();
        if (!empty($_FILES['pdf_upload']['name'][0])) { // Check if any file was uploaded
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $files = $_FILES['pdf_upload'];
            $file_count = count($files['name']);

            for ($i = 0; $i < $file_count; $i++) {
                if (empty($files['name'][$i])) {
                    continue;
                }

                $file_to_upload = array(
                    'name'         => $files['name'][$i],
                    'type'         => $files['type'][$i],
                    'tmp_name'     => $files['tmp_name'][$i],
                    'error'        => $files['error'][$i],
                    'size'         => $files['size'][$i]
                );

                if ($file_to_upload['error'] === UPLOAD_ERR_OK) {
                    $uploaded_file = wp_handle_upload($file_to_upload, array('test_form' => false));

                    if (isset($uploaded_file['error']) && !empty($uploaded_file['error'])) {
                        $errors[] = 'Fout bij het uploaden van PDF: ' . esc_html($files['name'][$i]) . ' - ' . $uploaded_file['error'];
                    } else {
                        $visibility = isset($_POST['pdf_visibility'][$i]) && $_POST['pdf_visibility'][$i] === 'private' ? 'private' : 'public';
                        $new_pdf_attachments[] = array(
                            'url' => $uploaded_file['url'],
                            'visibility' => $visibility,
                            'filename' => sanitize_file_name($files['name'][$i]),
                        );
                    }
                } else {
                    if ($file_to_upload['error'] === UPLOAD_ERR_NO_FILE) {
                        continue;
                    }
                    $errors[] = 'Uploadfout voor bestand ' . esc_html($files['name'][$i]) . ': ' . $file_to_upload['error'];
                }
            }
        }

        // Combineer met eventueel behouden oude PDF's
        $final_pdf_attachments = array();
        if ($is_updating && isset($_POST['existing_pdf_url'])) {
            foreach ($_POST['existing_pdf_url'] as $index => $url) {
                $url = esc_url_raw($url);
                $visibility = isset($_POST['existing_pdf_visibility'][$index]) && $_POST['existing_pdf_visibility'][$index] === 'private' ? 'private' : 'public';
                $filename = isset($_POST['existing_pdf_filename'][$index]) ? sanitize_file_name($_POST['existing_pdf_filename'][$index]) : basename(parse_url($url, PHP_URL_PATH));
                $final_pdf_attachments[] = array(
                    'url' => $url,
                    'visibility' => $visibility,
                    'filename' => $filename
                );
            }
        }
        $final_pdf_attachments = array_merge($final_pdf_attachments, $new_pdf_attachments);


        // --- Server-side validatie voor verplichte velden ---
        if (empty($form_data['datum'])) {
            $errors[] = 'Datum is verplicht.';
        }
        // Alleen verplicht bij 'in-optie' (NIET bij 'datum-in-optie')
        if ($form_data['order_status'] === 'in-optie' && empty($form_data['optie_geldig_tot'])) {
            $errors[] = 'Veld "Optie geldig tot" is verplicht wanneer de status op "in-optie" staat.';
        }

        if (empty($errors)) {


            $post_title = 'Maatwerk Bestelling - ' . (!empty($form_data['maatwerk_achternaam']) ? $form_data['maatwerk_achternaam'] : 'Onbekend') . ' - ' . date('Y-m-d H:i:s');
            $post_array = array(
                'post_title'    => wp_strip_all_tags($post_title),
                'post_content'  => $form_data['opmerkingen'],
                'post_status'   => 'publish',
                'post_type'     => 'maatwerk_bestelling',
            );

            if ($is_updating) {
                $post_array['ID'] = $current_post_id;
                $new_post_id = wp_update_post($post_array);
                $success_message = 'Bestelling is succesvol bijgewerkt.';
            } else {
                $new_post_id = wp_insert_post($post_array);
                $success_message = 'Nieuwe bestelling is succesvol aangemaakt.';
            }

            if (is_wp_error($new_post_id)) {
                $feedback_message = '<p class="form-error">Er is een fout opgetreden bij het opslaan van de bestelling: ' . $new_post_id->get_error_message() . '</p>';
            } else {
                // Sla alle formulierdata op als custom fields (post meta)
                update_post_meta($new_post_id, 'order_nummer', $form_data['order_nummer']);
                update_post_meta($new_post_id, 'maatwerk_voornaam', $form_data['maatwerk_voornaam']);
                update_post_meta($new_post_id, 'maatwerk_achternaam', $form_data['maatwerk_achternaam']);
                update_post_meta($new_post_id, 'maatwerk_email', $form_data['maatwerk_email']);
                update_post_meta($new_post_id, 'maatwerk_telefoonnummer', $form_data['maatwerk_telefoonnummer']);
                update_post_meta($new_post_id, 'bedrijfsnaam', $form_data['bedrijfsnaam']);
                update_post_meta($new_post_id, 'straat_huisnummer', $form_data['straat_huisnummer']);
                update_post_meta($new_post_id, 'postcode', $form_data['postcode']);
                update_post_meta($new_post_id, 'plaats', $form_data['plaats']);
                update_post_meta($new_post_id, 'referentie', $form_data['referentie']);
                update_post_meta($new_post_id, 'datum', $form_data['datum']);
                update_post_meta($new_post_id, 'start_tijd', $form_data['start_tijd']);
                update_post_meta($new_post_id, 'eind_tijd', $form_data['eind_tijd']);
                update_post_meta($new_post_id, 'aantal_medewerkers', $form_data['aantal_medewerkers']);
                update_post_meta($new_post_id, 'aantal_personen', $form_data['aantal_personen']);
                update_post_meta($new_post_id, 'order_status', $form_data['order_status']);
                update_post_meta($new_post_id, 'optie_geldig_tot', $form_data['optie_geldig_tot']);
                update_post_meta($new_post_id, 'important_opmerking', $form_data['important_opmerking']);
                update_post_meta($new_post_id, '_pdf_attachments', $final_pdf_attachments);
                update_post_meta($new_post_id, 'maatwerk_user_id', $form_data['user_id']);
                update_post_meta($new_post_id, '_employee_details', $form_data['employee_details']); // Sla medewerker details op

                // Stuur e-mail naar admin
                $subject = ($is_updating ? 'Bijgewerkte ' : 'Nieuwe ') . 'Maatwerk Bestelling: ' . $post_title;
                $message_body = "Beste,\n\nEr is een " . ($is_updating ? 'bijgewerkte' : 'nieuwe') . " maatwerk bestelling ingediend:\n\n";
                $message_body .= "Order ID: " . $new_post_id . "\n";
                $message_body .= "Status: " . ucfirst(str_replace('-', ' ', $form_data['order_status'])) . "\n";
                if ($form_data['order_status'] === 'in-optie' && !empty($form_data['optie_geldig_tot'])) {
                    $message_body .= "Optie Geldig Tot: " . $form_data['optie_geldig_tot'] . "\n";
                }
                $message_body .= "Intern Order Nummer: " . (!empty($form_data['order_nummer']) ? $form_data['order_nummer'] : 'N.v.t.') . "\n";
                $message_body .= "Referentie: " . (!empty($form_data['referentie']) ? $form_data['referentie'] : 'N.v.t.') . "\n";
                $message_body .= "Datum: " . $form_data['datum'] . "\n";
                $message_body .= "Tijd: " . $form_data['start_tijd'] . " - " . $form_data['eind_tijd'] . "\n";
                $message_body .= "Voornaam: " . (!empty($form_data['maatwerk_voornaam']) ? $form_data['maatwerk_voornaam'] : 'N.v.t.') . "\n";
                $message_body .= "Achternaam: " . (!empty($form_data['maatwerk_achternaam']) ? $form_data['maatwerk_achternaam'] : 'N.v.t.') . "\n";
                $message_body .= "E-mail: " . (!empty($form_data['maatwerk_email']) ? $form_data['maatwerk_email'] : 'N.v.t.') . "\n";
                $message_body .= "Telefoon: " . (!empty($form_data['maatwerk_telefoonnummer']) ? $form_data['maatwerk_telefoonnummer'] : 'N.v.t.') . "\n";
                $message_body .= "Bedrijf: " . (!empty($form_data['bedrijfsnaam']) ? $form_data['bedrijfsnaam'] : 'N.v.t.') . "\n";
                $message_body .= "Adres: " . (!empty($form_data['straat_huisnummer']) ? $form_data['straat_huisnummer'] : 'N.v.t.') . ", " . (!empty($form_data['postcode']) ? $form_data['postcode'] : 'N.v.t.') . " " . (!empty($form_data['plaats']) ? $form_data['plaats'] : 'N.v.t.') . "\n";
                $message_body .= "Aantal Personen: " . (!empty($form_data['aantal_personen']) ? $form_data['aantal_personen'] : 'N.v.t.') . "\n";
                $message_body .= "Aantal Medewerkers: " . (!empty($form_data['aantal_medewerkers']) ? $form_data['aantal_medewerkers'] : 'N.v.t.') . "\n";
                if (!empty($form_data['user_id'])) {
                    $message_body .= "Gekoppelde gebruiker ID: " . $form_data['user_id'] . "\n";
                }

                if (!empty($form_data['employee_details'])) {
                    $message_body .= "Medewerker details:\n";
                    foreach ($form_data['employee_details'] as $idx => $emp) {
                        $message_body .= "- " . (!empty($emp['name']) ? $emp['name'] : 'Geen naam') . ": " . (!empty($emp['start']) ? $emp['start'] : '-') . " - " . (!empty($emp['end']) ? $emp['end'] : '-') . "\n";
                    }
                }

                if (!empty($form_data['opmerkingen'])) {
                    $message_body .= "Opmerkingen Klant: " . $form_data['opmerkingen'] . "\n";
                }
                if (!empty($final_pdf_attachments)) {
                    $message_body .= "Bijgevoegde PDF(s):\n";
                    foreach ($final_pdf_attachments as $attachment) {
                        $visibility_text = ($attachment['visibility'] === 'public') ? 'Alle Werknemers' : 'Interne medewerkers';
                        $message_body .= "- " . $attachment['filename'] . " (" . $visibility_text . "): " . $attachment['url'] . "\n";
                    }
                }
                $message_body .= "\nBekijk de bestelling in de admin: " . admin_url('post.php?post=' . $new_post_id . '&action=edit') . "\n";
                $message_body .= "\nMet vriendelijke groet,\nUw website";

                // LET OP: $to moet ergens gedefinieerd zijn; voeg hier een fallback toe
                $to = get_option('admin_email');
                wp_mail($to, $subject, $message_body, array('Content-Type: text/plain; charset=UTF-8'));

                $feedback_message = '<div class="form-success">Bestelling succesvol opgeslagen.</div>';
                $should_auto_close = true;
            }

        } else {
            $feedback_message = '<div class="form-error"><strong>Fouten gevonden:</strong><ul>';
            foreach ($errors as $error) {
                $feedback_message .= '<li>' . esc_html($error) . '</li>';
            }
            $feedback_message .= '</ul></div>';
        }
    } // Einde van de POST-verwerkingslogica
    $existing_employees_json = wp_json_encode($form_data['employee_details']);
    $wrapper_data_attributes = [
        'data-auto-close' => $should_auto_close ? 'true' : 'false',
        'data-existing-employees' => $existing_employees_json,
    ];

    if ($edit_post_id) {
        $wrapper_data_attributes['data-edit-id'] = (string) $edit_post_id;
    }

    $wrapper_attr_string = '';
    foreach ($wrapper_data_attributes as $attr_key => $attr_value) {
        if ($attr_value === null || $attr_value === '') {
            continue;
        }

        $wrapper_attr_string .= sprintf(' %s="%s"', $attr_key, esc_attr($attr_value));
    }
    ?>



    <div class="maatwerk-bestelformulier-wrapper"<?php echo $wrapper_attr_string; ?>>
        <?php echo $feedback_message; ?>
        <?php echo $copied_message; ?>

        <form method="POST" action="" enctype="multipart/form-data">
            <?php if ($edit_post_id && !isset($_GET['maatwerk_copy'])) : ?>
                <input type="hidden" name="edit_post_id" value="<?php echo esc_attr($edit_post_id); ?>">
            <?php endif; ?>
            <?php wp_nonce_field('maatwerk_form_nonce', '_wpnonce_maatwerk_form'); ?>
            <h3 class="form-section-title">Order Gegevens</h3>
            <div class="form-group">
                <div class="form-field">
                    <label for="order_status">Status</label>
                    <select id="order_status" name="order_status">
                        <option value="geaccepteerd" <?php selected($form_data['order_status'], 'geaccepteerd'); ?>>Geaccepteerd</option>
                        <option value="afgewezen" <?php selected($form_data['order_status'], 'afgewezen'); ?>>Afgewezen</option>
                        <option value="in-optie" <?php selected($form_data['order_status'], 'in-optie'); ?>>In Optie</option>
                        <option value="datum-in-optie" <?php selected($form_data['order_status'], 'datum-in-optie'); ?>>Datum in optie</option>
                    </select>
                </div>
                <?php
                $optie_field_classes = ['form-field', 'maatwerk-optie-geldig-field'];
                if (in_array($form_data['order_status'], ['in-optie', 'datum-in-optie'], true)) {
                    $optie_field_classes[] = 'is-visible';
                }

                $optie_star_classes = ['required-field'];
                if ($form_data['order_status'] !== 'in-optie') {
                    $optie_star_classes[] = 'is-hidden';
                }
                ?>
                <div class="<?php echo esc_attr(implode(' ', $optie_field_classes)); ?>" id="optie_geldig_tot_field">
                    <label for="optie_geldig_tot">
                        Optie Geldig Tot <span id="optie_req_star" class="<?php echo esc_attr(implode(' ', $optie_star_classes)); ?>">*</span>
                    </label>
                    <input type="date" id="optie_geldig_tot" name="optie_geldig_tot" value="<?php echo esc_attr($form_data['optie_geldig_tot']); ?>">
                </div>
            </div>

            <div class="form-group">
                <div class="form-field">
                    <label for="order_nummer">Intern Order Nummer</label>
                    <input type="text" id="order_nummer" name="order_nummer" placeholder="Intern Order nummer" value="<?php echo esc_attr($form_data['order_nummer']); ?>">
                </div>
                <div class="form-field">
                    <label for="referentie">Referentie</label>
                    <input type="text" id="referentie" name="referentie" placeholder="Referentie" value="<?php echo esc_attr($form_data['referentie']); ?>">
                </div>
            </div>

            <div class="form-group three-cols">
                <div class="form-field">
                    <label for="datum">Datum <span class="required-field">*</span></label>
                    <input type="date" id="datum" name="datum" placeholder="Datum *" value="<?php echo esc_attr($form_data['datum']); ?>" required>
                </div>
                <div class="form-field">
                    <label for="start_tijd">Start tijd <span class="required-field">*</span></label>
                    <input type="time" id="start_tijd" name="start_tijd" placeholder="Start tijd *" value="<?php echo esc_attr($form_data['start_tijd']); ?>" >
                </div>
                <div class="form-field">
                    <label for="eind_tijd">Eind tijd <span class="required-field">*</span></label>
                    <input type="time" id="eind_tijd" name="eind_tijd" placeholder="Eind tijd *" value="<?php echo esc_attr($form_data['eind_tijd']); ?>" >
                </div>
            </div>


            <h3 class="form-section-title">Klant Gegevens</h3>
            <div class="form-group full-width user-search-container">
                <div class="form-field">
                    <label for="user_search">Zoek bestaande relatie</label>
                    <input type="text" id="user_search" placeholder="Zoek op naam, e-mail of ID">
                    <input type="hidden" id="user_id" name="user_id" value="<?php echo esc_attr($form_data['user_id']); ?>">
                    <ul id="user_search_results" class="user-search-results is-hidden"></ul>
                </div>
            </div>
            <div class="form-group full-width">
                <div class="form-field">
                    <label for="bedrijfsnaam">Bedrijfsnaam</label>
                    <input type="text" id="bedrijfsnaam" name="bedrijfsnaam" placeholder="Bedrijfsnaam" value="<?php echo esc_attr($form_data['bedrijfsnaam']); ?>">
                </div>
            </div>
            <div class="form-group">
                <div class="form-field">
                    <label for="maatwerk_voornaam">Voornaam</label>
                    <input type="text" id="maatwerk_voornaam" name="maatwerk_voornaam" placeholder="Voornaam" value="<?php echo esc_attr($form_data['maatwerk_voornaam']); ?>">
                </div>
                <div class="form-field">
                    <label for="maatwerk_achternaam">Achternaam</label>
                    <input type="text" id="maatwerk_achternaam" name="maatwerk_achternaam" placeholder="Achternaam" value="<?php echo esc_attr($form_data['maatwerk_achternaam']); ?>">
                </div>
            </div>
			
            <div class="form-group">
                <div class="form-field">
                    <label for="maatwerk_email">E-mail</label>
                    <input type="email" id="maatwerk_email" name="maatwerk_email" placeholder="E-mail" value="<?php echo esc_attr($form_data['maatwerk_email']); ?>">
                </div>
                <div class="form-field">
                    <label for="maatwerk_telefoonnummer">Telefoonnummer</label>
                    <input type="tel" id="maatwerk_telefoonnummer" name="maatwerk_telefoonnummer" placeholder="Telefoonnummer" value="<?php echo esc_attr($form_data['maatwerk_telefoonnummer']); ?>">
                </div>
            </div>



            <h3 class="form-section-title">Adres Gegevens</h3>
            <div class="form-group">
                <div class="form-field">
                    <label for="straat_huisnummer">Adres</label>
                    <input type="text" id="straat_huisnummer" name="straat_huisnummer" placeholder="Straat + Huisnummer, of bijv. Zaal" value="<?php echo esc_attr($form_data['straat_huisnummer']); ?>">
                </div>
                <div class="form-field">
                    <label for="postcode">Postcode</label>
                    <input type="text" id="postcode" name="postcode" placeholder="Postcode" value="<?php echo esc_attr($form_data['postcode']); ?>">
                </div>
            </div>

            <div class="form-group full-width">
                <div class="form-field">
                    <label for="plaats">Plaats</label>
                    <input type="text" id="plaats" name="plaats" placeholder="Plaats" value="<?php echo esc_attr($form_data['plaats']); ?>">
                </div>
            </div>

            <h3 class="form-section-title">Informatie</h3>
			<div class="form-group">
                <div class="form-field">
                    <label for="aantal_personen">Aantal personen</label>
                    <input type="text" id="aantal_personen" name="aantal_personen" placeholder="Aantal personen" value="<?php echo esc_attr($form_data['aantal_personen']); ?>">
                </div>
                <div class="form-field">
                    <label for="aantal_medewerkers">Aantal medewerkers</label>
                    <input type="number" min="0" id="aantal_medewerkers" name="aantal_medewerkers" placeholder="Aantal medewerkers" value="<?php echo esc_attr($form_data['aantal_medewerkers']); ?>">
                </div>
            </div>
			
			<div class="form-group full-width">
                <div class="form-field">
                    <label for="important_opmerking">Opmerking</label>
                    <input type="text" id="important_opmerking" name="important_opmerking" placeholder="Opmerking" value="<?php echo esc_attr($form_data['important_opmerking']); ?>">
                </div>
            </div>

            <div class="form-group full-width">
                <div class="form-field">
                    <label for="opmerkingen">Notities</label>
                    <textarea id="opmerkingen" name="opmerkingen" rows="5" placeholder="Notities (Intern)"><?php echo esc_textarea($form_data['opmerkingen']); ?></textarea>
                </div>
            </div>

			
            <!-- Medewerker details -->
            <h3 class="form-section-title">Medewerker Details</h3>
            <div id="employee-details-container">
                <?php
                // Bepaal hoeveel groepen tonen: op basis van aantal_medewerkers of bestaande employee_details
                $count = 0;
                if (!empty($form_data['aantal_medewerkers']) && is_numeric($form_data['aantal_medewerkers'])) {
                    $count = intval($form_data['aantal_medewerkers']);
                }
                if (!empty($form_data['employee_details']) && is_array($form_data['employee_details'])) {
                    $count = max($count, count($form_data['employee_details']));
                }
                for ($i = 0; $i < $count; $i++) {
                    $emp = isset($form_data['employee_details'][$i]) ? $form_data['employee_details'][$i] : array('name' => '', 'start' => '', 'end' => '');
                    ?>
                    <div class="employee-group" data-index="<?php echo $i; ?>">
                        <div class="field">
                            <label for="employee_name_<?php echo $i; ?>">Naam medewerker</label>
                            <input type="text" id="employee_name_<?php echo $i; ?>" name="employee_name[]" placeholder="Naam" value="<?php echo esc_attr($emp['name']); ?>">
                        </div>
                        <div class="field">
                            <label for="employee_start_<?php echo $i; ?>">Start tijd medewerker</label>
                            <input type="time" id="employee_start_<?php echo $i; ?>" name="employee_start[]" value="<?php echo esc_attr($emp['start']); ?>">
                        </div>
                        <div class="field">
                            <label for="employee_end_<?php echo $i; ?>">Eind tijd medewerker</label>
                            <input type="time" id="employee_end_<?php echo $i; ?>" name="employee_end[]" value="<?php echo esc_attr($emp['end']); ?>">
                        </div>
                    </div>
                <?php } ?>
            </div>

            <h3 class="form-section-title">Documenten</h3>
            <div id="pdf-uploads-container">
                <?php if (!empty($pdf_attachments)) : ?>
                    <?php foreach ($pdf_attachments as $index => $attachment) : ?>
                        <div class="pdf-upload-group existing-pdf-group" id="pdf-upload-group-existing-<?php echo $index; ?>">
                            <div class="form-field">
                                <label>Bestaand PDF</label>
                                <a href="<?php echo esc_url($attachment['url']); ?>" target="_blank"><?php echo esc_html($attachment['filename']); ?></a>
                                <input type="hidden" name="existing_pdf_url[]" value="<?php echo esc_url($attachment['url']); ?>">
                                <input type="hidden" name="existing_pdf_filename[]" value="<?php echo esc_attr($attachment['filename']); ?>">
                            </div>
                            <div class="visibility-field">
                                <label>Zichtbaarheid</label>
                                <select name="existing_pdf_visibility[]">
                                    <option value="public" <?php selected($attachment['visibility'], 'public'); ?>>Alle Werknemers</option>
                                    <option value="private" <?php selected($attachment['visibility'], 'private'); ?>>Interne medewerkers</option>
                                </select>
                            </div>
                            <button type="button" class="pdf-action-button remove-button" data-id="existing-<?php echo $index; ?>">
                                <span class="dashicons dashicons-no"></span>
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <div class="pdf-upload-group new-pdf-group" id="pdf-upload-group-0">
                    <div class="form-field">
                        <label for="pdf_upload_0">Upload PDF</label>
                        <input type="file" id="pdf_upload_0" name="pdf_upload[]" accept=".pdf">
                    </div>
                    <div class="visibility-field">
                        <label for="pdf_visibility_0">Zichtbaarheid</label>
                        <select name="pdf_visibility[]" id="pdf_visibility_0">
                            <option value="public">Alle Werknemers</option>
                            <option value="private">Interne medewerkers</option>
                        </select>
                    </div>
                    <button type="button" class="pdf-action-button remove-button is-hidden" data-id="0">
                        <span class="dashicons dashicons-no"></span>
                    </button>
                </div>
                <button type="button" id="add-pdf-button" class="pdf-action-button add-button">
                    +
                </button>

            </div>

            <div class="form-actions">
                <input
                    type="submit"
                    name="submit_bestelling"
                    value="<?php echo esc_attr( $submit_button_text ); ?>"
                    class="button button--primary"
                />

                <?php if ( $edit_post_id ) : ?>
                    <button
                        type="button"
                        id="delete-order-button"
                        class="button button--danger"
                    >
                        Verwijder
                    </button>

                    <a
                        href="<?php echo esc_url( add_query_arg( 'maatwerk_copy', $edit_post_id ) ); ?>"
                        class="button button--warning"
                    >
                        Kopieer
                    </a>

                <?php endif; ?>
            </div>

        </form>
    </div>

    

    <?php
    return ob_get_clean();
}

/*
|--------------------------------------------------------------------------
| AJAX Handler voor het zoeken naar WordPress gebruikers
|--------------------------------------------------------------------------
*/
// Hook voor ingelogde gebruikers
// Hook voor niet-ingelogde gebruikers (als je wilt dat iedereen kan zoeken)

//
// **PLAATS HIERONDER** je delete-handler:
function delete_maatwerk_order_ajax() {
    // Nonce controle toevoegen voor veiligheid
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'delete_maatwerk_order_nonce' ) ) {
        wp_send_json_error( 'Nonce verificatie mislukt.' );
    }

    if ( ! isset( $_POST['order_id'] ) ) {
        wp_send_json_error( 'Geen bestelling ID opgegeven.' );
    }
    $id = absint( $_POST['order_id'] );
    if ( ! current_user_can( 'delete_post', $id ) ) {
        wp_send_json_error( 'Je hebt geen rechten om deze bestelling te verwijderen.' );
    }
    $deleted = wp_delete_post( $id, true );
    if ( $deleted ) {
        wp_send_json_success();
    } else {
        wp_send_json_error( 'Verwijderen van bestelling mislukt.' );
    }
}

function maatwerk_search_users_ajax() {
    // Controleer nonce voor veiligheid
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'maatwerk_search_users_nonce')) {
        wp_send_json_error('Nonce verificatie mislukt.');
    }

    $search_term = sanitize_text_field($_POST['search_term']);
    $users_data = array();

    if (strlen($search_term) < 2) {
        wp_send_json_success($users_data);
    }

    // 1) Zoek in wp_users op login, naam, e-mail
    $name_args = array(
        'search'         => '*' . esc_attr( $search_term ) . '*',
        'search_columns' => array( 'user_login', 'user_nicename', 'user_email', 'display_name' ),
        'number'         => 10,
        'orderby'        => 'display_name',
        'order'          => 'ASC',
        'fields'         => array( 'ID' ),
    );
    $name_query = new WP_User_Query( $name_args );
    $name_ids   = wp_list_pluck( $name_query->results, 'ID' );

    // 2) Zoek in usermeta op bedrijfsnaam (jouw veld & WooCommerce veld)
    global $wpdb;
    $like  = '%' . $wpdb->esc_like( $search_term ) . '%';
    $company_ids = $wpdb->get_col( $wpdb->prepare( "
        SELECT user_id
        FROM {$wpdb->usermeta}
        WHERE (meta_key = 'bedrijfsnaam' OR meta_key = 'billing_company')
          AND meta_value LIKE %s
    ", $like ) );

    // 3) Combineer, limiet tot 10 en haal de volledige user objects op
    $all_ids = array_unique( array_merge( $name_ids, $company_ids ) );
    $all_ids = array_slice( $all_ids, 0, 10 );

    if ( empty( $all_ids ) ) {
        $users_data = array();
    } else {
        $final_users = get_users( array(
            'include' => $all_ids,
            'orderby' => 'include',
            'fields'  => array( 'ID', 'display_name', 'user_email' ),
        ) );
        $users_data = array();
        foreach ( $final_users as $user ) {
            $users_data[] = array(
                'ID'           => $user->ID,
                'display_name' => $user->display_name,
                'user_email'   => $user->user_email,
                'first_name'   => get_user_meta( $user->ID, 'first_name', true ),
                'last_name'    => get_user_meta( $user->ID, 'last_name', true ),
                'company'      => get_user_meta( $user->ID, 'bedrijfsnaam', true ) ?: get_user_meta( $user->ID, 'billing_company', true ),
                'billing_phone'=> get_user_meta( $user->ID, 'billing_phone', true ),
                'billing_address_1'=> get_user_meta( $user->ID, 'billing_address_1', true ),
                'billing_postcode' => get_user_meta( $user->ID, 'billing_postcode', true ),
                'billing_city'     => get_user_meta( $user->ID, 'billing_city', true ),
            );
        }
    }

    wp_send_json_success( $users_data );
}

// Einde van dit bestand
