<?php

include('../../../inc/includes.php');

$plugin = new Plugin();
if ($plugin->isActivated("redminesync")) {
    if (!empty($_POST["update"])) {
        PluginRedminesyncSync::updateConfig($_POST);
        Session::addMessageAfterRedirect(__('Config updated successfully'), false, INFO);
        Html::back();
    } else {
        $config = PluginRedminesyncSync::getConfig();
        $projects = PluginRedminesyncSync::getRmProjects();
        $trackers = PluginRedminesyncSync::getRmTrackers();
        $statuses = PluginRedminesyncSync::getRmStatuses();
        $glpiTicketStatuses = Ticket::getAllStatusArray(false);
        Html::header('RedmineConfig', '', "admin", "pluginredminesync");
        echo '<form method="post" name="helpdeskform" action="">'; ?>
        <table class="tab_cadre_fixehov">
            <tr>
                <td colspan="2">
                    <h2>Настройка интеграции с Redmine</h2>
                </td>
            </tr>
            <tr class='tab_bg_2'>
                <td>Категория</td>
                <td>
                    <?php ITILCategory::dropdown(['value' => $config['categoryId']]); ?>
                </td>
            </tr>

            <tr class='tab_bg_2'>
                <td>URL</td>
                <td>
                    <input type="text" name="url" value="<?php echo $config['url']; ?>">
                </td>
            </tr>
            <tr class='tab_bg_2'>
                <td>Api key</td>
                <td>
                    <input type="text" name="key" value="<?php echo $config['key']; ?>">
                </td>
            </tr>
            <tr class='tab_bg_2'>
                <td>Интервал запуска синхронизации</td>
                <td>
                    <select name="hour" class="form-control">
                        <?php
                        $default=$config['hour'];
                        for ($i=1; $i <=24 ; $i++) {
                            $selected = $i==$default?' selected="selected" ':'';
                            echo "<option $selected value='$i'>$i Hour</option>";
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr class='tab_bg_2'>
                <td>Проект в Redmine</td>
                <td>
                    <select name="rmProjectId" class="form-control">
                        <?php
                        $default=$config['rmProjectId'];
                        foreach ($projects as $project) {
                            $selected = $project['rm_project_id'] == $config['rmProjectId'] ? ' selected="selected" ' : '';
                            echo "<option $selected value='".$project['rm_project_id']."'>".$project['rm_project_name']."</option>";
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr class='tab_bg_2'>
                <td>Трекер в Redmine</td>
                <td>
                    <select name="rmTrackerId" class="form-control">
                        <?php
                        $default=$config['rmTrackerId'];
                        foreach ($trackers as $tracker) {
                            $selected = $tracker['rm_tracker_id'] == $config['rmTrackerId'] ? ' selected="selected" ' : '';
                            echo "<option $selected value='".$tracker['rm_tracker_id']."'>".$tracker['rm_tracker_name']."</option>";
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td colspan="2">Статусы</td>
            </tr>
            <?php
            foreach ($statuses as $status){
                echo "<tr>";
                echo "<td>";
                echo $status['rm_status_name'];
                echo "</td>";
                echo "<td>";
                echo "<select name=\"rmStatusId[".$status['rm_status_id']."]\" class=\"form-control\">";
                $default=$config['rmStatusId'][$status['rm_status_id']];
                foreach ($glpiTicketStatuses as $glpiTicketStatusId => $glpiTicketStatusName) {
                    $selected = $glpiTicketStatusId == $default ? ' selected="selected" ' : '';
                    echo "<option $selected value='".$glpiTicketStatusId."'>".$glpiTicketStatusName."</option>";
                }
                echo "</select>";
                echo "</td>";
                echo "</tr>";
            }
            ?>
            <tr>
                <td colspan="2">
                    <center><input type="submit" value="Сохранить" name="update" class="submit"></center>
                </td>
            </tr>
        </table>

        <?php Html::closeForm();
        Html::footer();
    }
} else {
    Html::header(__('Setup'), '', "config", "plugins");
    echo "<div align='center'><br><br>";
    echo "<img src=\"" . $CFG_GLPI["root_doc"] . "/pics/warning.png\" alt='warning'><br><br>";
    echo "<b>" . __('Please activate the plugin', 'redminesync') . "</b></div>";
    Html::footer();
}
