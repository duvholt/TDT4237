<?php

namespace tdt4237\webapp\models;

class Request
{

    private $ip;
    private $time;

    public function __construct($ip)
    {
        $this->ip = $ip;
        $this->time=time();
    }

    public function getIp(){
      return $this->ip;
    }
    public function getTime(){
        return $this->time;
    }
}
