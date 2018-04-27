<?php declare(strict_types=1);

namespace Ellipse;

class Session
{
    /**
     * The reference to an array of session data.
     *
     * @var array
     */
    private $data;

    /**
     * Set up a session with the given ref to an array of session data.
     *
     * @param array $data
     */
    public function __construct(array &$data)
    {
        $this->data = &$data;
    }

    /**
     * Return whether the given key is set.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    /**
     * Return the value associated with the given key when set or the optional
     * given default value when it is not.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return $this->has($key) ? $this->data[$key] : $default;
    }

    /**
     * Associate the given key with the given value.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set(string $key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * Unset the given key.
     *
     * @param string $key
     * @return void
     */
    public function unset(string $key)
    {
        unset($this->data[$key]);
    }
}
