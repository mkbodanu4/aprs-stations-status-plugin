<?php
/*
Plugin Name:    Simple IGate Status Plugin
Plugin URI:     https://github.com/mkbodanu4/simple-igate-status-plugin
Description:    Add IGate/Digipeaters status on ARPS network to your WordPress site with shortcode [igate_status_table]
Version:        1.0
Author:         UR5WKM
Author URI:     https://diy.manko.pro
Text Domain:    simple-igate-status-plugin
*/

class Simple_IGate_Status_Plugin
{
    public function __construct()
    {
        add_action('init', array($this, 'init'));
    }

    public function init()
    {
        add_shortcode('igate_status_table', array($this, 'shortcode'));

        add_action('wp_ajax_sigsp_table', array($this, 'ajax_data'));
        add_action('wp_ajax_nopriv_sigsp_table', array($this, 'ajax_data'));

        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_menu', array($this, 'setting_page'));

        load_plugin_textdomain('simple-igate-status-plugin', FALSE, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function register_settings()
    {
        register_setting('sigsp_options_group', 'sigsp_table_header');
        register_setting('sigsp_options_group', 'sigsp_dead_time');
        register_setting('sigsp_options_group', 'sigsp_api_url');
    }

    public function setting_page()
    {
        add_options_page(
            __('Simple IGate Status Plugin Settings', 'simple-igate-status-plugin'),
            __('Simple IGate Status Plugin', 'simple-igate-status-plugin'),
            'manage_options',
            'sigsp-setting',
            array($this, 'html_form')
        );
    }

    public function html_form()
    {
        ?>
        <div class="wrap">
            <h2><?= __('Plugin Settings', 'simple-igate-status-plugin'); ?></h2>
            <form method="post" action="options.php">
                <?php settings_fields('sigsp_options_group'); ?>
                <table class="form-table">
                    <tr>
                        <th>
                            <label for="sigsp_api_url">
                                <?= __('URL to api.php file of Simple IGate Status Monitor with API key:', 'simple-igate-status-plugin'); ?>
                            </label>
                        </th>
                        <td>
                            <input type='text' class="regular-text" id="sigsp_api_url" name="sigsp_api_url"
                                   placeholder="https://demo.com/igate-status/api.php?key=123456789"
                                   value="<?php echo get_option('sigsp_api_url'); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="sigsp_table_header">
                                <?= __('Table header:', 'simple-igate-status-plugin'); ?>
                            </label>
                        </th>
                        <td>
                            <input type='text' class="regular-text" id="sigsp_table_header" name="sigsp_table_header"
                                   placeholder="APRS DIGI and I-GATE Status"
                                   value="<?php echo get_option('sigsp_table_header'); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="sigsp_dead_time">
                                <?= __('Seconds level since last activity, appropriate for active station:', 'simple-igate-status-plugin'); ?>
                            </label>
                        </th>
                        <td>
                            <input type='text' class="regular-text" id="sigsp_dead_time" name="sigsp_dead_time"
                                   placeholder="3600"
                                   value="<?php echo get_option('sigsp_dead_time'); ?>">
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
            .sigsp_table {
                width: 100%;
                border-collapse: collapse;
                border: 1px solid #d3d3d3;
            }

            .sigsp_table th,
            .sigsp_table td {
                border-collapse: collapse;
                border: 1px solid #d3d3d3;
            }

            .sigsp_text_right {
                text-align: right;
            }

            .sigsp_text_center {
                text-align: center;
            }

            .sigsp_igate {
                font-size: 10px;
            }

            .sigsp_info {
                cursor: help;
            }

            @media only screen and (max-width: 500px) {
                .sigsp_table {
                    font-size: 12px !important;
                }

                .sigsp_table td, th {
                    padding: 1px !important;
                }
            }
        </style>
        <div>
            <table class="sigsp_table">
                <thead>
                <?php if (get_option('sigsp_table_header')) { ?>
                    <tr>
                        <th colspan="4">
                            <?= get_option('sigsp_table_header') ?>
                        </th>
                    </tr>
                <?php } ?>
                <tr>
                    <td colspan="4" class="sigsp_text_right" id="sigsp_last_update">
                        <?= __('Last update: Loading...', 'simple-igate-status-plugin'); ?>
                    </td>
                </tr>
                <tr>
                    <th class="sigsp_text_center">
                        <?= __('Digi/IGate Call Sign', 'simple-igate-status-plugin'); ?>
                    </th>
                    <th class="sigsp_text_center">
                        <?= __('Status', 'simple-igate-status-plugin'); ?>
                    </th>
                    <th class="sigsp_text_center">
                        <?= __('Last Activity', 'simple-igate-status-plugin'); ?>
                    </th>
                    <th class="sigsp_text_center">
                        <?= __('Source', 'simple-igate-status-plugin'); ?>
                    </th>
                </tr>
                </thead>
                <tbody id="sigsp_table">
                <tr>
                    <td colspan="4">
                        <?= __('Loading...', 'simple-igate-status-plugin'); ?>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/min/moment-with-locales.min.js"
                integrity="sha256-QwcluVRoJ33LzMJ+COPYcydsAIJzcxCwsa0zA5JRGEc=" crossorigin="anonymous"></script>
        <script>
            function sigsp_reload_data() {
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

                            tbody += '<tr class="sigsp_text_center">' + '<th colspan="4">' + region.title + '</th>' + '</tr>';

                            call_signs_from_region.forEach(function (call_sign) {
                                var date_updated = +new Date(call_sign.date.replace(" ", "T") + "Z"),
                                    seconds_last_heard = (+new Date() - date_updated) / 1000,
                                    is_active = (<?= intval(get_option('sigsp_dead_time')); ?> > seconds_last_heard),
                                    data_source = null,
                                    action = '',
                                    img = '';

                                if (call_sign.beacon_date && call_sign.activity_date) {
                                    if (+new Date(call_sign.beacon_date.replace(" ", "T") + "Z") > +new Date(call_sign.activity_date.replace(" ", "T") + "Z")) {
                                        data_source = 'beacon';
                                        action = '<?= __('Beacon over', 'simple-igate-status-plugin'); ?> '
                                    } else {
                                        data_source = 'activity'
                                    }
                                } else if (call_sign.beacon_date && !call_sign.activity_date) {
                                    data_source = 'beacon'
                                    action = '<?= __('Beacon over', 'simple-igate-status-plugin'); ?> '
                                } else if (!call_sign.beacon_date && call_sign.activity_date) {
                                    data_source = 'activity'
                                }

                                var path = data_source ? call_sign[data_source + '_path'].split(",").reverse() : "N/A",
                                    igate = path[0],
                                    q = path[1],
                                    source = "TCP-IP",
                                    from = data_source ? call_sign[data_source + '_from'] : "N/A";

                                if (['qAR', 'qAO', 'qAo'].includes(q)) {
                                    source = 'RF' + (igate !== call_sign.call_sign ? ' <span class="sigsp_igate">(IGate: ' + igate + ')</span>' : '');
                                }

                                var symbol = 47, symbol_table = 1;
                                if (call_sign.beacon_symbol && call_sign.beacon_symbol_table) {
                                    symbol_table = call_sign.beacon_symbol_table.charCodeAt(0);
                                    symbol = call_sign.beacon_symbol.charCodeAt(0);

                                    if (symbol_table === 47) {
                                        symbol_table = 1
                                    } else if (symbol_table === 92) {
                                        symbol_table = 1
                                    } else {
                                        symbol = 47;
                                        symbol_table = 1;
                                    }
                                }
                                img = '<img src="<?= plugin_dir_url(__FILE__); ?>icons/' + symbol + '-' + symbol_table + '.png">' + ' ';

                                tbody += '<tr class="sigsp_text_center">';
                                tbody += '<td>' + '<a href="https://aprs.fi/?call=' + call_sign.call_sign + '" target="_blank">' + img + call_sign.call_sign + '</a>' + '</td>';
                                tbody += '<td>' + (is_active ? "<?= __('Active', 'simple-igate-status-plugin'); ?>" : "<?= __('Dead', 'simple-igate-status-plugin'); ?>") + '</td>';
                                tbody += '<td>' + moment(date_updated).fromNow() + '</td>';
                                tbody += '<td class="sigsp_info"><span title="' + from + '>' + path.reverse().join(',') + '">' + action + source + '</span></td>';
                                tbody += '</tr>';
                            });
                        });

                        document.getElementById('sigsp_table').innerHTML = tbody;
                        document.getElementById('sigsp_last_update').innerHTML = "<?= __('Last update:', 'simple-igate-status-plugin'); ?> " + moment().format('lll');
                    }
                };
                xhttp.send("action=sigsp_table");
            }

            document.addEventListener("DOMContentLoaded", function (event) {
                sigsp_reload_data();

                moment.locale("<?=get_locale();?>");

                setInterval(sigsp_reload_data, 60000);
            });
        </script>
        <?php
        $html = ob_get_clean();

        return $html;
    }

    public function ajax_data()
    {
        $api_url = get_option('sigsp_api_url');

        if (!$api_url)
            wp_die();

        $handler = curl_init();
        curl_setopt($handler, CURLOPT_URL, $api_url);
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

$Simple_IGate_Status_Plugin = new Simple_IGate_Status_Plugin();