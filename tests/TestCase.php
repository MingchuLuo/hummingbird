<?php
/**
 * Created by IntelliJ IDEA.
 * User: Eason
 * Date: 26/06/2018
 * Time: 11:38 PM
 */

namespace test;

use PHPUnit\Framework\TestCase as PHPUnit;

class TestCase extends PHPUnit {

    public function setUp()
    {
        parent::setUp();
        require __DIR__ . "/../vendor/autoload.php";
        require __DIR__ . "/../core/hummingbird.php";
    }
}
