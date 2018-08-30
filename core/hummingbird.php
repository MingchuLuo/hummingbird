<?php
/**
 *
 * Hummingbird is a framework with the concept using Registrar to register components
 * into the Container, then use Accessor to lookup or invoke the component from Container
 * while the Registrar and the Accessor are also components so you can register your own
 * registrars and accessors. With this idea, you can extend the framework flexibly.
 *
 * User: Eason
 * Date: 25/06/2018
 * Time: 10:55 PM
 */

namespace Hummingbird {

    use Hummingbird\Framework\Pool;

    define('APP_ROOT', realpath(__DIR__. '/../../'));

    define('OBJECT_DISPOSABLE', "Object.Disposable");

    define('OBJECT_SHARED', "Object.Shared");

    define('OBJECT_POOLED', 'Object.Pooled');

    define('BINDING_SINGLE', 'Binding.Single');

    define('BINDING_GROUP', 'Binding.Group');


    /**
     * the container. contains all the components
     */
    $_container = [

        '_registrars' => [],

        '_accessors' =>[],

        '_env' => [],

        '_config' => [],

        '_listeners' => [],

        '_factories' => [],

        '_objects' => [],

        '_bindings' => [],

        '_decorators' => [],

        '_filters' => [],

        '_pipelines' => [],
    ];

    /**
     * ensure the value exists and apply the default value if not
     *
     * @param $value
     * @param null $default
     */
    function ensure(&$value, $default=null) {

        if(!isset($value)) {

            $value = $default;

        }

    }

    /**
     * registrar function to register components
     *
     * @param $component component to be registered
     * @param callable $registrar the custom registrar function to register the component
     */
    function registrar($component, callable $registrar) {

        global $_container;

        ensure($_container[$component], []);

        call_user_func($registrar, $_container[$component]);

    }

    /**
     * accessor function to lookup or invoke components
     *
     * @param $component the component to lookup or invoke
     * @param callable $accessor the custom accessor function
     * @return mixed
     */
    function accessor($component, callable $accessor) {

        global $_container;

        ensure($_container[$component], []);

        return call_user_func($accessor, $_container[$component]);

    }

    /**
     * event listener registrar function
     *
     * @param $event the event to be listened
     * @param callable $listener the listener function to be registered
     */
    function listener($event, callable $listener) {

        registrar('_listeners', function (&$comp) use ($event, $listener) {

            ensure($comp[$event], []);

            $comp[$event][] = $listener;

            $comp[$event] = $comp[$event];

        });

    }

    /**
     * factory registrar function to register factory of a specific product class
     *
     * @param $concrete_class
     * @param callable $factory
     */
    function factory($concrete_class, callable $factory) {

        registrar('_factories', function (&$comp) use ($concrete_class, $factory) {

            $comp[$concrete_class] = $factory;

        });

    }

    /**
     * binding the key to a value with the group, useful for binding abstract class to concrete class
     *
     * @param $group
     * @param $key
     * @param $value
     */
    function bind($group, $key, $value) {

        registrar('_bindings', function (&$comp) use ($group, $key, $value) {

            ensure($comp[$group], []);

            $comp[$group][$key] = $value;

        });

    }

    /**
     * decorator registrar function to register decorator class for the target class
     *
     * @param $target_class
     * @param $key give the decorator class a key to handle ordering and filtering
     * @param $decorator
     */
    function decorator($target_class, $key, $decorator) {

        registrar('_decorators', function (&$comp) use ($target_class, $key, $decorator) {

            ensure($comp[$target_class], []);

            $comp[$target_class][$key] = $decorator;

        });

    }

    /**
     * filter registrar function to register a data filter
     *
     * @param $key key of a data set
     * @param callable $filter
     */
    function filter($key, callable $filter) {

        registrar('_filters', function (&$comp) use ($key, $filter) {

            ensure($comp[$key], []);

            $comp[$key][] = $filter;

            $comp[$key] = $comp[$key];

        });

    }

    /**
     * pipeline registrar function to register pipes (middleware)
     *
     * @param $name
     * @param $pipe
     */
    function pipeline($name, $pipe) {

        registrar('_pipelines', function (&$comp) use ($name, $pipe) {

            $comp[$name] = $pipe;

        });

    }

    /**
     * create and register a pool for specific type of objects
     *
     * @param $component
     * @param $object_class
     * @param $pool_class
     */
    function pool($component, $object_class, $pool_class) {

        $pool = make($pool_class);

        registrar($component, function (&$comp) use ($object_class, $pool) {

            ensure($comp[$object_class], $pool);

        });

    }

