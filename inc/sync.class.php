<?php
/**
 * Class PluginRedminesyncSync
 * @author iSalePro Team
 */

class PluginRedminesyncSync extends CommonGLPI
{
    static $config = array();
    static $response_data = array();
    static $rightname = "plugin_redminesync";

    static function updateConfig($data)
    {
        global $DB;
        $value = serialize(array(
            'categoryId' => $data['itilcategories_id'],
            'url' => $data['url'],
            'key' => $data['key'],
            'hour' => $data['hour'],
            'rmProjectId' => $data['rmProjectId'],
            'rmTrackerId' => $data['rmTrackerId'],
            'rmStatusId' => $data['rmStatusId'],
        ));
        $DB->query("UPDATE glpi_configs SET value='$value' WHERE context='isalepro' AND name='redmine_data'");
        $frequency = $data['hour'] * 60 * 60;
        $DB->query("UPDATE glpi_crontasks SET frequency='$frequency' WHERE itemtype='PluginRedminesyncSync' AND name='Syncredmine'");
        return true;
    }

    static function getConfig()
    {
        if (count(self::$config)) {
            return self::$config;
        } else {
            self::initConfig();
            return self::$config;
        }
    }

    /**
     * Get redmaine projects for configure Integration
     *
     * @return array
     */
    static function getRmProjects()
    {
        global $DB;
        $projects = [];

        if (!empty(self::$config['url']) && !empty(self::$config['key'])) {
            self::syncProjects();
        }

        $result = $DB->request("SELECT * FROM glpi_plugin_redminesync_projects ORDER BY rm_project_id");
        foreach ($result as $value) {
            $projects[] = $value;
        }
        return $projects;
    }


    /**
     * Get redmaine statuses for configure Integration
     *
     * @return array
     */
    static function getRmStatuses()
    {
        global $DB;
        $projects = [];

        if (!empty(self::$config['url']) && !empty(self::$config['key'])) {
            self::syncStatuses();
        }

        $result = $DB->request("SELECT * FROM glpi_plugin_redminesync_statuses ORDER BY rm_status_id");
        foreach ($result as $value) {
            $projects[] = $value;
        }
        return $projects;
    }


    /**
     * Get redmaine tracker
     *
     * @return array
     */
    static function getTracker()
    {
        global $DB;
        $tracker = false;

        if (!empty(self::$config['rmTrackerId'])) {
            $result = $DB->request("SELECT * FROM glpi_plugin_redminesync_trackers WHERE rm_tracker_id = "
                . self::$config['rmTrackerId']);
            foreach ($result as $value) {
                $tracker = $value;
            }
        }

        return $tracker;
    }

    /**
     * Get redmaine trackers for configure Integration
     *
     * @return array
     */
    static function getRmTrackers()
    {
        global $DB;
        $trackers = [];

        if (!empty(self::$config['url']) && !empty(self::$config['key'])) {
            self::syncTrackers();
        }

        $result = $DB->request("SELECT * FROM glpi_plugin_redminesync_trackers ORDER BY rm_tracker_name");
        foreach ($result as $value) {
            $trackers[] = $value;
        }
        return $trackers;
    }


    static function initConfig()
    {
        global $DB;
        $result = $DB->query("SELECT * FROM glpi_configs WHERE context='isalepro' AND name='redmine_data'");
        if ($result->num_rows == 0) {
            self::$config = array(
                'categoryId' => '',
                'url' => '',
                'key' => '',
                'hour' => 24,
                'rmProjectId' => '',
                'rmTrackerId' => '',
                'rmStatusId' => [],
            );
            $config = serialize(self::$config);
            $DB->query("INSERT INTO glpi_configs SET context='isalepro', name='redmine_data', value='$config'");
        } else {
            $result = $DB->request("SELECT * FROM glpi_configs WHERE context='isalepro' AND name='redmine_data'");
            foreach ($result as $value) {
                self::$config = unserialize($value['value']);
                return;
            }
        }
    }

    /**
     * Cron command sync
     *
     * @param $task
     * @return bool
     */
    static function cronSyncredmine($task)
    {
        self::initConfig();
        self::syncAddTasks();
        self::syncTasksStatuses();
        return true;
    }

