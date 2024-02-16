<?php

/**
 * Load services definition file.
 */
$settings['container_yamls'][] = __DIR__ . '/services.yml';

/**
 * Include the Pantheon-specific settings file.
 *
 * n.b. The settings.pantheon.php file makes some changes
 *      that affect all envrionments that this site
 *      exists in.  Always include this file, even in
 *      a local development environment, to insure that
 *      the site settings remain consistent.
 */
include __DIR__ . "/settings.pantheon.php";

/**
 * If there is a local settings file, then include it
 */
$local_settings = __DIR__ . "/settings.local.php";
if (file_exists($local_settings)) {
  include $local_settings;
}
$settings['install_profile'] = 'standard';
$settings['hash_salt'] = 'dkKj3wEQHxmzZOSByc19cXfxg_tSqYHlNjnflas3RJc379hlgzSrU-t6vP7jqQmJqc4M1ep8Dw';
$settings['config_sync_directory'] = 'sites/default/files/config_YLZJmmpOqc_KBWbMc2I58ky3-8c7qtg4G-OpSqFClHs5E0NL9YMFgyF4RRTv8IFdl_kAMs_Bdw/sync';
$settings['system.logging']['error_level'] = 'verbose';