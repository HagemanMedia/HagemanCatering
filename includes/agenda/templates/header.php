<?php

if (! defined('ABSPATH')) {
    exit;
}

function hageman_catering_render_agenda_header($user_roles, $week_start_timestamp) {
    ob_start();
    ?>
    <div class="wc-agenda-navigation" style="display:flex;align-items:center;gap:8px;flex-wrap:nowrap;">
        <div class="wc-nav-elements-left" style="display:flex;align-items:center;gap:6px;">
            <?php if (in_array('administrator', $user_roles, true)) : ?>
                <a href="https://banquetingportaal.nl/alg/wp-admin/" target="_blank" class="wc-nav-button wc-back-portal-button" style="display:flex;align-items:center;justify-content:center;height:32px;padding:0 10px;background-color:#2271b1;color:#fff;font-weight:bold;border-radius:4px;text-decoration:none;line-height:32px;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" style="width:14px;height:14px;margin-right:6px;fill:currentColor;vertical-align:middle;">
                        <path d="M491 100.8C478.1 93.8 462.3 94.5 450 102.6L192 272.1L192 128C192 110.3 177.7 96 160 96C142.3 96 128 110.3 128 128L128 512C128 529.7 142.3 544 160 544C177.7 544 192 529.7 192 512L192 367.9L450 537.5C462.3 545.6 478 546.3 491 539.3C504 532.3 512 518.8 512 504.1L512 136.1C512 121.4 503.9 107.9 491 100.9z" />
                    </svg>
                    Terug naar het portaal
                </a>
            <?php endif; ?>

            <?php if (in_array('administrator', $user_roles, true)) : ?>
                <button class="wc-nav-button wc-new-maatwerk-button icon-only">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M96 32l0 32L48 64C21.5 64 0 85.5 0 112l0 48 448 0 0-48c0-26.5-21.5-48-48-48l-48 0 0-32c0-17.7-14.3-32-32-32s-32 14.3-32 32l0 32L160 64l0-32c0-17.7-14.3-32-32-32S96 14.3 96 32zM448 192L0 192 0 464c0 26.5 21.5 48 48 48l352 0c26.5 0 48-21.5 48-48l0-272zM224 248c13.3 0 24 10.7 24 24l0 56 56 0c13.3 0 24 10.7 24 24s-10.7 24-24 24l-56 0 0 56c0 13.3-10.7 24-24 24s-24-10.7-24-24l0-56-56 0c-13.3 0-24-10.7-24-24s10.7-24 24-24l56 0 0-56c0-13.3 10.7-24 24-24z" /></svg>
                </button>

                <button class="wc-nav-button wc-add-customer-button icon-only">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path fill="currentColor" d="M96 128a128 128 0 1 1 256 0A128 128 0 1 1 96 128zM0 482.3C0 383.8 79.8 304 178.3 304l91.4 0C368.2 304 448 383.8 448 482.3c0 16.4-13.3 29.7-29.7 29.7L29.7 512C13.3 512 0 498.7 0 482.3zM504 312l0-64-64 0c-13.3 0-24-10.7-24-24s10.7-24 24-24l64 0 0-64c0-13.3 10.7-24 24-24s24 10.7 24 24l0 64 64 0c13.3 0 24 10.7 24 24s-10.7 24-24 24l-64 0 0 64c0 13.3-10.7 24-24 24s-24-10.7-24-24z" /></svg>
                </button>
            <?php endif; ?>

            <button class="wc-nav-button wc-turflijst-button icon-only">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M152.1 38.2c9.9 8.9 10.7 24 1.8 33.9l-72 80c-4.4 4.9-10.6 7.8-17.2 7.9s-12.9-2.4-17.6-7L7 113C-2.3 103.6-2.3 88.4 7 79s24.6-9.4 33.9 0l22.1 22.1 55.1-61.2c8.9-9.9 24-10.7 33.9-1.8zm0 160c9.9 8.9 10.7 24 1.8 33.9l-72 80c-4.4 4.9-10.6 7.8-17.2 7.9s-12.9-2.4-17.6-7L7 273c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l22.1 22.1 55.1-61.2c8.9-9.9 24-10.7 33.9-1.8zM224 96c0-17.7 14.3-32 32-32l224 0c17.7 0 32 14.3 32 32s-14.3 32-32 32l-224 0c-17.7 0-32-14.3-32-32zm0 160c0-17.7 14.3-32 32-32l224 0c17.7 0 32 14.3 32 32s-14.3 32-32 32l-224 0c-17.7 0-32-14.3-32-32zM160 416c0-17.7 14.3-32 32-32l288 0c17.7 0 32 14.3 32 32s-14.3 32-32 32l-288 0c-17.7 0-32-14.3-32-32zM48 368a48 48 0 1 1 0 96 48 48 0 1 1 0-96z" /></svg>
            </button>
        </div>

        <div style="flex:1;display:flex;justify-content:center;align-items:center;">
            <input type="text" id="wc-search-input" placeholder="zoekbalk werkt nog niet!" aria-label="Zoeken" style="width:300px;max-width:100%;padding:6px 8px;border:1px solid #ccc;border-radius:4px;" />
        </div>

        <div class="wc-nav-elements-right" style="display:flex;align-items:center;gap:6px;">
            <button id="wc-hide-cancelled" class="wc-nav-button" type="button" title="Verberg of toon geannuleerde orders" aria-pressed="false">Verberg geannuleerd</button>

            <button class="wc-nav-button wc-refresh-button" aria-label="Verversen" title="Verversen" type="button" onclick="location.reload();" style="display:flex;align-items:center;justify-content:center;padding:0 10px;height:32px;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" role="img" aria-hidden="true" style="width:1em;height:1em;display:block;flex-shrink:0;" fill="currentColor">
                    <path d="M552 256L408 256C398.3 256 389.5 250.2 385.8 241.2C382.1 232.2 384.1 221.9 391 215L437.7 168.3C362.4 109.7 253.4 115 184.2 184.2C109.2 259.2 109.2 380.7 184.2 455.7C259.2 530.7 380.7 530.7 455.7 455.7C463.9 447.5 471.2 438.8 477.6 429.6C487.7 415.1 507.7 411.6 522.2 421.7C536.7 431.8 540.2 451.8 530.1 466.3C521.6 478.5 511.9 490.1 501 501C401 601 238.9 601 139 501C39.1 401 39 239 139 139C233.3 44.7 382.7 39.4 483.3 122.8L535 71C541.9 64.1 552.2 62.1 561.2 65.8C570.2 69.5 576 78.3 576 88L576 232C576 245.3 565.3 256 552 256z" />
                </svg>
            </button>

            <div style="display:flex;gap:5px;align-items:center;">
                <button class="wc-nav-button wc-today-button">Deze week</button>
                <button class="wc-nav-button wc-arrow-button" data-direction="-1">&lt;</button>
                <input type="date" id="wc-week-date-picker" value="<?php echo esc_attr(date('Y-m-d', $week_start_timestamp)); ?>" aria-label="Kies week" autocomplete="off" />
                <button class="wc-nav-button wc-arrow-button" data-direction="1">&gt;</button>
            </div>
        </div>
    </div>
    <?php
    return trim(ob_get_clean());
}
