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
        if(defined('COUCHDB_USER') && strlen(COUCHDB_USER) >= 1) {
            $this->couchdb = new \Nano\Nano('http://'.COUCHDB_USER.':'.COUCHDB_PASS.'@'.COUCHDB_HOST.':'.COUCHDB_PORT);
        } else {
            $this->couchdb = new \Nano\Nano('http://'.COUCHDB_HOST.':'.COUCHDB_PORT);
        }
        if(defined('TEST')) {
            $this->db = $this->couchdb->db->use(COUCHDB_BASE."_test");
        } else {
            $this->db = $this->couchdb->db->use(COUCHDB_BASE);
        }
    }
}
