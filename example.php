<?php

require('Tentacle.php');

const PANDORA_IP = '1.2.3.4';
const TENTACLE_PORT = '41121';
const AGENT_FREQUENCY = '60'; // In seconds!
const AGENT_NAME = 'webservice-monitoring';

$t = new Tentacle(PANDORA_IP, TENTACLE_PORT);

$module = [
    [
        'name' => 'Webservice status',
        'data' => '{"status": "CRIT", "body": {"title": "Se lió parda", "message": "ñá ñá çava"}}',
        'str_warning' => ': "WARN',
        'str_critical' => ': "CRIT',
        'alert_template' => [
            "Technical support 24x7 critical",
            "Technical support 24x7 warning",
            "Technical support 24x7 unknown",
        ],
        'type' => 'generic_data_string',
        'module_interval' => '60',
    ],
    [
        'name' => 'Other webservice status',
        'data' => '{"status": "WARN", "body": {"title": "Se lió parda", "message": "ñá ñá çava"}}',
        'str_warning' => ': "WARN',
        'str_critical' => ': "CRIT',
        'alert_template' => [
            "Technical support 24x7 critical",
            "Technical support 24x7 warning",
            "Technical support 24x7 unknown",
        ],
        'type' => 'generic_data_string',
        'module_interval' => '600',
    ],
    [
        'name' => 'Yet another webservice status',
        'data' => '{"status": "OK", "body": {"title": "good", "message": "yep, all good bro."}}',
        'str_warning' => ': "WARN',
        'str_critical' => ': "CRIT',
        'alert_template' => [
            "Technical support 24x7 critical",
            "Technical support 24x7 warning",
            "Technical support 24x7 unknown",
        ],
        'type' => 'generic_data_string',
        'module_interval' => '300',
    ],
];

$t->sendDataToPandora($module, AGENT_FREQUENCY, AGENT_NAME);

?>
