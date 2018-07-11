<?php
/**
 * Created by IntelliJ IDEA.
 * User: Eason
 * Date: 25/06/2018
 * Time: 11:58 PM
 */

namespace Hummingbird\Framework;

use function Hummingbird\make;

abstract class Application
{

    public $container;

    public function __construct()
    {
        global $_container;
        $this->container = &$_container;
    }

    public function run() {

        make(Configuration::class)->load($this);

        make(Starter::class)->boot($this);

        make(Request::class)->process($this);

        $this->destroy();
    }

    protected function register() {}

    protected function destroy() {
    }
}
