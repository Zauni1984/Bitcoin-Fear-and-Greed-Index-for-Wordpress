<?php
/*
Plugin Name: Bitcoin Fear and Greed Index
Plugin URI: https://github.com/Zauni1984/Bitcoin-Fear-and-Greed-Index-for-Wordpress/
Description: Zeigt den aktuellen Bitcoin Fear and Greed Index als Balkenvisualisierung an, optimiert für hellen und dunklen Modus.
Version: 1.8
Author: BlockSocial UG (haftungsbeschränkt)
Author URI: https://blocksocial.eu
License: GPL2
*/

// Funktion, um den Fear and Greed Index abzufragen (Caching integriert)
function fetch_fear_and_greed_index_cached() {
    $cache_key = 'fear_greed_index';
    $cached_data = get_transient($cache_key);

    // Wenn ein gecachter Wert existiert, diesen verwenden
    if ($cached_data !== false) {
        return $cached_data;
    }

    // API-Daten abrufen
    $api_url = "https://api.alternative.me/fng/?limit=1&format=json";
    $response = wp_remote_get($api_url);

    // Wenn die API nicht erreichbar ist, Abbruch
    if (is_wp_error($response)) {
        return false;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    // Wenn die Daten fehlerhaft sind, Abbruch
    if (!isset($data['data'][0])) {
        return false;
    }

    // Speichern der Daten im Cache (für 1 Stunde)
    set_transient($cache_key, $data['data'][0], HOUR_IN_SECONDS);

    return $data['data'][0];
}

// Shortcode für den Fear and Greed Index
function render_fear_and_greed_index() {
    $data = fetch_fear_and_greed_index_cached();

    if (!$data) {
        return "Daten konnten nicht abgerufen werden.";
    }

    $value = $data['value'];
    $classification = $data['value_classification'];
    $timestamp = date("d.m.Y H:i:s", $data['timestamp']);

    // HTML und CSS für den Balken
    return "
        <div class='fear-greed-index'>
            <h3>Bitcoin Fear and Greed Index</h3>
            <div class='fear-greed-bar'>
                <div class='bar-background'>
                    <div class='bar-fill' style='width: $value%;'></div>
                    <div class='bar-indicator' style='left: $value%;'></div>
                </div>
                <div class='bar-labels'>
                    <span>0 (Fear)</span>
                    <span>100 (Greed)</span>
                </div>
            </div>
            <div style='font-size: 16px; margin-top: 10px;'>$classification ($value)</div>
            <small>Letztes Update: $timestamp</small>
        </div>
        <style>
            .fear-greed-bar {
                margin: 20px auto;
                width: 100%;
                max-width: 400px;
            }
            .bar-background {
                position: relative;
                height: 20px;
                background: linear-gradient(to right, green, yellow, red);
                border-radius: 10px;
                overflow: hidden;
            }
            .bar-fill {
                position: absolute;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.1); /* leicht transparente Markierung */
            }
            .bar-indicator {
                position: absolute;
                top: 0;
                height: 100%;
                width: 5px;
                background-color: black;
            }
            .bar-labels {
                display: flex;
                justify-content: space-between;
                font-size: 12px; /* Kleinere Beschriftung */
                margin-top: 5px;
            }
            .fear-greed-index {
                text-align: center;
                padding: 20px;
                border: 1px solid transparent; /* Keine feste Farbe */
                border-radius: 10px;
                background-color: transparent; /* Transparenter Hintergrund */
                max-width: 400px;
                margin: 20px auto;
                color: var(--wp--preset--color--text, #333); /* Dynamischer Text für helle/dunkle Themes */
            }
        </style>
    ";
}

// Shortcode registrieren
function register_fear_and_greed_shortcode() {
    add_shortcode('fear_greed_index', 'render_fear_and_greed_index');
}
add_action('init', 'register_fear_and_greed_shortcode');
