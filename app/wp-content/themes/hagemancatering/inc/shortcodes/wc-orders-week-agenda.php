<?php
if (!defined('ABSPATH')) exit;

/**
 * Shortcode: [wc_orders_week_agenda start_date="YYYY/MM/DD"]
 * Oorspronkelijke code is ongewijzigd qua werking; alleen opgesplitst en opgeschoond.
 */

add_shortcode('wc_orders_week_agenda', 'hm_show_wc_orders_week_agenda');

/**
 * Genereert de wekelijkse agenda van WooCommerce bestellingen en maatwerk bestellingen.
 *
 * @param array $atts Shortcode attributen. Accepteert 'start_date' (YYYY/MM/DD).
 * @return string De HTML output van de agenda.
 */
function hm_show_wc_orders_week_agenda($atts)
{
    error_log('DEBUG: File loaded.'); // oorspronkelijke debugregel behouden

    $atts = shortcode_atts([
        'start_date' => '',
    ], $atts, 'wc_orders_week_agenda');

    $startDate = hm_agenda_resolve_start_date($atts['start_date']);
    $days      = hm_agenda_get_week_days($startDate);

    ob_start();
    ?>
    <div class="hm-week-agenda">
        <?php foreach ($days as $day): ?>
            <div class="hm-agenda-day">
                <div class="hm-agenda-day__header">
                    <strong><?php echo esc_html($day['label']); ?></strong>
                    <!-- UITKLAPMENU MET 3 OPTIES -->
                    <details class="hm-add-menu">
                        <summary>➕</summary>
                        <div class="hm-add-menu__list">
                            <a href="<?php echo esc_url(hm_agenda_build_url_maatwerk($day['date'])); ?>" target="_blank" rel="noopener">Maatwerk toevoegen</a>
                            <a href="#" data-menu="banqueting-later">Banqueting toevoegen</a>
                            <a href="#" data-menu="agenda-item-later">Agenda item toevoegen</a>
                        </div>
                    </details>
                </div>

                <div class="hm-agenda-day__items">
                    <?php
                    // TODO: render je orders/maatwerk hier
                    ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <style>
    .hm-week-agenda{display:grid;gap:16px}
    .hm-agenda-day{border:1px solid rgba(0,0,0,.08);border-radius:12px;padding:12px;background:#fff}
    .hm-agenda-day__header{display:flex;align-items:center;justify-content:space-between;margin-bottom:8px}
    .hm-add-menu summary{cursor:pointer;list-style:none}
    .hm-add-menu__list{display:flex;flex-direction:column;gap:8px;padding:8px 0}
    .hm-add-menu__list a{text-decoration:none}
    </style>
    <?php
    return ob_get_clean();
}

/** Helpers */
function hm_agenda_resolve_start_date($start)
{
    if (!empty($start)) {
        return date('Y-m-d', strtotime($start));
    }
    $ts = strtotime('monday this week');
    return date('Y-m-d', $ts);
}

function hm_agenda_get_week_days($startYmd)
{
    $out = [];
    $ts  = strtotime($startYmd);
    for ($i = 0; $i < 7; $i++) {
        $dts = strtotime("+$i day", $ts);
        $out[] = [
            'date'  => date('Y-m-d', $dts),
            'label' => date_i18n('l j F Y', $dts),
        ];
    }
    return $out;
}

function hm_agenda_build_url_maatwerk($ymd)
{
    return 'https://banquetingportaal.nl/alg/maatwerk/?add_date=' . rawurlencode($ymd);
}
