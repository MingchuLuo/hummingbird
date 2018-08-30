<?php
/**
 * Created by IntelliJ IDEA.
 * User: Eason
 * Date: 25/06/2018
 * Time: 11:58 PM
 */

namespace Hummingbird\Framework;

use function Hummingbird\make;

class Application
{

    public $container;

    public function __construct()
    {
        global $_container;
        $this->container = &$_container;
    }

    public static function run() {

        $app = new Application();

        make(Configuration::class)->load($app);

        make(Starter::class)->boot($app);

        make(Request::class)->process($app);

        $app->destroy();
    }

    public function register() {}

    public function destroy() {
    }
}
