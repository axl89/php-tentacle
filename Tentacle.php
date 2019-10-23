<?php

final class Tentacle
{
    private $pandora_ip;
    private $tentacle_port;

    const REQUIRED_MODULE_FIELDS = [
        'name' => null,
        'type' => null,
        'data' => null,
    ];

    const OPTIONAL_MODULE_FIELDS = [
        'description'=> null,
        'max'=> null,
        'min'=> null,
        'descripcion'=> null,
        'post_process'=> null,
        'module_interval'=> null,
        'min_critical'=> null,
        'max_critical'=> null,
        'min_warning'=> null,
        'max_warning'=> null,
        'disabled'=> null,
        'min_ff_event'=> null,
        'datalist'=> null,
        'status'=> null,
        'unit'=> null,
        'timestamp'=> null,
        'module_group'=> null,
        'custom_id'=> null,
        'str_warning'=> null,
        'str_critical'=> null,
        'critical_instructions'=> null,
        'warning_instructions'=> null,
        'unknown_instructions'=> null,
        'tags'=> null,
        'critical_inverse'=> null,
        'warning_inverse'=> null,
        'quiet'=> null,
        'module_ff_interval'=> null,
        'alert_template'=> null,
        'crontab'=> null,
        'min_ff_event_normal'=> null,
        'min_ff_event_warning'=> null,
        'min_ff_event_critical'=> null,
        'ff_timeout'=> null,
        'each_ff'=> null,
        'module_parent'=> null,
        'module_parent_unlink'=> null,
        'cron_interval'=> null,
        'ff_type'=> null,
    ];

    public function __construct($pandora_ip, $tentacle_port)
    {
        //Is tentacle_client present?
        if (!self::commandExists('tentacle_client')) {
            throw new Exception('Tentacle client not present on PATH. Check your Pandora Agent installation');
        }

        $this->pandora_ip = $pandora_ip;
        $this->tentacle_port = strval($tentacle_port);
        $this->tmp_folder = '/tmp';
    }


    private static function commandExists($cmd)
    {
        $return_val = shell_exec(sprintf("which %s", escapeshellarg($cmd)));
        return $return_val != null;
    }

    private static function getAgentXMLHeader($agent_name, $frequency)
    {
        $utc_time = gmdate('Y/m/d H:i:s', time());
        $str = '<?xml version="1.0" encoding="utf-8" ?>'.PHP_EOL; //Agent header
        $str .= '<agent_data agent_name="'.$agent_name.'" timezone_offset="0" description=""  timestamp="'.$utc_time.'" interval="'.strval($frequency).'">'.PHP_EOL;

        return $str;
    }

    private static function getAgentXMLFooter()
    {
        $str = '</agent_data>';
        return $str;
    }

    private static function getModuleXML($monitoring_data, $agent_frequency)
    {
        $str = '<module>'.PHP_EOL;
        foreach ($monitoring_data as $module_info => $value) {
            if ($value === null) {
                continue;
            }
            // Special case, for module_interval
            if ($module_info === 'module_interval') {
                $module_frequency = $monitoring_data['module_interval'];
                //The module interval is specified as a multiplier of the agent's frequency. See http://wiki.pandorafms.com/index.php?title=Pandora:Windows_Agent#Modules_definition
                $pandorized_module_interval = ceil(floatval($module_frequency) / floatval($agent_frequency));
                $str .= '<module_interval>'.strval($pandorized_module_interval).'</module_interval>'.PHP_EOL;
            } elseif (is_array($value)) {
                // Special case for alert_template
                if ($module_info === 'alert_template') {
                    foreach ($value as $alert_name) {
                        $str .= "<$module_info><![CDATA[$alert_name]]></$module_info>".PHP_EOL;
                    }
                } else {
                    $str .= "<$module_info><![CDATA[".implode(',', $value)."]]></$module_info>".PHP_EOL;
                }
            } elseif ($module_info === 'module_name' ||
                $module_info === 'module_data' ||
                $module_info === 'module_description' ||
                $module_info === 'module_data'
            ) {
                // Maintain retrocompat. with all schedulers
                // that send data like module_name or module_data
                $module_info = str_replace('module_', '', $module_info);
                $str .= "<$module_info><![CDATA[".$value."]]></$module_info>".PHP_EOL;
            } else {
                $str .= "<$module_info><![CDATA[".$value."]]></$module_info>".PHP_EOL;
            }
        }


        $str .= '</module>'.PHP_EOL;

        return $str;
    }

    private function getXMLToSendToPandora($modules, $agent_frequency = '300', $agent_name = null)
    {
        if ($agent_name === null) {
            $agent_name = gethostname();
        }

        $agent_header = self::getAgentXMLHeader($agent_name, $agent_frequency);

        $module_str = '';

        foreach ($modules as $module) {
            $monitoring_data = array_merge(self::REQUIRED_MODULE_FIELDS, self::OPTIONAL_MODULE_FIELDS, $module);
            $module_str .= self::getModuleXML($monitoring_data, $agent_frequency);
        }

        $agent_footer = self::getAgentXMLFooter();

        $final_xml = $agent_header.$module_str.$agent_footer;

        return $final_xml;
    }

    public function sendDataToPandora($modules, $agent_frequency = '300', $agent_name = null)
    {
        if ($agent_name === null) {
            $agent_name = gethostname();
        }

        $xml_string = self::getXMLToSendToPandora($modules, $agent_frequency, $agent_name);
        $file_path = $this->tmp_folder.DIRECTORY_SEPARATOR.$agent_name.'_'.mt_rand(0, 10000).'.'.time().'.data';
        $tmp_file = fopen($file_path, 'w');
        fwrite($tmp_file, $xml_string);
        fclose($tmp_file);
        $output = shell_exec("tentacle_client -a $this->pandora_ip -p $this->tentacle_port $file_path");
        //unlink($file_path); //Deletes the file
        return $output;
    }
}