    /**
     * fire a event, will trigger all the listener that listen to this event
     *
     * @param $event
     * @param mixed ...$params
     * @return mixed
     */
    function fire($event, ...$params) {

        return accessor('_listeners', function (&$comp) use ($event, $params) {

            $listeners = ensure($comp[$event], []);

            foreach($listeners as $listener) {

                call_user_func_array($listener, $params);

            }

        });

    }

    /**
     * apply filters to a specific data set
     *
     * @param $key
     * @param $filterable
     * @return mixed
     */
    function apply($key, $filterable) {

        return accessor('_filters', function (&$comp) use ($key, $filterable) {

            $filters = ensure($comp[$key], []);

            return array_reduce($filters, function ($filtered, $filter) {

                return call_user_func($filter, $filtered);

            }, $filterable);

        });

    }

    /**
     * decorate the target object
     *
     * @param $type
     * @param $target
     * @return mixed
     */
    function decorate($type, $target) {

        return accessor('_decorators', function (&$comp) use ($type, $target) {

            $decorators = ensure($comp[$type], []);

            return array_reduce($decorators, function ($decorated, $decorator_class) {

                return make($decorator_class, [$decorated]);

            }, $target);

        });

    }

    /**
     * lookup a binding
     *
     * @param $group
     * @param $key
     * @param null $default
     * @return mixed
     */
    function lookup($group, $key, $default=null) {

        return accessor('_bindings', function (&$binding) use ($group, $key, $default) {

            $map = isset($binding[$group]) ? $binding[$group] : [];

            return isset($map[$key]) ? $map[$key] : $default;

        });

    }

    /**
     * return a instance of the target type, first will lookup if it's shared.
     * Otherwise, it will create and instance including all the dependence specified in the constructor recursively.
     *
     * @param $abstract_class
     * @param array $dependence
     * @return mixed
     */
    function make($abstract_class, $dependence=[]) {

        $concrete_class = lookup(BINDING_SINGLE, $abstract_class, $abstract_class);

        $object = shared($concrete_class);

        if($object) {

            return $object;

        }

        return accessor('_factories', function (&$comp) use ($concrete_class, $dependence) {

            $factory = isset($comp[$concrete_class]) ? $comp[$concrete_class] : function () use ($concrete_class, $dependence) {

                $instance = instantiate($concrete_class);

                registrar('_objects', function (&$comp) use ($concrete_class, $instance) {

                    $comp[$concrete_class] = $instance;

                });

                return $instance;

            };

            return call_user_func($factory);

        });

    }

    /**
     * a helper function to turn the conditional structure to a function call
     *
     * @param $condition
     * @param callable $fn
     * @param null $default
     * @return mixed|null
     */
    function when(&$condition, callable $fn, $default=null) {

        return (isset($condition) && !!$condition) ? call_user_func($fn, $condition) : $default;

    }

    /**
     * get a object instance from cache
     *
     * @param $concrete_class
     * @return mixed
     */
    function cached($concrete_class) {

        return accessor('_objects', function (&$comp) use ($concrete_class) {

            return isset($comp[$concrete_class]) ? $comp[$concrete_class] : null;

        });
    }

    /**
     * get a object from cache, or a pool if it's pooled
     *
     * @param $concrete_class
     * @return mixed
     */
    function shared($concrete_class) {

        $target = cached($concrete_class);

        return is_a($target, Pool::class) ? $target->offer() : $target;
    }

    /**
     * instantiate a instance of the given type
     *
     * @param $concrete_class
     * @param array $dependence
     * @return object
     * @throws \ReflectionException
     */
    function instantiate($concrete_class, $dependence=[]) {

        $reflect = new \ReflectionClass($concrete_class);

        $constructor = $reflect->getConstructor();

        if($constructor && $constructor->isPublic()) {

            $paramTypes = $constructor->getParameters();

            if(count($paramTypes)== count($dependence)) {

                return $reflect->newInstanceArgs($dependence);
            }

            $params = [];

            foreach($paramTypes as $paramType) {

                $params[] = make($paramType);

            }

            return $reflect->newInstanceArgs($params);

        }

        return $reflect->newInstanceWithoutConstructor();

    }

    /**
     * a helper function to load a file and return the content
     *
     * @param $file
     * @return mixed|null
     */
    function load($file) {
        if(!file_exists($file)) return null;

        if(!is_dir($file)) return null;

        return require $file;
    }

}
