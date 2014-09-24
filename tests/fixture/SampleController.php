<?php

class SampleController
{
    private $outputPrefix;

    public function __construct($outputPrefix='')
    {
        $this->outputPrefix = $outputPrefix;
    }

    public function get($id, $idd=null, $iddd=null)
    {

        return $this->outputPrefix.'get-'.implode('-', array_filter(array($id, $idd, $iddd)));
    }

    public function cget($id=null, $idd=null)
    {
        return $this->outputPrefix.'cget'.implode('-', array_filter(array($id, $idd)));
    }

    public function post($id=null, $idd=null)
    {
        return $this->outputPrefix.'post'.implode('-', array_filter(array($id, $idd)));
    }

    public function put($id, $idd=null, $iddd=null)
    {
        return $this->outputPrefix.'put-'.implode('-', array_filter(array($id, $idd, $iddd)));;
    }

    public function patch($id, $idd=null, $iddd=null)
    {
        return $this->outputPrefix.'patch-'.implode('-', array_filter(array($id, $idd, $iddd)));;
    }

    public function delete($id, $idd=null, $iddd=null)
    {
        return $this->outputPrefix.'delete-'.implode('-', array_filter(array($id, $idd, $iddd)));;
    }
}
