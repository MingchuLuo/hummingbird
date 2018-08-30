<?php
/**
 * Created by IntelliJ IDEA.
 * User: Eason
 * Date: 26/06/2018
 * Time: 11:39 PM
 */

namespace test\Unit;

use Hummingbird\Framework\Application;
use test\TestCase;

class FrameworkTestCase extends TestCase
{

    /**
     * @test
     */
    public function resolve_dependence() {
        $app = make(Application::class);
        $this->assertNotNull($app->container);
    }
}