    /**
     * to sync statuses
     *
     * @return array|bool
     */
    static function syncStatuses()
    {
        global $DB;

        if (self::$config['url'] == '' || self::$config['key'] == '') {
            return false;
        }
        $ch = curl_init();
        $requestUrl = self::$config['url'] . '/issue_statuses.json?key=' . self::$config['key'];
        curl_setopt($ch, CURLOPT_URL, $requestUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $result = json_decode($response);

        // no issue statuses
        if (NULL == $result || !count($result->issue_statuses)) {
            return [];
        }
        $statusData = [];

        foreach ($result->issue_statuses as $value) {
            $statusData[] = [
                'rm_status_id' => $value->id,
                'rm_status_name' => $value->name,
            ];
        }

        foreach ($statusData as $statusDatum) {

            $DB->updateOrInsert(
                'glpi_plugin_redminesync_statuses',
                $statusDatum,
                ['rm_status_id' => $statusDatum['rm_status_id']]
            );
        }
        return true;
    }

    /**
     * to sync trackers
     *
     * @return array|bool
     */
    static function syncTrackers()
    {
        global $DB;

        if (self::$config['url'] == '' || self::$config['key'] == '') {
            return false;
        }
        $ch = curl_init();
        $requestUrl = self::$config['url'] . '/trackers.json?key=' . self::$config['key'];
        curl_setopt($ch, CURLOPT_URL, $requestUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $result = json_decode($response);

        // no trackers
        if (NULL == $result || !count($result->trackers)) {
            return [];
        }

        $trackersData = [];

        foreach ($result->trackers as $value) {
            $trackersData[] = [
                'rm_tracker_id' => $value->id,
                'rm_tracker_name' => $value->name,
                'rm_default_status_id' => $value->default_status->id,
                'rm_default_status_name' => $value->default_status->name,
            ];
        }

        foreach ($trackersData as $trackersDatum) {

            $DB->updateOrInsert(
                'glpi_plugin_redminesync_trackers',
                $trackersDatum,
                ['rm_tracker_id' => $trackersDatum['rm_tracker_id']]
            );
        }

        return true;
    }

    /**
     * to sync projects
     *
     * @return array|bool
     */
    static function syncProjects()
    {
        global $DB;
        if (self::$config['url'] == '' || self::$config['key'] == '') {
            return false;
        }
        $ch = curl_init();
        $requestUrl = self::$config['url'] . '/projects.json?key=' . self::$config['key'];
        curl_setopt($ch, CURLOPT_URL, $requestUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $result = json_decode($response);

        // no projects
        if (NULL == $result || !count($result->projects)) {
            return [];
        }

        $projectData = [];

        foreach ($result->projects as $value) {
            $projectData[] = [
                'rm_project_id' => $value->id,
                'rm_project_name' => $value->name,
                'rm_project_identifier' => $value->identifier,
                'rm_project_status' => $value->status,
            ];
        }

        foreach ($projectData as $projectDatum) {

            $DB->updateOrInsert(
                'glpi_plugin_redminesync_projects',
                $projectDatum,
                ['rm_project_id' => $projectDatum['rm_project_id']]
            );
        }

        return true;
    }

    /**
     * to add task
     *
     * @return bool
     */
    static function syncAddTasks()
    {
        global $DB;
        if (self::$config['url'] == '' || self::$config['key'] == '') {
            return false;
        }

        $tracker = self::getTracker();
        if (empty($tracker['rm_default_status_id'])) {
            return false;
        }

        $lastDate = date("Y-m-d H:i:s", strtotime('-' . self::$config['hour'] . ' hours'));
        $whereCategory = self::$config['categoryId']
            ? ' AND t.itilcategories_id = ' . self::$config['categoryId'] .' '
            : '';
        $result = $DB->request("
            SELECT 
                t.*
            FROM 
                 glpi_tickets t 
                 LEFT JOIN glpi_plugin_redminesync_tickets rt ON rt.ticket_id = t.id
             WHERE 
                   t.date >= '" . $lastDate . "'
                   ".$whereCategory."
                   AND rt.id IS NULL");
        $tickets = [];
        foreach ($result as $item) {
            $tickets[] = $item;
        }

        if ($tickets) {
            foreach ($tickets as $ticket) {
                $ch = curl_init();
                $requestUrl = self::$config['url'] . '/issues.json?key=' . self::$config['key'];

                $postData = [
                    'issue' => [
                        'subject' => $ticket['name'],
                        'description' => $ticket['content'],
                        'project_id' => self::$config['rmProjectId'],
                        'tracker_id' => self::$config['rmTrackerId'],
                        'status_id' => $tracker['rm_default_status_id'],
                    ]
                ];

                $payload = json_encode($postData);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
                curl_setopt($ch, CURLOPT_URL, $requestUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($ch);
                $result = json_decode($response);
                if (NULL == $result || !count($result->issue)) {
                    return true;
                }
                $now = date('Y-m-d H:i:s');
                $rmIssueId = $result->issue->id;
                $addHistorySql = "INSERT INTO glpi_plugin_redminesync_tickets SET ticket_id='" . $ticket['id'] . "', rm_task_id='$rmIssueId', created_at='$now'";
                $DB->query($addHistorySql);
            }
        }
        return true;
    }

    /**
     * to sync tasks statuses
     *
     * @return array|bool
     */
    static function syncTasksStatuses()
    {
        global $DB;
        if (self::$config['url'] == '' || self::$config['key'] == '' || empty(self::$config['rmStatusId'])) {
            return false;
        }

        $needSynchTickts = $DB->request("
            SELECT 
                rt.*
            FROM 
                glpi_plugin_redminesync_tickets rt
                JOIN glpi_tickets t  ON rt.ticket_id = t.id
            WHERE 
                 t.status NOT IN (" . Ticket::SOLVED . ", " . Ticket::CLOSED . ")");
        $tickets = [];
        foreach ($needSynchTickts as $item) {
            $tickets[$item['rm_task_id']] = $item;
        }

        $ticketIds = array_map(function ($ticket) {
            return $ticket['rm_task_id'];
        }, $tickets);

        if ($ticketIds) {
            $ch = curl_init();
            $requestUrl = self::$config['url'] . '/issues.json?status_id=*&issue_id=' . implode(',', $ticketIds) . '&key=' . self::$config['key'];
            curl_setopt($ch, CURLOPT_URL, $requestUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            $result = json_decode($response);
            // no issues
            if (NULL == $result || !count($result->issues)) {
                return [];
            }

            foreach ($result->issues as $issue) {
                $newTicketStatusId = self::$config['rmStatusId'][$issue->status->id];
                $ticket = $tickets[$issue->id];
                $updateStatusSql = "UPDATE glpi_tickets SET status=" . $newTicketStatusId . " WHERE id = " . $ticket['ticket_id'];
                $DB->query($updateStatusSql);
            }
        }
        return  true;
    }
}
