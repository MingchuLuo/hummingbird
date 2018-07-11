<?php
/**
 * Container
 *
 * Component
 *
 * Functional Component
 * Container Component
 *
 * Registrar
 * Factory
 * Event
 * Filter
 * Pipeline
 *
 * User: Eason
 * Date: 25/06/2018
 * Time: 10:55 PM
 */

define('DISPOSABLE', "Disposable");

define('SHARED', "Shared");

define('POOLED', "Pooled");


$_container = [

    /*
     * Contains parsed .env file properties.
     */
    '_env' => [],

    /*
     * Contains config.php object with .env applied
     */
    '_config' => [],

    /*
     * Contains factories and instructions for creating objects.
     */
    '_factories' => [],

    /*
     *
     */
    '_explanations' => [],

    /*
     * Contains abstract type to concrete type bindings for creating objects.
     */
    '_bindings' => [],

    /*
     *
     */
    '_decorators' => [],

    /*
     * Contains created Shared or Pooled objects, but not Disposable objects.
     */
    '_objects' => [],

    /*
     * Contains registered events and their listeners.
     */
    '_listeners' => [],

    /**
     *
     */
    '_pipelines' => [],

    /*
     * Contains filter functions for filtering request and data
     */
    '_filters' => [],

    /*
     * Contains all data generated or loaded at runtime to be shared
     */
    '_data' => [],

    /*
     * Contains custom registrars for registering components
     */
    '_registrars' => [],
];

if(!function_exists('env')) {

    function env($prop, $default=null) {

        return chunk('_env', $prop, $default);

    }

}

if(!function_exists('config')) {

    function config($key, $default=null) {

        return chunk('_config', $key, $default);

    }

}

if(function_exists('chunk')) {

    function chunk($component, $key, $default=null) {

        global $_container;

        $chunk = ensure($_container[$component], []);

        $parts = explode(".", $key);

        foreach($parts as $part) {

            if(!isset($chunk[$part])){
                return $default;
            }

            $chunk = $chunk[$part];
        }

        return $chunk;
    }
}

if(!function_exists('factory')) {

    function factory($concrete, Closure $builder, $options=[]) {}

}


if(!function_exists('bind')) {

    function bind($group, $key, $value) {

        global $_container;

        $bindings = $_container['_bindings'];

        ensure($bindings[$group], []);

        $bindings[$group][$key] = $value;

    }

}


if(!function_exists('unbind')) {

    function unbind($group, $key) {

        global $_container;

        $bindings = $_container['_bindings'];

        ensure($bindings[$group], []);

        unset($bindings[$group][$key]);

        $bindings[$group][$key] = null;

    }

}

if(!function_exists('concrete')) {

    function concrete($abstract) {

        return lookup('type', $abstract, $abstract);

    }

}

if(!function_exists('lookup')) {

    function lookup($group, $key, $default=null) {

        global $_container;

        $bindings = $_container['_bindings'];

        ensure($bindings[$group], []);

        return isset($bindings[$group][$key]) ? $bindings[$group][$key] : $default;

    }

}


if(!function_exists('ensure')) {

    function ensure(&$value, $default=null) {

        if(!isset($value)) {
            $value = $default;
        }

        return $value;
    }

}

if(!function_exists('make')) {

    function make($abstract, $options=[]) {
        global $_container;
        // find the specs
        $concrete = concrete($abstract);

        $style = ensure($options['style'], DISPOSABLE);

        $objects = $_container['_objects'];

        $object = null;

        switch ($style) {
            case SHARED: {
                if(isset($objects[$concrete])) {
                    $object = $objects[$concrete];
                }
                break;
            }
            case POOLED: {
                if(isset($objects[$concrete])) {
                    $pool = $objects[$concrete];
                    $object = $pool->obtain();
                }
                break;
            }
            case DISPOSABLE:
            default: {
                $object = new $concrete();
            }
        }

        return decorate($concrete, $object);
    }

}

if(!function_exists('decorator')) {

    function decorator($abstract, $decorator, $key=null) {
        register('_decorators', $abstract, $decorator);
    }

}


if(!function_exists('decorate')) {

    function decorate($abstract, $object, $decorators=[]) {
        global $_container;

        if(!$object) {
            return $object;
        }

        $comp_decorators = $_container['_decorators'];
        $decorated_object = $object;
        $decorators = ensure($comp_decorators[$abstract], []);

        $decorators = apply('_filters.'.$abstract, $decorators);
        // filter or reorder decorators
        foreach($decorators as $decorator) {
            $decorated_object = call_user_func($decorator, $decorated_object);
            if(!$decorated_object instanceof $abstract) {
                throw new Exception("Decorator cannot change target's type");
            }
        }

        return $decorated_object;

    }

}


if(!function_exists('fire')) {

    function fire($event, ...$params) {

        global $_container;

        $comp_listeners = $_container['_listeners'];

        $listeners = ensure($comp_listeners[$event], []);

        foreach($listeners as $listener) {

            call_user_func_array($listener, $params);

        }
    }

}


if(!function_exists('listener')) {

    function listener($event, $listener) {

        register('_listeners', $event, $listener);

    }

}

if(!function_exists('pipeline')) {

    function pipeline($name, Closure $fn, $options=[]) {}

}


if(!function_exists('filter')) {

    function filter($key, $filter) {

        register('_filters', $key, $filter);

    }

}


if(!function_exists('apply')) {

    function apply($key, $data) {

        global $_container;

        $comp_filters = $_container['_filters'];

        $filters = ensure($comp_filters[$key], []);

        return array_reduce($filters, function(&$result, $filter) {

            return call_user_func($filter, $result);

        }, $data);

    }

}

if(!function_exists('registrar')) {

    function registrar($component, Closure $fn, $overwrite=true) {

        global $_container;

        $comp_registrars = &$_container['_registrars'];

        ensure($comp_registrars[$component], null);

        if(!$comp_registrars[$component] || $overwrite) {
            $comp_registrars[$component] = $fn;
        }
    }

}

if(!function_exists('register')) {

    function register($component, ...$params) {

        global $_container;

        $registrar = &ensure($_container['_registrars'][$component], function ($component, $key, $object) use (&$_container) {
            $_container[$component][$key] = ensure($_container[$component][$key], []);
            $_container[$component][$key][] = $object;
        });

        call_user_func_array($registrar, $params);
    }

}
