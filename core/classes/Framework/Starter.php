<?php
/**
 * Created by IntelliJ IDEA.
 * User: Eason
 * Date: 26/06/2018
 * Time: 12:21 AM
 */

namespace Hummingbird\Framework;


class Starter
{

    public function boot(Application $app) {

        $app->register();
    }
}
