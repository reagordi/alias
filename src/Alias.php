<?php
/**
 * Reagordi Component
 *
 * @link https://reagordi.com/
 * @copyright Copyright (c) 2020 - 2022 Universe Group
 * @license https://dev.reagordi.com/license
 */

declare(strict_types=1);

namespace Reagordi\Component\Alias;

use Reagordi\Contracts\Foundation\Component;
use Psr\SimpleCache\CacheInterface;
use DateInterval;

/**
 * Alias - this is a class for registering path aliases
 *
 * @author Sergej Rufov <support@reagordi.com>
 * @since 1.0
 */
class Alias extends Component implements CacheInterface
{
    /**
     * @var array registered path aliases
     * @see get()
     * @see set()
     */
    private array $_alias = [];

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->has($key) ? $this->_alias[$key]: $default;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        if (strncmp($key, '@', 1)) {
            $key = '@' . $key;
        }
        $pos = strpos($key, '/');
        $root = $pos === false ? $key : substr($key, 0, $pos);
        if ($value !== null) {
            $value = strncmp($value, '@', 1) ? rtrim($value, '\\/') : $this->get($value);
            if (!isset($this->_alias[$root])) {
                if ($pos === false) {
                    $this->_alias[$root] = $value;
                } else {
                    $this->_alias[$root] = [$key => $value];
                }
            } elseif (is_string($this->_alias[$root])) {
                if ($pos === false) {
                    $this->_alias[$root] = $value;
                } else {
                    $this->_alias[$root] = [
                        $key => $value,
                        $root => $this->_alias[$root],
                    ];
                }
            } else {
                $this->_alias[$root][$key] = $value;
                krsort($this->_alias[$root]);
            }
        } elseif (isset($this->_alias[$root])) {
            if (is_array($this->_alias[$root])) {
                unset($this->_alias[$root][$key]);
            } elseif ($pos === false) {
                unset($this->_alias[$root]);
            }
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        unset($this->_alias[$key]);
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        $this->_alias = [];
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $data = [];
        foreach ($keys as $key) {
            $data[$key] = $this->get($key, $default);
        }
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        return isset($this->_alias[$key]);
    }
}
