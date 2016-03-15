<?php

namespace Dez\Collection;

class InvalidArgumentException extends \Exception
{
}

class OutOfRangeException extends \Exception
{
}

abstract class AbstractCollection implements \ArrayAccess, \IteratorAggregate, \Countable
{

    protected $items = [];
    protected $type = null;

    /**
     * AbstractCollection constructor.
     * @param array $items
     */
    public function __construct(array $items = [])
    {
        $this->addAll($items);
    }

    /**
     * @param $item
     * @return mixed
     */
    abstract public function add($item);

    /**
     * @param $item
     * @return mixed
     */
    abstract public function append($item);

    /**
     * @param $item
     * @return mixed
     */
    abstract public function prepend($item);

    /**
     * @param array $items
     */
    public function addAll(array $items)
    {
        if (count($items) > 0) {
            foreach ($items as $item) {
                $this->add($item);
            }
        }
    }

    /**
     * @return null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param null $type
     */
    public function setType($type = null)
    {
        $this->type = $type;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return ($this->count() == 0);
    }

    /**
     * @param callable $callback
     * @return $this
     */
    public function each(callable $callback)
    {
        if ($this->count() > 0) {
            foreach ($this->items as $key => $item) {
                $callback($key, $item);
            }
        }

        return $this;
    }

    /**
     * @param callable $callback
     * @return bool|mixed
     */
    public function findOne(callable $callback)
    {
        $index = $this->findIndex($callback);

        return 0 > $index ? false : $this->at($index);
    }

    /**
     * @param callable $callback
     * @return int
     */
    public function findIndex(callable $callback)
    {
        $index = -1;
        for ($i = 0, $c = $this->count(); $i < $c; $i++) {
            if ($callback($this->index($i))) {
                $index = $i;
                break;
            }
        }

        return $index;
    }

    /**
     * @return mixed
     */
    public function first()
    {
        return $this->at(0);
    }

    /**
     * @return mixed
     */
    public function last()
    {
        return $this->at($this->count() - 1);
    }

    /**
     * @param int $index
     * @return mixed
     */
    public function index($index = 0)
    {
        return $this->at($index);
    }

    /**
     * @param int $index
     * @return mixed
     * @throws InvalidArgumentException
     * @throws OutOfRangeException
     */
    public function at($index = 0)
    {
        $this->validateIndex($index);

        return $this->items[$index];
    }

    /**
     * @param $index
     * @throws InvalidArgumentException
     * @throws OutOfRangeException
     */
    protected function validateIndex($index)
    {
        if (!is_int($index)) {
            throw new InvalidArgumentException('Index must be integer');
        }

        if (0 > $index) {
            throw new InvalidArgumentException('Index must be zero or bigger');
        }

        if ($this->count() - 1 < $index) {
            throw new OutOfRangeException('Index is out of range. Max index is ' . ($this->count() - 1));
        }
    }

    /**
     * @return mixed
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * @param callable $callback
     * @return static
     */

    public function findAll(callable $callback)
    {
        $indexes = $this->findIndexes($callback);
        $collection = clone $this;
        $collection->removeAll(function () {
            return true;
        });
        foreach ($indexes as $index) {
            $collection->add($this->at($index));
        }

        return $collection;
    }

    /**
     * @param callable $callback
     * @return array
     */
    public function findIndexes(callable $callback)
    {
        $indexes = [];
        for ($i = 0, $c = $this->count(); $i < $c; $i++) {
            if ($callback($this->index($i))) {
                $indexes[] = $i;
            }
        }

        return $indexes;
    }

    /**
     * @param callable $callback
     * @return int
     */
    public function removeAll(callable $callback)
    {
        $removed = 0;
        while ($this->remove($callback)) {
            $removed++;
        }

        return $removed;
    }

    /**
     * @param callable $callback
     * @return bool
     */
    public function remove(callable $callback)
    {
        $index = $this->findIndex($callback);
        if (0 > $index) {
            return false;
        } else {
            $this->removeAt($index);

            return true;
        }
    }

    /**
     * @param int $index
     * @throws InvalidArgumentException
     * @throws OutOfRangeException
     */
    public function removeAt($index = 0)
    {
        $this->validateIndex($index);
        $leftPart = array_slice($this->items, 0, $index);
        $rightPart = array_slice($this->items, $index + 1);
        $this->items = array_merge($leftPart, $rightPart);
    }

    /**
     *
     */
    public function clear()
    {
        $this->items = [];
    }

    /**
     * @param callable $callback
     * @return static
     */

    public function sort(callable $callback)
    {
        usort($this->items, $callback);

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->items;
    }

    /**
     * @return mixed
     */
    public function toJSON()
    {
        return json_encode($this->items);
    }

    /**
     * @param mixed $index
     * @return bool
     */
    public function offsetExists($index)
    {
        return isset($this->items[$index]);
    }

    /**
     * @param mixed $index
     */
    public function offsetUnset($index)
    {
        $this->removeAt($index);
    }

    /**
     * @param mixed $index
     * @return mixed
     */
    public function offsetGet($index)
    {
        return $this->at($index);
    }

    /**
     * @param mixed $index
     * @param mixed $item
     */
    public function offsetSet($index, $item)
    {
        if (is_null($index)) {
            $this->items[] = $item;
        } else {
            $this->items[$index] = $item;
        }
    }

    /**
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->items);
    }

    /**
     * @param $item
     * @throws InvalidArgumentException
     */
    protected function validateItem($item)
    {
        if ($this->type != null && !is_a($item, $this->type)) {
            throw new InvalidArgumentException('Collection type must be: ' . $this->type . ' passed ' . gettype($item));
        }
    }

    /**
     * Transforms an under_scored_string to a camelCasedOne
     * @param string $underScoreString
     * @return string
     */
    protected function camelize($underScoreString)
    {
        return lcfirst(implode('', array_map('ucfirst', array_map('strtolower', explode('_', $underScoreString)))));
    }

    /**
     * Transforms a camelCasedString to an under_scored_one
     * @param string $cameled
     * @return string
     */
    protected function underscore($cameled)
    {
        return implode('_', array_map('strtolower',
            preg_split('/([A-Z]{1}[^A-Z]*)/', $cameled, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY)));
    }

}