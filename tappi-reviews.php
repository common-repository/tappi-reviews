<?php
/*
 * Plugin Name: tappi reviews
 * Description: Easily embed Tappi reviews widget into your WordPress website.
 * Version: 2.1
 * Author: tappidev
 * Author URI: https://tappi.app
 * Author email: dev@tappi.app
 * License: GPL 2
 *
 * Copyright 2024 tappi
 *
 * tappi reviews is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as published by
 * the Free Software Foundation.
 *
 * tappi reviews is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with WordPress. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
 */

// Security check
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Settings section
add_action('admin_menu', 'tappi_reviews_settings_menu');
function tappi_reviews_settings_menu()
{
    add_options_page(
        __('Tappi Reviews Settings', 'tappi-reviews'),
        __('Tappi Reviews', 'tappi-reviews'),
        'manage_options',
        'tappi-reviews-settings',
        'tappi_reviews_settings_page'
    );
}

function tappi_reviews_settings_page()
{
    ?>
    <div class="wrap">
        <h2><?php __('Tappi Reviews Settings', 'tappi-reviews'); ?></h2>
        <form method="post" action="options.php">
            <?php
            settings_fields('tappi_reviews_settings_group');
            do_settings_sections('tappi-reviews-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

add_action('admin_init', 'tappi_reviews_settings_init');
function tappi_reviews_settings_init()
{
    // Register settings
    register_setting(
        'tappi_reviews_settings_group',
        'tappi_reviews_join_code',
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        )
    );
    register_setting(
        'tappi_reviews_settings_group',
        'tappi_reviews_callback_url',
        array(
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => ''
        )
    );
    register_setting(
        'tappi_reviews_settings_group',
        'tappi_reviews_custom_css',
        array(
            'type' => 'string',
            'sanitize_callback' => 'wp_strip_all_tags',
            'default' => ''
        )
    );

    // Add settings section
    add_settings_section(
        'tappi_reviews_settings_section',
        __('Tappi Reviews Settings', 'tappi-reviews'),
        '',
        'tappi-reviews-settings'
    );

    // Add settings fields
    add_settings_field(
        'tappi_reviews_join_code',
        __('Join Code *', 'tappi-reviews'),
        'tappi_reviews_join_code_field',
        'tappi-reviews-settings',
        'tappi_reviews_settings_section'
    );

    add_settings_field(
        'tappi_reviews_callback_url',
        __('Reviews Page (optional)', 'tappi-reviews'),
        'tappi_reviews_callback_url_field',
        'tappi-reviews-settings',
        'tappi_reviews_settings_section'
    );

    add_settings_field(
        'tappi_reviews_custom_css',
        __('Custom CSS (optional)', 'tappi-reviews'),
        'tappi_reviews_custom_css_field',
        'tappi-reviews-settings',
        'tappi_reviews_settings_section'
    );
}

function tappi_reviews_join_code_field()
{
    $join_code = sanitize_text_field(get_option('tappi_reviews_join_code'));
    echo '<input type="text" name="tappi_reviews_join_code" value="' . esc_attr($join_code) . '" required />';
}

function tappi_reviews_callback_url_field()
{
    $callback_url = sanitize_text_field(get_option('tappi_reviews_callback_url'));
    echo '<input type="text" name="tappi_reviews_callback_url" value="' . esc_attr($callback_url) . '" />';
    echo '<p class="description">Only required when displaying reviews submission form.<br/>
        Must be valid URL. Example: https://mydomain.com/reviews</p>';
}

function tappi_reviews_custom_css_field()
{
    $custom_css = get_option('tappi_reviews_custom_css');
    $example_css = ".tappi-label-text {
        color: red;
}";
    echo '<textarea name="tappi_reviews_custom_css" rows="10" cols="50">' . esc_textarea($custom_css) . '</textarea>';
    echo '<p class="description">Enter custom CSS to style the reviews widget.<br/>Example:</p>';
    echo '<pre><code>' . esc_html($example_css) . '</code></pre>';
}

function tappi_reviews_embed_script()
{
    $join_code = sanitize_text_field(get_option('tappi_reviews_join_code'));

    ?>
    <script>
        var tappiDisplayForm = window.tappiDisplayForm;

        (function (w, d, s, o, f, js, fjs) {
            w["tappi-review-widget"] = o;
            w[o] =
                w[o] ||
                function () {
                    (w[o].q = w[o].q || []).push(arguments);
                };
            (js = d.createElement(s)), (fjs = d.getElementsByTagName(s)[0]);
            js.id = o;
            js.src = f;
            js.async = 1;
            fjs.parentNode.insertBefore(js, fjs);
        })(window, document, "script", "tappiReviews", "https://v2-embed.tappi.app/embed.js");
        if (tappiDisplayForm) {
            tappiReviews("create_review_form", { joinCode: '<?php echo esc_js($join_code); ?>', targetId: "leave-tappi-review" });
        } else {

            tappiReviews("init", {
                joinCode: '<?php echo esc_js($join_code); ?>',
                targetId: tappiDisplayForm ? "leave-tappi-review" : "tappi-reviews",
                displayForm: tappiDisplayForm
            });
        }
    </script>
    <?php
}

add_action('wp_footer', 'tappi_reviews_embed_script', 20);

function tappi_reviews_shortcode($atts)
{
    // Extract shortcode attributes
    $atts = shortcode_atts(
        array(
            'display_form' => false
        ),
        $atts
    );

    $display_form = filter_var($atts['display_form'], FILTER_VALIDATE_BOOLEAN);

    // Output the appropriate div based on the attribute
    $output = $display_form ? '<div id="leave-tappi-review"></div>' : '<div id="tappi-reviews"></div>';

    // Set the global JavaScript variable in the footer
    add_action('wp_footer', function () use ($display_form) {
        ?>
        <script>
            window.tappiDisplayForm = <?php echo $display_form ? 'true' : 'false'; ?>;
        </script>
        <?php
    }, 10);

    return $output;
}

// Register the shortcode
add_shortcode('tappi-reviews', 'tappi_reviews_shortcode');

function tappi_reviews_custom_css()
{
    $custom_css = get_option('tappi_reviews_custom_css');
    if (!empty($custom_css)) {
        echo '<style type="text/css">' . esc_html($custom_css) . '</style>';
    }
}

add_action('wp_head', 'tappi_reviews_custom_css', 20);
?>