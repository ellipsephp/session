<?php declare(strict_types=1);

namespace Ellipse;

class Session
{
    /**
     * The flash namespace.
     *
     * @var string
     */
    const FLASH = '::flash::';

    /**
     * The reference to an array of session data.
     *
     * @var array
     */
    private $data;

    /**
     * The flashed data from the previous session.
     *
     * @var array
     */
    private $flash;

    /**
     * Set up a session with the given ref to an array of session data.
     *
     * @param array $data
     */
    public function __construct(array &$data)
    {
        $this->data = &$data;

        $this->flash = $this->data[self::FLASH] ?? [];

        unset($this->data[self::FLASH]);
    }

    /**
     * Return the session id.
     *
     * @return string
     */
    public function id(): string
    {
        return session_id();
    }

    /**
     * Regenerate the session id.
     *
     * @param bool $delete_old_session
     * @return void
     */
    public function regenerate_id(bool $delete_old_session = false)
    {
        return session_regenerate_id($delete_old_session);
    }

    /**
     * Return all the session data.
     *
     * @return array
     */
    public function all(): array
    {
        $data = array_merge($this->data, $this->data[self::FLASH] ?? []);

        unset($data[self::FLASH]);

        $previous = array_diff_key($this->flash, $data);

        return array_merge($data, $previous);
    }

    /**
     * Return whether the given key is set.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->flash[$key])
            || isset($this->data[$key])
            || isset($this->data[self::FLASH][$key]);
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
        return $this->data[$key]
            ?? $this->data[self::FLASH][$key]
            ?? $this->flash[$key]
            ?? $default;
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
        unset($this->data[self::FLASH][$key]);

        $this->data[$key] = $value;
    }

    /**
     * Associate the given key with the given value within the flash namespace.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function flash(string $key, $value)
    {
        unset($this->data[$key]);

        $this->data[self::FLASH][$key] = $value;
    }

    /**
     * Unset the given key.
     *
     * @param string $key
     * @return void
     */
    public function unset(string $key)
    {
        unset($this->flash[$key]);
        unset($this->data[$key]);
        unset($this->data[self::FLASH][$key]);
    }

    /**
     * Unset all keys.
     *
     * @return void
     */
    public function delete()
    {
        foreach (array_keys($this->data) as $key) {

            unset($this->data[$key]);

        }

        foreach (array_keys($this->flash) as $key) {

            unset($this->flash[$key]);

        }
    }
}
