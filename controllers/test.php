<?php

class test extends Controller {
    function __construct() {
        parent::__construct();
    }

    function index() {
        print_r("####################################");
        print_r($content);
        die();
    }
}