<?php
/**
 * Created by IntelliJ IDEA.
 * User: Eason
 * Date: 14/07/2018
 * Time: 6:09 PM
 */

namespace Hummingbird\Framework;


interface Pool
{

    public function offer();

    public function recycle($object);

}
