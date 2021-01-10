<?php

/**
 * Get the name and the version of the plugin - Needed
 */
if (!function_exists('plugin_version_redminesync')) {
    function plugin_version_redminesync() {
        return array('name'           => "Redmine Sync",
            'version'        => '1.0.0',
            'author'         => '<a href="https://isale.pro/">IsalePro</a>',
            'license'        => 'GPLv2+',
            'homepage'       => 'https://isale.pro/',
            'minGlpiVersion' => '9.2.4');
    }
}


/**
 *  Check if the config is ok - Needed
 */
if (!function_exists('plugin_redminesync_check_config')) {
    function plugin_redminesync_check_config()
    {
        return true;
    }
}
 
/**
 * Check if the prerequisites of the plugin are satisfied - Needed
 */
if (!function_exists('plugin_redminesync_check_prerequisites')) {
    function plugin_redminesync_check_prerequisites()
    {
        // Check that the GLPI version is compatible
        if (version_compare(GLPI_VERSION, '9.2.4', 'lt')) {
            echo "This plugin Requires GLPI >= 9.2.4";
            return false;
        }
        return true;
    }
}

/**
 * Init the hooks of the plugins -Needed
**/
if (!function_exists('plugin_init_redminesync')) {
    function plugin_init_redminesync()
    {
        global $PLUGIN_HOOKS;

        $PLUGIN_HOOKS['csrf_compliant']['redminesync'] = true;
        $PLUGIN_HOOKS['config_page']['redminesync'] = 'front/config.form.php';
    }
}
