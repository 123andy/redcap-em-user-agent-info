<?php

namespace Stanford\UserAgentInfo;

include_once "emLoggerTrait.php";

require_once "BrowserDetection.php";

use REDCap;

class UserAgentInfo extends \ExternalModules\AbstractExternalModule
{

    use emLoggerTrait;
    public $options;    // Array to hold all options available for mapping
    static $CONFIG_KEYS = ['ua_option', 'field_name', 'overwrite'];

    /**
     *
     * ADD NEW OPTIONS HERE TO MAKE THEM AVAILABLE
     * AND ALSO ADD THEM TO THE CONFIG.JSON OPTIONS
     *
     */
    function buildOptions() {
        $options['ip-address']          = isset($_SERVER['HTTP_CLIENT_IP'])?$_SERVER['HTTP_CLIENT_IP']:isset($_SERVER['HTTP_X_FORWARDE‌​D_FOR'])?$_SERVER['HTTP_X_FORWARDED_FOR']:$_SERVER['REMOTE_ADDR'];
        $options['remote-addr']         = $_SERVER['REMOTE_ADDR'];
        $options['x-forwarded-for']     = $_SERVER['HTTP_X_FORWARDED_FOR'];
        $options['hostname-from-ip']    = gethostbyaddr($_SERVER["REMOTE_ADDR"]);
        $options['referrer']            = $_SERVER['HTTP_REFERER'];

        // Get a bunch of stuff from this browserDetection class
        // https://wolfcast.com/open-source/browser-detection/doc/Browser_Detection/BrowserDetection.html
        $browser = new BrowserDetection();
        $options['browser-name']        = $browser->getName();
        $options['user-agent']          = $browser->getUserAgent();
        $options['platform']            = $browser->getPlatform();
        $options['platform-version']    = $browser->getPlatformVersion();
        $options['is-mobile']           = (int) $browser->isMobile();
        $options['is-robot']            = (int) $browser->isRobot();

        $this->options = $options;
        $this->emDebug("Options", $options);
    }


    /**
     * This is the hook to inject on survey pages
     **/
    function redcap_survey_page_top($project_id, $record, $instrument, $event_id, $group_id, $survey_hash, $response_id, $repeat_instance) {

        // Get the configs
        $configs = $this->pivotRepeatingSettings(self::$CONFIG_KEYS);
        $this->emDebug("Raw Configs",$configs);
        $configs = $this->filterConfigsByInstrumentFields($configs, $instrument);
        $this->emDebug("Filtered Configs",$configs);

        // Exit out if there is nothing to do on this instrument
        if (empty($configs)) return false;

        // Load up all of the options
        $this->buildOptions();

        // Add the option value to the configs
        foreach ($configs as $i => &$config) {
            $config['value'] = $this->getOption($config['ua_option']);
        }

        // Now we need to push this config to the client
        $jsUrl = $this->getUrl('UserAgentInfo.js', false, true);
        ?>
            <script src='<?php echo $jsUrl; ?>'></script>
            <script>
                if (typeof UserAgentInfo === "undefined") {
                    alert("This page uses an external module called 'UserAgentInfo' but due to a configuration error " +
                        "the module is not loading the required javascript library correctly.\n\n" +
                        "Please notify the project administrator."
                    );
                } else {
                    UserAgentInfo.configs = <?php echo json_encode(array_values($configs)); ?>;
                    UserAgentInfo.isDev = <?php echo json_encode((boolean) $this->getProjectSetting('enable-project-debug-logging')); ?>;
                    $(document).ready(function () {
                        UserAgentInfo.init();
                    });
                }
            </script>
        <?php
    }


    /**
     * Filter configs by fields in the instrument provided
     *
     * @param $configs
     * @param $instrument
     * @return mixed
     */
    public function filterConfigsByInstrumentFields($configs, $instrument) {
        // Get the fields defined on this form
        $fields = REDCap::getFieldNames($instrument);

        // Build the params that apply to this instrument
        foreach ($configs as $i => &$config) {
            if (!in_array($config['field_name'], $fields)) {
                unset($configs[$i]);
            }
        }
        return $configs;
    }



    /**
     * Return the option requested
     * @param $option
     * @return string
     */
    public function getOption($option) {
        if (!isset($this->options[$option])) {
            $this->emError("Call for undefined option: $option - valid options are: " . implode(",",array_keys($this->options)));
            return "";
        } else {
            return $this->options[$option];
        }
    }


    /**
     *
     * This function takes a list of keys (from a repeating group in the config.json) and pivots them out into
     * an array where each array is a group of all entries.
     *
       In other words, it converts this:
        [ua-option] => [
            [0] => AA
            [1] => BB
        ]

        [field-name] => [
            [0] => ip_address
            [1] => machine_name
        ]

        [overwrite] => [
            [0] => 1
            [1] =>
        ]

        INTO THIS:
        [
            [0] => [
                [ua-option] => AA
                [field-name] => ip_address
                [overwrite] => 1
            ],
            [1] => [
                [ua-option] => BB
                [field-name] => machine_name
                [overwrite] =>
            ]
     * @param array $keys (keys from config.json in same repeating group)
     * @return array
     */
    function pivotRepeatingSettings($keys) {
        $config = [];
        foreach ($keys as $key) {
            $config[$key] = $this->getProjectSetting($key);
        }

        // Now we have a bunch variables with the same number of repeats.  Lets group them:

        $map = [];
        $count = count($keys[0]);
        for ($i = 0; $i <= $count; $i++) {
            $c = [];
            foreach ($keys as $key) {
                $c[$key] = $config[$key][$i];
            }
            $map[$i] = $c;
        }

        return $map;
    }

}
