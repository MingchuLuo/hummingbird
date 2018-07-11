<?php
/**
 * Created by IntelliJ IDEA.
 * User: Eason
 * Date: 25/06/2018
 * Time: 11:58 PM
 */

namespace Hummingbird\Framework;

use function Hummingbird\fire;
use function Hummingbird\load;

class Configuration
{

    protected static $env;

    public static function setEnv($file) {
        static::$env = $file;
    }

    public function load(Application $app){
        $this->loadEnv($app);
        $this->loadConfig($app);
        fire("_config.loaded", $this);
    }

    protected function loadEnv(Application $app) {
        $lines = file($this->getEnv(), FILE_SKIP_EMPTY_LINES|FILE_IGNORE_NEW_LINES);
        $props = [];
        foreach($lines as $line) {
            list ($key, $value) = explode("=", $line);
            $key = trim($key);
            $value = trim($value);
            $props[$key] = $value;
        }
        $app->container["_env"] = array_merge($app->container["_env"], $props);
    }

    protected function loadConfig(Application $app) {
        $app->container["_config"] = load(APP_ROOT . "/app/config.php");
    }

    protected function getEnv() {
        return !is_null(static::$env) ? static::$env : APP_ROOT . "/.env";
    }
}
