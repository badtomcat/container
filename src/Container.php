<?php
/**
 * 2017/4/26 17:36:25
 * IOC容器
 */

namespace Badtomcat;

use Closure;
use ArrayAccess;
use Exception;
use ReflectionClass;

class Container implements ArrayAccess
{
    //绑定实例
    public $bindings = [];
    //单例服务
    public $instances = [];

    /**
     * 服务绑定到容器
     *
     * @param string $name 服务名
     * @param closure|string $closure 返回服务对象的闭包函数
     * @param bool $force 是否单例
     * @return $this
     */
    public function bind($name, $closure, $force = false)
    {
        $this->bindings[$name] = compact('closure', 'force');
        return $this;
    }

    /**
     * @param $name
     * @return bool
     */
    public function bound($name)
    {
        return array_key_exists($name, $this->instances) || array_key_exists($name, $this->bindings);
    }

    /**
     * 注册单例服务
     *
     * @param string $name 服务
     * @param closure $closure 闭包函数
     * @return $this
     */
    public function single($name, $closure)
    {
        $this->bind($name, $closure, true);
        return $this;
    }

    /**
     * 创建名称和对象的链接
     *
     * @param string $name 名称
     * @param mixed $object 对象
     * @return $this
     */
    public function instance($name, $object)
    {
        $this->instances[$name] = $object;
        return $this;
    }

    /**
     * 获取服务实例
     * 如果存在于instances中，直接返回instances中对象，没有利用反射NEW一个对象
     * 这中间涉及到构造函数的参数问题，现在处理方式使用默认值，如果没有默认值会抛出一个异常
     * @param string $name 服务名
     * @param bool $force 单例
     *
     * @return mixed|object
     * @throws Exception
     */
    public function make($name, $force = false)
    {
        if (isset($this->instances[$name])) {
            return $this->instances[$name];
        }
        //获得实现提供者
        $closure = $this->getClosure($name);
        //获取实例
        $object = $this->build($closure);
        //单例绑定
        if (isset($this->bindings[$name]['force']) && $this->bindings[$name]['force'] || $force) {
            $this->instances[$name] = $object;
        }

        return $object;
    }

    /**
     * 获得实例实现
     *
     * @param string $name 创建实例方式:类名或闭包函数
     *
     * @return mixed
     */
    private function getClosure($name)
    {
        return isset($this->bindings[$name]) ? $this->bindings[$name]['closure'] : $name;
    }

    /**
     * 依赖注入方式调用函数
     *
     * @param $function
     *
     * @return mixed
     * @throws Exception
     */
    public function callFunction($function)
    {
        $reflectionFunction = new \ReflectionFunction($function);
        $args = $this->getDependencies($reflectionFunction->getParameters());

        return $reflectionFunction->invokeArgs($args);
    }

    /**
     * 反射执行方法并实现依赖注入
     *
     * @param string|object $class 类
     * @param string $method 方法
     *
     * @return mixed
     * @throws Exception
     */
    public function callMethod($class, $method)
    {
        //反射方法实例
        $reflectionMethod = new \ReflectionMethod($class, $method);
        //解析方法参数
        $args = $this->getDependencies($reflectionMethod->getParameters());

        //生成类并执行方法
        return $reflectionMethod->invokeArgs($this->build($class), $args);
    }

    /**
     * 生成服务实例
     *
     * @param mixed $className 生成方式 类或闭包函数
     *
     * @return object
     * @throws Exception
     */
    public function build($className)
    {
        //匿名函数
        if ($className instanceof Closure) {
            //执行闭包函数
            return $this->callFunction($className);
        }

        //获取类信息
        $reflector = new ReflectionClass($className);
        // 检查类是否可实例化, 排除抽象类abstract和对象接口interface
        if (!$reflector->isInstantiable()) {
            throw new Exception("$className 不能实例化.");
        }
        //获取类的构造函数
        $constructor = $reflector->getConstructor();
        //若无构造函数，直接实例化并返回
        if (is_null($constructor)) {
            return new $className;
        }
        //取构造函数参数,通过 ReflectionParameter 数组返回参数列表
        $parameters = $constructor->getParameters();
        //递归解析构造函数的参数
        $dependencies = $this->getDependencies($parameters);

        //创建一个类的新实例，给出的参数将传递到类的构造函数。
        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * 递归解析参数,可以传递一个环境,用于变量解析
     *
     * @param \ReflectionParameter[] $parameters
     * (new ReflectionClass( $className ))->getConstructor()->getParameters()
     * (new \ReflectionFunction( $function ))->getParameters()
     * (new \ReflectionMethod( $class, $method ))->getParameters()
     *
     * @param array $env
     * @return array
     * @throws Exception
     */
    public function getDependencies($parameters, array $env = [])
    {
        $dependencies = [];
        //参数列表
        foreach ($parameters as $parameter) {
            //获取参数类型
            $dependency = $parameter->getClass();
            if (is_null($dependency)) {
                //是变量,有默认值则设置默认值
                $dependencies[] = $this->resolveNonClass($parameter);
            } else if (array_key_exists($dependency->name, $env)) {
                //是一个类,递归解析
                $dependencies[] = $env[$dependency->name];
            } else {
                //是一个类,递归解析
                $dependencies[] = $this->build($dependency->name);
            }
        }

        return $dependencies;
    }

    /**
     * 提取参数默认值
     *
     * @param \ReflectionParameter $parameter
     *
     * @return mixed
     * @throws Exception
     */
    public function resolveNonClass($parameter)
    {
        // 有默认值则返回默认值
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        throw new Exception('参数无默认值');
    }

    public function offsetExists($key)
    {
        return isset($this->bindings[$key]);
    }

    /**
     * @param mixed $key
     * @return mixed|object
     * @throws Exception
     */
    public function offsetGet($key)
    {
        return $this->make($key);
    }

    public function offsetSet($key, $value)
    {
        if (!$value instanceof Closure) {
            $value = function () use ($value) {
                return $value;
            };
        }

        $this->bind($key, $value);
    }

    public function offsetUnset($key)
    {
        unset($this->bindings[$key], $this->instances[$key]);
    }
}
