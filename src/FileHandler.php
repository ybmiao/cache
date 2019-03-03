<?php

namespace Mix\Cache;

use Mix\Core\Component\AbstractComponent;
use Mix\Helper\FileSystemHelper;

/**
 * Class FileHandler
 * @package Mix\Cache
 * @author LIUJIAN <coder.keda@gmail.com>
 */
class FileHandler extends AbstractComponent implements HandlerInterface
{

    /**
     * 缓存目录
     * @var string
     */
    public $dir = 'cache';

    /**
     * 分区
     * @var int
     */
    public $partitions = 64;

    /**
     * 获取缓存
     * @param $key
     * @param null $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        $filename = $this->getCacheFileName($key);
        $data     = @file_get_contents($filename);
        if (empty($data)) {
            return $default;
        }
        $data = unserialize($data);
        if (!is_array($data) || count($data) !== 2) {
            return $default;
        }
        list($value, $expire) = $data;
        if ($expire > 0 && $expire < time()) {
            $this->delete($key);
            return $default;
        }
        return $value;
    }

    /**
     * 设置缓存
     * @param $key
     * @param $value
     * @param null $ttl
     * @return bool
     */
    public function set($key, $value, $ttl = null)
    {
        $filename = $this->getCacheFileName($key);
        $expire   = is_null($ttl) ? 0 : time() + $ttl;
        $data     = [
            $value,
            $expire,
        ];
        $bytes    = file_put_contents($filename, serialize($data), FILE_APPEND | LOCK_EX);
        return $bytes ? true : false;
    }

    /**
     * 删除缓存
     * @param $key
     * @return bool
     */
    public function delete($key)
    {
        $filename = $this->getCacheFileName($key);
        return @unlink($filename);
    }

    /**
     * 清除缓存
     * @return bool
     */
    public function clear()
    {
        $dir = $this->getCacheDir();
        return FileSystemHelper::deleteFolder($dir);
    }

    /**
     * 判断缓存是否存在
     * @param $key
     * @return bool
     */
    public function has($key)
    {
        $value = $this->get($key);
        return is_null($value) ? false : true;
    }

    /**
     * 获取缓存目录
     * @return string
     */
    protected function getCacheDir()
    {
        $cacheDir = $this->dir;
        if (!FileSystemHelper::isAbsolute($cacheDir)) {
            $cacheDir = \Mix::$app->getRuntimePath() . DIRECTORY_SEPARATOR . $this->dir;
        }
        return $cacheDir;
    }

    /**
     * 获取缓存文件
     * @param $key
     * @return string
     */
    protected function getCacheFileName($key)
    {
        $dir      = $this->getCacheDir();
        $subDir   = crc32($key) / $this->partitions;
        $name     = md5($key);
        $filename = $dir . DIRECTORY_SEPARATOR . $subDir . DIRECTORY_SEPARATOR . $name;
        return $filename;
    }

}
