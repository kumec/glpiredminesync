<?php
/**
 * Called when user click on Install - Needed
 */
function plugin_redminesync_install() {
    global $DB;
    $DB->query("
        CREATE TABLE IF NOT EXISTS `glpi_plugin_redminesync_tickets` (
            `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `ticket_id` int NOT NULL,
            `rm_task_id` int NOT NULL COMMENT 'redmine task id',
            `created_at` datetime NOT NULL
        );
    ");

    $DB->query("
        CREATE TABLE IF NOT EXISTS `glpi_plugin_redminesync_projects` (
            `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `rm_project_id` int NOT NULL COMMENT 'redmine project id',
            `rm_project_name` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'redmine project name' ,
            `rm_project_identifier` VARCHAR(255) NOT NULL COMMENT 'redmine project identifier',
            `rm_project_status` int NOT NULL COMMENT 'redmine project status',
            `created_at` datetime NOT NULL
        );
    ");

    $DB->query("
        CREATE TABLE IF NOT EXISTS `glpi_plugin_redminesync_trackers` (
            `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `rm_tracker_id` int NOT NULL COMMENT 'redmine tracker id',
            `rm_tracker_name` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'redmine tracker name' ,
            `rm_default_status_id` VARCHAR(255) NOT NULL COMMENT 'redmine tracker default status id',
            `rm_default_status_name` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'redmine tracker default status name' ,
            `created_at` datetime NOT NULL
        );
    ");

    $DB->query("
        CREATE TABLE IF NOT EXISTS `glpi_plugin_redminesync_statuses` (
            `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `rm_status_id` int NOT NULL COMMENT 'redmine status id',
            `rm_status_name` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'redmine status name' ,
            `created_at` datetime NOT NULL
        );
    ");

    CronTask::register('PluginRedminesyncSync', 'Syncredmine', HOUR_TIMESTAMP*24,
        array(
        'comment'   => 'Sync tickets from redmine',
        'mode'      => CronTask::MODE_EXTERNAL
    ));
	
    return true;
}

/**
 * Called when user click on Uninstall - Needed
 */
function plugin_redminesync_uninstall() { return true; }

function redminesync_item_can($param){
  $param->right=1;
  return true;
}
