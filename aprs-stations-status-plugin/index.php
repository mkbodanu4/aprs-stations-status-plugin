<?php
/*
Plugin Name:    APRS Stations Status Plugin
Plugin URI:     https://github.com/mkbodanu4/aprs-stations-status-plugin
Description:    Add APRS stations status in various forms to your WordPress site with shortcodes.
Version:        1.0.1
Author:         UR5WKM
Author URI:     https://diy.manko.pro
Text Domain:    aprs-stations-status-plugin
*/

class APRS_Stations_Status_Plugin
{
    public function __construct()
    {
        add_action('init', array($this, 'init'));
    }

    public function init()
    {
        add_shortcode('aprs_stations_status_table', array($this, 'table_shortcode'));
        add_shortcode('aprs_stations_status_map', array($this, 'map_shortcode'));

        add_action('wp_ajax_assp_data', array($this, 'ajax_data'));
        add_action('wp_ajax_nopriv_assp_data', array($this, 'ajax_data'));

        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_menu', array($this, 'setting_page'));

        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        register_uninstall_hook(__FILE__, array($this, 'deactivate'));

        load_plugin_textdomain('aprs-stations-status-plugin', FALSE, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function deactivate()
    {
        delete_option('assp_frontend_url');
        delete_option('assp_api_key');

        unregister_setting('assp_options_group', 'assp_frontend_url');
        unregister_setting('assp_options_group', 'assp_api_key');
    }

    public function register_settings()
    {
        register_setting('assp_options_group', 'assp_frontend_url');
        register_setting('assp_options_group', 'assp_api_key');
    }

    public function setting_page()
    {
        add_options_page(
            __('Plugin Settings', 'aprs-stations-status-plugin'),
            __('APRS Stations Status', 'aprs-stations-status-plugin'),
            'manage_options',
            'assp-setting',
            array($this, 'html_form')
        );
    }

    public function html_form()
    {
        ?>
        <style>
            .assp_table {
                border: 1px solid #d3d3d3;
                border-collapse: collapse;
                width: 100%;
            }

            .assp_table td, .assp_table th {
                border: 1px solid #d3d3d3;
                padding: 5px;
                background-color: #fbfbfb;
            }

            .assp_shortcode {
                padding: 24px 10px;
                background-color: #fbfbfb;
                font-size: 17px;
                text-align: center
            }
        </style>
        <div class="wrap">
            <h2><?= __('APRS Stations Status Plugin', 'aprs-stations-status-plugin'); ?></h2>
            <form method="post" action="options.php">
                <?php settings_fields('assp_options_group'); ?>
                <h3><?= __('API Settings', 'aprs-stations-status-plugin'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th>
                            <label for="assp_frontend_url">
                                <?= __('URL', 'aprs-stations-status-plugin') . ":"; ?>
                            </label>
                        </th>
                        <td>
                            <input type='text' class="regular-text" id="assp_frontend_url" name="assp_frontend_url"
                                   placeholder="<?= __('E.g.', 'aprs-stations-status-plugin'); ?> https://demo.com/folder/"
                                   value="<?= get_option('assp_frontend_url'); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="assp_api_key">
                                <?= __('API Key', 'aprs-stations-status-plugin') . ":"; ?>
                            </label>
                        </th>
                        <td>
                            <input type='text' class="regular-text" id="assp_api_key" name="assp_api_key"
                                   placeholder="<?= __('E.g.', 'aprs-stations-status-plugin'); ?> xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
                                   value="<?= get_option('assp_api_key'); ?>">
                        </td>
                    </tr>
                </table>

                <h3><?= __('Table', 'aprs-stations-status-plugin') . ":"; ?></h3>
                <div>
                    <div class="assp_shortcode">
                        [<b>aprs_stations_status_table</b>
                        table_header="<i><?= __('APRS Stations Status', 'aprs-stations-status-plugin'); ?></i>"
                        active_time_limit="<i>7200</i>"
                        table_group_filter="<i>1,2,3</i>"]
                    </div>
                    <table class="assp_table">
                        <tr>
                            <th>
                                <?= __('Attribute', 'aprs-stations-status-plugin'); ?>
                            </th>
                            <th>
                                <?= __('Explanation', 'aprs-stations-status-plugin'); ?>
                            </th>
                            <th>
                                <?= __('Mandatory?', 'aprs-stations-status-plugin'); ?>
                            </th>
                            <th>
                                <?= __('Example', 'aprs-stations-status-plugin'); ?>
                            </th>
                        </tr>
                        <tr>
                            <td>
                                <i>table_header</i>
                            </td>
                            <td>
                                <?= __('Table header', 'aprs-stations-status-plugin'); ?>
                            </td>
                            <td>
                                <?= __('No', 'aprs-stations-status-plugin'); ?>
                            </td>
                            <td>
                                <?= __('APRS Stations Status', 'aprs-stations-status-plugin'); ?>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <i>active_time_limit</i>
                            </td>
                            <td>
                                <?= __('Seconds level since last activity, appropriate for active station', 'aprs-stations-status-plugin'); ?>
                            </td>
                            <td>
                                <?= __('Yes', 'aprs-stations-status-plugin'); ?>
                            </td>
                            <td>
                                7200
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <i>table_group_filter</i>
                            </td>
                            <td>
                                <?= __('Comma-separated list of groups to show', 'aprs-stations-status-plugin'); ?>
                            </td>
                            <td>
                                <?= __('Yes', 'aprs-stations-status-plugin'); ?>
                            </td>
                            <td>
                                1,2,3
                            </td>
                        </tr>
                    </table>
                </div>

                <h3><?= __('Map', 'aprs-stations-status-plugin') . ":"; ?></h3>
                <div>
                    <div class="assp_shortcode">
                        [<b>aprs_stations_status_map</b>
                        map_header="<i><?= __('APRS Stations Status', 'aprs-stations-status-plugin'); ?></i>"
                        map_group_filter="<i>1,5,6,9</i>"
                        map_height="<i>480</i>"
                        map_zoom="<i>5</i>"
                        map_center="<i>49.0139,31.2858</i>"
                        open_popup="<i>Yes</i>"
                        aprs_is_filter_overlay="<i>r/49.7/25.35/284 r/49.44/31.5/368 r/48.81/37.79/202
                            r/47/32.46/388</i>"]
                    </div>
                    <table class="assp_table">
                        <tr>
                            <th>
                                <?= __('Attribute', 'aprs-stations-status-plugin'); ?>
                            </th>
                            <th>
                                <?= __('Explanation', 'aprs-stations-status-plugin'); ?>
                            </th>
                            <th>
                                <?= __('Mandatory?', 'aprs-stations-status-plugin'); ?>
                            </th>
                            <th>
                                <?= __('Example', 'aprs-stations-status-plugin'); ?>
                            </th>
                        </tr>
                        <tr>
                            <td>
                                <i>map_header</i>
                            </td>
                            <td>
                                <?= __('Map header', 'aprs-stations-status-plugin'); ?>
                            </td>
                            <td>
                                <?= __('No', 'aprs-stations-status-plugin'); ?>
                            </td>
                            <td>
                                <?= __('APRS Stations Status', 'aprs-stations-status-plugin'); ?>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <i>map_group_filter</i>
                            </td>
                            <td>
                                <?= __('Comma-separated list of groups to show', 'aprs-stations-status-plugin'); ?>
                            </td>
                            <td>
                                <?= __('Yes', 'aprs-stations-status-plugin'); ?>
                            </td>
                            <td>
                                1,5,6,9
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <i>map_height</i>
                            </td>
                            <td>
                                <?= __('Map height (px)', 'aprs-stations-status-plugin'); ?>
                            </td>
                            <td>
                                <?= __('Yes', 'aprs-stations-status-plugin'); ?>
                            </td>
                            <td>
                                480
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <i>map_zoom</i>
                            </td>
                            <td>
                                <?= __('Map zoom', 'aprs-stations-status-plugin'); ?>
                            </td>
                            <td>
                                <?= __('Yes', 'aprs-stations-status-plugin'); ?>
                            </td>
                            <td>
                                5
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <i>map_center</i>
                            </td>
                            <td>
                                <?= __('Map center coordinates', 'aprs-stations-status-plugin'); ?>
                            </td>
                            <td>
                                <?= __('Yes', 'aprs-stations-status-plugin'); ?>
                            </td>
                            <td>
                                49.0139,31.2858
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <i>open_popup</i>
                            </td>
                            <td>
                                <?= __('Automatically open all popups when map loaded, Yes or No', 'aprs-stations-status-plugin'); ?>
                            </td>
                            <td>
                                <?= __('No', 'aprs-stations-status-plugin'); ?>
                            </td>
                            <td>
                                No
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <i>aprs_is_filter_overlay</i>
                            </td>
                            <td>
                                <?= __('Add overlay of radius filtering, used at APRS-IS data collecting', 'aprs-stations-status-plugin'); ?>
                            </td>
                            <td>
                                <?= __('No', 'aprs-stations-status-plugin'); ?>
                            </td>
                            <td>
                                r/49.7/25.35/284 r/49.44/31.5/368 r/48.81/37.79/202 r/47/32.46/388
                            </td>
                        </tr>
                    </table>
                </div>

                <?php submit_button(); ?>

        </div>
        <?php
    }

    public function table_shortcode($attributes)
    {
        $guid = substr(md5(mt_rand()), 0, 7);

        $args = shortcode_atts(array(
            'table_header' => '',
            'active_time_limit' => 7200,
            'table_group_filter' => ''
        ), $attributes);

        if (!$args['active_time_limit'] || !$args['table_group_filter']) {
            return __('Missing mandatory attributes, check shortcode', 'aprs-stations-status-plugin');
        }

        ob_start();
        ?>
        <style>
            .assp_table {
                width: 100%;
                border-collapse: collapse;
                border: 1px solid #d3d3d3;
            }

            .assp_table th,
            .assp_table td {
                border-collapse: collapse;
                border: 1px solid #d3d3d3;
            }

            .assp_text_right {
                text-align: right;
            }

            .assp_text_center {
                text-align: center;
            }

            .assp_igate {
                font-size: 10px;
            }

            .assp_info {
                cursor: help;
            }

            @media only screen and (max-width: 500px) {
                .assp_table {
                    font-size: 12px !important;
                }

                .assp_table td, th {
                    padding: 1px !important;
                }
            }
        </style>
        <div>
            <table class="assp_table">
                <thead>
                <?php if ($args['table_header']) { ?>
                    <tr>
                        <th colspan="4">
                            <?= $args['table_header']; ?>
                        </th>
                    </tr>
                <?php } ?>
                <tr>
                    <td colspan="4" class="assp_text_right" id="assp_last_update_<?= $guid; ?>">
                        <?= __('Last update', 'aprs-stations-status-plugin') . ": " . __('Loading...', 'aprs-stations-status-plugin'); ?>
                    </td>
                </tr>
                <tr>
                    <th class="assp_text_center">
                        <?= __('Call Sign', 'aprs-stations-status-plugin'); ?>
                    </th>
                    <th class="assp_text_center">
                        <?= __('Status', 'aprs-stations-status-plugin'); ?>
                    </th>
                    <th class="assp_text_center">
                        <?= __('Last Activity', 'aprs-stations-status-plugin'); ?>
                    </th>
                    <th class="assp_text_center">
                        <?= __('Action', 'aprs-stations-status-plugin'); ?>
                    </th>
                </tr>
                </thead>
                <tbody id="assp_table_<?= $guid; ?>">
                <tr>
                    <td colspan="4">
                        <?= __('Loading...', 'aprs-stations-status-plugin'); ?>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/min/moment-with-locales.min.js"
                integrity="sha256-QwcluVRoJ33LzMJ+COPYcydsAIJzcxCwsa0zA5JRGEc=" crossorigin="anonymous"></script>
        <script>
            var assp_table_group_filter_<?= $guid; ?> = JSON.parse("<?= json_encode(array_filter(array_map(function ($group) {
                return is_numeric($group) ? intval($group) : '';
            }, explode(",", $args['table_group_filter'])))); ?>");

            function assp_table_reload_data_<?= $guid; ?>() {
                var xhttp = new XMLHttpRequest();
                xhttp.open("POST", "<?= admin_url('admin-ajax.php');?>", true);
                xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded; charset=utf-8");
                xhttp.onreadystatechange = function () {
                    if (this.readyState === 4 && this.status === 200) {
                        var json = JSON.parse(this.response);

                        var tbody = '';

                        json.groups.forEach(function (group) {
                            var call_signs_from_group = json.data.filter(function (call_sign) {
                                return call_sign.group_id === group.group_id;
                            });

                            tbody += '<tr class="assp_text_center">' + '<th colspan="4">' + group.title + '</th>' + '</tr>';

                            call_signs_from_group.forEach(function (call_sign) {
                                var is_active = false,
                                    is_active_string = '-',
                                    last_activity_string = '-',
                                    type = '',
                                    action = '-',
                                    img = '';

                                if (call_sign.date_last_activity !== null) {
                                    var date_last_activity = +new Date(call_sign.date_last_activity.replace(" ", "T") + "Z"),
                                        seconds_last_heard = (+new Date() - date_last_activity) / 1000,
                                        path = call_sign.last_path.split(",").reverse(),
                                        igate = path[0],
                                        q = path[1];

                                    is_active = (<?= intval($args['active_time_limit']); ?> > seconds_last_heard)
                                    is_active_string = is_active ? "<?= __('Active', 'aprs-stations-status-plugin'); ?>" : "<?= __('Dead', 'aprs-stations-status-plugin'); ?>";
                                    last_activity_string = moment(date_last_activity).fromNow()

                                    if (call_sign.last_activity === 'position') {
                                        type = '<?= __('Position', 'aprs-stations-status-plugin') . ' ' . __('over', 'aprs-stations-status-plugin') . ' '; ?>';
                                    } else if (call_sign.last_activity === 'object') {
                                        type = "<?= __('Object', 'aprs-stations-status-plugin') . ' ' . __('over', 'aprs-stations-status-plugin') . ' '; ?>";
                                    } else if (call_sign.last_activity === 'routing') {
                                        type = '<?= __('Routing', 'aprs-stations-status-plugin') . ' ' . __('over', 'aprs-stations-status-plugin') . ' '; ?>';
                                    } else if (call_sign.last_activity === 'status') {
                                        type = '<?= __('Status', 'aprs-stations-status-plugin') . ' ' . __('over', 'aprs-stations-status-plugin') . ' '; ?>';
                                    } else if (call_sign.last_activity === 'telemetry') {
                                        type = '<?= __('Telemetry', 'aprs-stations-status-plugin') . ' ' . __('over', 'aprs-stations-status-plugin') . ' '; ?>';
                                    } else if (call_sign.last_activity === 'weather') {
                                        type = '<?= __('WX', 'aprs-stations-status-plugin') . ' ' . __('over', 'aprs-stations-status-plugin') . ' '; ?>';
                                    }

                                    if (['qAR', 'qAO', 'qAo'].includes(q)) {
                                        action = 'RF' + (igate !== call_sign.call_sign ? ' <span class="assp_igate">(IGate: ' + igate + ')</span>' : '');
                                    } else {
                                        action = "TCP-IP";
                                    }

                                    var symbol = 47, symbol_table = 1;
                                    if (call_sign.symbol_table && call_sign.symbol) {
                                        symbol_table = call_sign.symbol_table.charCodeAt(0);
                                        symbol = call_sign.symbol.charCodeAt(0);
                                    }
                                    img = '<img src="<?= plugin_dir_url(__FILE__); ?>symbols/symbol-' + symbol + '-' + symbol_table + '.svg">' + ' ';
                                }

                                tbody += '<tr class="assp_text_center">';
                                tbody += '<td>' + '<a href="https://aprs.fi/?call=' + call_sign.call_sign + '" target="_blank">' + img + call_sign.call_sign + '</a>' + '</td>';
                                tbody += '<td>' + is_active_string + '</td>';
                                tbody += '<td>' + last_activity_string + '</td>';
                                tbody += '<td class="assp_info"><span title="' + (call_sign.last_raw ? call_sign.last_raw.escapeHTML() : '') + '">' + type + action + '</span></td>';
                                tbody += '</tr>';
                            });
                        });

                        document.getElementById('assp_table_<?= $guid; ?>').innerHTML = tbody;
                        document.getElementById('assp_last_update_<?= $guid; ?>').innerHTML = "<?= __('Last update', 'aprs-stations-status-plugin'); ?>: " + moment().format('lll');
                    }
                };
                xhttp.send("action=assp_data&get=status" + (assp_table_group_filter_<?= $guid; ?> ? "&group=" + assp_table_group_filter_<?= $guid; ?>.join(',') : ""));
            }

            var __entityMap = {
                "&": "&amp;",
                "<": "&lt;",
                ">": "&gt;",
                '"': '&quot;',
                "'": '&#39;',
                "/": '&#x2F;'
            };

            String.prototype.escapeHTML = function () {
                return String(this).replace(/[&<>"'\/]/g, function (s) {
                    return __entityMap[s];
                });
            }

            document.addEventListener("DOMContentLoaded", function (event) {
                moment.locale("<?=get_locale();?>");

                assp_table_reload_data_<?= $guid; ?>();
                setInterval(assp_table_reload_data_<?= $guid; ?>, 60000);
            });
        </script>
        <?php
        $html = ob_get_clean();

        return $html;
    }

    public function map_shortcode($attributes)
    {
        $guid = substr(md5(mt_rand()), 0, 7);

        $args = shortcode_atts(array(
            'map_header' => '',
            'map_height' => 480,
            'map_group_filter' => '',
            'map_zoom' => 5,
            'map_center' => '',
            'open_popup' => 'No',
            'aprs_is_filter_overlay' => ''
        ), $attributes);

        if (!$args['map_group_filter'] || !$args['map_zoom'] || !$args['map_center'] || !$args['map_center']) {
            return __('Missing mandatory attributes, check shortcode', 'aprs-stations-status-plugin');
        }

        ob_start();
        ?>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.8.0/dist/leaflet.css"
              integrity="sha512-hoalWLoI8r4UszCkZ5kL8vayOGVae1oxXe/2A4AO6J9+580uKHDO3JdHb7NzwwzK5xr/Fs0W40kiNHxM9vyTtQ=="
              crossorigin=""/>
        <style>
            #assp_map_<?= $guid; ?> {
                height: <?= intval($args['map_height']).'px'; ?>;
            }

            .assp_text_bold {
                font-weight: bold;
            }
        </style>
        <?php if ($args['map_header']) { ?>
            <h4>
                <?= $args['map_header']; ?>
            </h4>
        <?php } ?>
        <div id="assp_map_<?= $guid; ?>"></div>
        <script>
            var assp_map_<?= $guid; ?>,
                assp_map_group_filter_<?= $guid; ?> = JSON.parse("<?= json_encode(array_filter(array_map(function ($group) {
                    return is_numeric($group) ? intval($group) : '';
                }, explode(",", $args['map_group_filter'])))); ?>"),
                assp_map_markers_<?= $guid; ?> = [];

            function assp_map_reload_data_<?= $guid; ?>() {

                var xhttp = new XMLHttpRequest();
                xhttp.open("POST", "<?= admin_url('admin-ajax.php');?>", true);
                xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded; charset=utf-8");
                xhttp.onreadystatechange = function () {
                    if (this.readyState === 4 && this.status === 200) {
                        var json = JSON.parse(this.response),
                            marker;

                        assp_map_markers_<?= $guid; ?>.forEach(function (marker) {
                            assp_map_<?= $guid; ?>.removeLayer(marker);
                        });
                        assp_map_markers_<?= $guid; ?> = [];

                        json.data.forEach(function (call_sign) {
                            if (call_sign.latitude && call_sign.longitude) {
                                var symbol = 47, symbol_table = 1;
                                if (call_sign.symbol_table && call_sign.symbol) {
                                    symbol_table = call_sign.symbol_table.charCodeAt(0);
                                    symbol = call_sign.symbol.charCodeAt(0);
                                }
                                var img_url = '<?= plugin_dir_url(__FILE__); ?>symbols/symbol-' + symbol + '-' + symbol_table + '.svg';

                                marker = L.marker([call_sign.latitude, call_sign.longitude], {
                                    title: call_sign.call_sign,
                                    icon: L.icon({
                                        iconUrl: img_url,
                                    })
                                }).addTo(assp_map_<?= $guid; ?>);
                                marker.bindPopup('<div class="assp_text_bold">' + call_sign.call_sign + '</div>' +
                                    '<div>' + call_sign.group_title + '</div>')<?= strtolower($args['open_popup']) === "yes" ? ".openPopup()" : ""; ?>;
                                assp_map_markers_<?= $guid; ?>.push(marker);
                            }
                        });
                    }
                };
                xhttp.send("action=assp_data&get=status" + (assp_map_group_filter_<?= $guid; ?> ? "&group=" + assp_map_group_filter_<?= $guid; ?>.join(',') : ""));
            }

            document.addEventListener("DOMContentLoaded", function (event) {
                assp_map_<?= $guid; ?> = L.map('assp_map_<?= $guid; ?>').setView(JSON.parse("<?= json_encode(array_map(function ($float) {
                    return floatval($float);
                }, explode(",", $args['map_center']))); ?>"), <?= $args['map_zoom'] && is_numeric($args['map_zoom']) ? $args['map_zoom'] : 5; ?>);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© <a target="_blank" href="https://diy.manko.pro">UR5WKM</a> | © <a target="_blank" href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                }).addTo(assp_map_<?= $guid; ?>);

                <?php

                $aprs_is_filter_overlay = $args['aprs_is_filter_overlay'];
                if($aprs_is_filter_overlay) {
                    $circles = explode(" ", $aprs_is_filter_overlay);
                    if($circles && is_array($circles) && count($circles) > 0) {
                        foreach ($circles as $i => $circle) {
                            $circle_data = explode("/", $circle);
                            if(count($circle_data) === 4) {
                                ?>
                                var circle<?= $i; ?> = L.circle([<?= floatval($circle_data[1]); ?>, <?= floatval($circle_data[2]); ?>], {
                                    color: '#000000',
                                    stroke: false,
                                    fillColor: '#000000',
                                    fillOpacity: 0.08,
                                    radius: <?= intval($circle_data[3]) * 1000; ?>
                                }).addTo(assp_map_<?= $guid; ?>);
                                <?php
                            }
                        }
                    }
                }

                ?>

                assp_map_reload_data_<?= $guid; ?>();
                setInterval(assp_map_reload_data_<?= $guid; ?>, 60000);
            });
        </script>
        <script src="https://unpkg.com/leaflet@1.8.0/dist/leaflet.js"
                integrity="sha512-BB3hKbKWOc9Ez/TAwyWxNXeoV9c1v6FIeYiBieIWkpLjauysF18NzgR1MBNBXf8/KABdlkX68nAhlwcDFLGPCQ=="
                crossorigin=""></script>
        <?php
        $html = ob_get_clean();

        return $html;
    }

    public function ajax_data()
    {
        $frontend_url = get_option('assp_frontend_url');
        $api_url = trim($frontend_url, '/') . '/api.php';
        $api_key = get_option('assp_api_key');

        $get = filter_var($_POST['get'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $group = filter_var($_POST['group'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $params = array(
            'key' => $api_key,
            'get' => $get ?? NULL,
            'group' => $group ?? NULL,
        );
        $request_url = $api_url . '?' . http_build_query($params);

        if (!$frontend_url || !$api_key)
            wp_die();

        $handler = curl_init();
        curl_setopt($handler, CURLOPT_URL, $request_url);
        curl_setopt($handler, CURLOPT_HEADER, FALSE);
        curl_setopt($handler, CURLINFO_HEADER_OUT, FALSE);
        curl_setopt($handler, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($handler, CURLOPT_MAXREDIRS, 10);
        curl_setopt($handler, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($handler, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($handler, CURLOPT_TIMEOUT, 30);
        curl_setopt($handler, CURLOPT_USERAGENT, "WordPress at " . get_home_url());
        $result = curl_exec($handler);
        curl_close($handler);

        header("Content-type:application/json");
        echo $result;

        wp_die();
    }
}

$APRS_Stations_Status_Plugin = new APRS_Stations_Status_Plugin();