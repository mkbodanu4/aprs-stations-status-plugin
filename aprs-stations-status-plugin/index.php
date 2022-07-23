<?php
/*
Plugin Name:    APRS Stations Status Plugin
Plugin URI:     https://github.com/mkbodanu4/aprs-stations-status-plugin
Description:    Add APRS stations status in various forms to your WordPress site with shortcodes.
Version:        1.0
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
        add_shortcode('aprs_stations_status_table', array($this, 'shortcode'));

        add_action('wp_ajax_assp_table', array($this, 'ajax_data'));
        add_action('wp_ajax_nopriv_assp_table', array($this, 'ajax_data'));

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
        delete_option('assp_table_header');
        delete_option('assp_dead_time');

        unregister_setting('assp_options_group', 'assp_frontend_url');
        unregister_setting('assp_options_group', 'assp_api_key');
        unregister_setting('assp_options_group', 'assp_table_header');
        unregister_setting('assp_options_group', 'assp_dead_time');
    }

    public function register_settings()
    {
        register_setting('assp_options_group', 'assp_frontend_url');
        register_setting('assp_options_group', 'assp_api_key');
        register_setting('assp_options_group', 'assp_table_header');
        register_setting('assp_options_group', 'assp_dead_time');
    }

    public function setting_page()
    {
        add_options_page(
            __('APRS Stations Status Plugin Settings', 'aprs-stations-status-plugin'),
            __('APRS Stations Status Plugin', 'aprs-stations-status-plugin'),
            'manage_options',
            'assp-setting',
            array($this, 'html_form')
        );
    }

    public function html_form()
    {
        ?>
        <div class="wrap">
            <h2><?= __('Plugin Settings', 'aprs-stations-status-plugin'); ?></h2>
            <form method="post" action="options.php">
                <?php settings_fields('assp_options_group'); ?>
                <h3><?= __('APRS Stations Status Monitor', 'aprs-stations-status-plugin'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th>
                            <label for="assp_frontend_url">
                                <?= __('URL to APRS Stations Status Monitor Frontend', 'aprs-stations-status-plugin') . ":"; ?>
                            </label>
                        </th>
                        <td>
                            <input type='text' class="regular-text" id="assp_frontend_url" name="assp_frontend_url"
                                   placeholder="https://demo.com/folder/"
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
                                   placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
                                   value="<?= get_option('assp_api_key'); ?>">
                        </td>
                    </tr>
                </table>

                <h3><?= __('Table', 'aprs-stations-status-plugin'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th>
                            <label for="assp_shortcode">
                                <?= __('Shortcode', 'aprs-stations-status-plugin') . ":"; ?>
                            </label>
                        </th>
                        <td>
                            <input type='text' class="regular-text" id="assp_shortcode"
                                   value="<?= "[aprs_stations_status_table]"; ?>">
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="assp_table_header">
                                <?= __('Table header', 'aprs-stations-status-plugin') . ":"; ?>
                            </label>
                        </th>
                        <td>
                            <input type='text' class="regular-text" id="assp_table_header" name="assp_table_header"
                                   placeholder="<?= __('APRS Stations Status', 'aprs-stations-status-plugin'); ?>"
                                   value="<?= get_option('assp_table_header'); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="assp_dead_time">
                                <?= __('Seconds level since last activity, appropriate for active station', 'aprs-stations-status-plugin') . ":"; ?>
                            </label>
                        </th>
                        <td>
                            <input type='text' class="regular-text" id="assp_dead_time" name="assp_dead_time"
                                   placeholder="3600"
                                   value="<?= get_option('assp_dead_time'); ?>">
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>

        </div>
        <?php
    }

    public function shortcode()
    {
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
                <?php if (get_option('assp_table_header')) { ?>
                    <tr>
                        <th colspan="4">
                            <?= get_option('assp_table_header') ?>
                        </th>
                    </tr>
                <?php } ?>
                <tr>
                    <td colspan="4" class="assp_text_right" id="assp_last_update">
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
                <tbody id="assp_table">
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
            function assp_reload_data() {
                var xhttp = new XMLHttpRequest();
                xhttp.open("POST", "<?= admin_url('admin-ajax.php');?>", true);
                xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded; charset=utf-8");
                xhttp.onreadystatechange = function () {
                    if (this.readyState === 4 && this.status === 200) {
                        var json = JSON.parse(this.response);

                        var tbody = '';

                        json.regions.forEach(function (region) {
                            var call_signs_from_region = json.data.filter(function (call_sign) {
                                return call_sign.region_id === region.region_id;
                            });

                            tbody += '<tr class="assp_text_center">' + '<th colspan="4">' + region.title + '</th>' + '</tr>';

                            call_signs_from_region.forEach(function (call_sign) {
                                var is_active = false, img = '', action = '', last_activity_string = '-', source = '-';

                                if (call_sign.date_last_activity !== null) {
                                    var date_last_activity = +new Date(call_sign.date_last_activity.replace(" ", "T") + "Z"),
                                        seconds_last_heard = (+new Date() - date_last_activity) / 1000,
                                        path = call_sign.last_path.split(",").reverse(),
                                        igate = path[0],
                                        q = path[1];

                                    is_active = (<?= intval(get_option('assp_dead_time')); ?> > seconds_last_heard)
                                    last_activity_string = moment(date_last_activity).fromNow()

                                    if (['qAR', 'qAO', 'qAo'].includes(q)) {
                                        source = 'RF' + (igate !== call_sign.call_sign ? ' <span class="assp_igate">(IGate: ' + igate + ')</span>' : '');
                                    } else {
                                        source = "TCP-IP";
                                    }

                                    var symbol = 47, symbol_table = 1;
                                    if (call_sign.symbol_table && call_sign.symbol) {
                                        symbol_table = call_sign.symbol_table.charCodeAt(0);
                                        symbol = call_sign.symbol.charCodeAt(0);
                                    }
                                    img = '<img src="<?= plugin_dir_url(__FILE__); ?>symbols/symbol-' + symbol + '-' + symbol_table + '.svg">' + ' ';

                                    if (call_sign.last_activity === 'position') {
                                        action = '<?= __('Position', 'aprs-stations-status-plugin') . ' ' . __('over', 'aprs-stations-status-plugin') . ' '; ?>';
                                    } else if (call_sign.last_activity === 'object') {
                                        action = "<?= __('Object', 'aprs-stations-status-plugin') . ' ' . __('over', 'aprs-stations-status-plugin') . ' '; ?>";
                                    } else if (call_sign.last_activity === 'routing') {
                                        action = '<?= __('Routing', 'aprs-stations-status-plugin') . ' ' . __('over', 'aprs-stations-status-plugin') . ' '; ?>';
                                    } else if (call_sign.last_activity === 'status') {
                                        action = '<?= __('Status', 'aprs-stations-status-plugin') . ' ' . __('over', 'aprs-stations-status-plugin') . ' '; ?>';
                                    } else if (call_sign.last_activity === 'telemetry') {
                                        action = '<?= __('Telemetry', 'aprs-stations-status-plugin') . ' ' . __('over', 'aprs-stations-status-plugin') . ' '; ?>';
                                    } else if (call_sign.last_activity === 'weather') {
                                        action = '<?= __('WX', 'aprs-stations-status-plugin') . ' ' . __('over', 'aprs-stations-status-plugin') . ' '; ?>';
                                    }
                                }

                                tbody += '<tr class="assp_text_center">';
                                tbody += '<td>' + '<a href="https://aprs.fi/?call=' + call_sign.call_sign + '" target="_blank">' + img + call_sign.call_sign + '</a>' + '</td>';
                                tbody += '<td>' + (call_sign.date_last_activity == null ? '-' : (is_active ? "<?= __('Active', 'aprs-stations-status-plugin'); ?>" : "<?= __('Dead', 'aprs-stations-status-plugin'); ?>")) + '</td>';
                                tbody += '<td>' + last_activity_string + '</td>';
                                tbody += '<td class="assp_info"><span title="' + call_sign.last_raw + '">' + action + source + '</span></td>';
                                tbody += '</tr>';
                            });
                        });

                        document.getElementById('assp_table').innerHTML = tbody;
                        document.getElementById('assp_last_update').innerHTML = "<?= __('Last update', 'aprs-stations-status-plugin'); ?>: " + moment().format('lll');
                    }
                };
                xhttp.send("action=assp_table");
            }

            document.addEventListener("DOMContentLoaded", function (event) {
                assp_reload_data();

                moment.locale("<?=get_locale();?>");

                setInterval(assp_reload_data, 60000);
            });
        </script>
        <?php
        $html = ob_get_clean();

        return $html;
    }

    public function ajax_data()
    {
        $frontend_url = get_option('assp_frontend_url');
        $api_key = get_option('assp_api_key');

        if (!$frontend_url || !$api_key)
            wp_die();

        $handler = curl_init();
        curl_setopt($handler, CURLOPT_URL, trim($frontend_url, '/') . '/api.php?' . http_build_query(array(
                'key' => $api_key
            )));
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