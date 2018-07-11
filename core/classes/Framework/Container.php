<?php
/**
 * Created by IntelliJ IDEA.
 * User: Eason
 * Date: 26/06/2018
 * Time: 11:49 PM
 */

namespace Hummingbird\Framework;


class Container
{
    public function __construct(\SplQueue $queue)
    {
        $queue->add(0, 1);
        die($queue->shift());
    }
}
