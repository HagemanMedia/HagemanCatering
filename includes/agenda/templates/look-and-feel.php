<?php

if (! defined('ABSPATH')) {
    exit;
}

function hageman_catering_render_agenda_layout($agenda_visibility_setting, $header_html, $content_html) {
    $output  = '<div class="wc-weekagenda-wrapper" data-agenda-visibility="' . esc_attr($agenda_visibility_setting) . '">';
    $output .= $header_html;
    $output .= '<div class="wc-weekagenda-google" id="wc-weekagenda-container">';
    $output .= $content_html;
    $output .= '</div>';
    $output .= '</div>';

    $output .= '<div id="wc-order-modal" class="wc-order-modal">'
            . '    <div class="wc-order-modal-content">'
            . '        <span class="wc-order-modal-close">&times;</span>'
            . '        <div id="wc-order-modal-body">'
            . '            <div style="text-align: center; padding: 20px;">Laden bestelgegevens...</div>'
            . '        </div>'
            . '    </div>'
            . '</div>';

    return $output;
}
