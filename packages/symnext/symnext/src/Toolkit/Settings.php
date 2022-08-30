<?php

namespace Symnext\Toolkit;

trait Settings
{
    #protected array $settings = [];

    public function get(string $key = null)
    {
        if (!$key) {
            $return = [];
            foreach (array_keys($this->settings) as $key) {
                $return[$key] = $this->getSetting($key);
            }
            return $return;
        } elseif (array_key_exists($key, $this->settings)) {
            return $this->getSetting($key);
        }
    }

    private function getSetting(string $key)
    {
        return $this->settings[$key]['value']
            ?? $this->settings[$key]['default_value']
            ?? null;
    }

    public function set(string $key, $value)
    {
        if (array_key_exists($key, $this->settings)) {
            $value = is_string($value) ? trim($value) : $value;
            $settings = $this->settings[$key];
            $type = $settings['type'] ?? 'string';
            if (gettype($value) === $type) {
                $values_allowed = $settings['values_allowed'] ?? null;
                if (is_array($values_allowed) and $type == 'string') {
                    $value = in_array($value, $values_allowed)
                        ? $value
                        : ($settings['default_value'] ?? $values_allowed[0]);
                }
                $this->settings[$key]['value'] = $value;
            }
        }
    }

    public function setFromArray(array $data)
    {
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $this->set($key, $value);
            }
        }
    }

    /**
     * Implementation of ArrayAccess::offsetExists()
     *
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->settings);
    }

    /**
     * Implementation of ArrayAccess::offsetGet()
     *
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Implementation of ArrayAccess::offsetSet()
     *
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        if (!is_string($offset)) return;
        $this->set($offset, $value);
    }

    /**
     * Implementation of ArrayAccess::offsetUnset()
     *
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
    }
}
