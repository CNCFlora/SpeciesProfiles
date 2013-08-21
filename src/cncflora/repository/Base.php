<?php

namespace cncflora\repository;

use cncflora\Utils ;

abstract class Base {
    public $user = null;
    public $couchdb = null;
    public $db = null;

    public function __construct($user=null) {
        Utils::config();
        $this->user = $user;
        $this->couchdb = new \Nano\Nano('http://'.COUCH_USER.":".COUCH_PASS."@".COUCH_HOST.":".COUCH_PORT);
        if(defined('TEST')) {
            $this->db = $this->couchdb->db->use(COUCH_BASE."_test");
        } else {
            $this->db = $this->couchdb->db->use(COUCH_BASE);
        }
    }
}
