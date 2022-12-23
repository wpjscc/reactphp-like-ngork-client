<?php

namespace App\Output;

class StdoutStreamManager
{
    private static $manager;

    protected $stream;

    public function __construct($strem)
    {
        $this->stream = $strem;
    }

    /**
     * Undocumented function
     *
     * @return self
     */
    public static  function createManager()
    {
        if (self::$manager) {
            return self::$manager;
        }
        return self::$manager = new static(new \React\Stream\WritableResourceStream(STDOUT));
    }

    public function write($msg)
    {
        $this->stream->write($msg."\n");
    }

    public function end($msg = '')
    {
        $this->stream->end($msg);
    }
}