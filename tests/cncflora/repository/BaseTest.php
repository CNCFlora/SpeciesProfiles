<?php

namespace cncflora\repository ;

include_once 'vendor/autoload.php';
include_once 'tests/cncflora/error_handler.php';

use cncflora\Utils;

class Repo extends Base {
}

class BaseTest extends \PHPUnit_Framework_TestCase {

    public static function setUpBeforeClass() {
        putenv("PHP_ENV=test");
        putenv("DB=cncflora_test");
        //Init variables
        Utils::init();
        set_error_handler('defaultErrorHandler');
        try {
            Utils::http_delete(COUCHDB."/cncflora_test",[]);
        }
        catch (Exception $e){
            // Database doesn't exist, no need to delete it
        }
        Utils::http_put(COUCHDB."/cncflora_test",[]);

        $repo0 = new \cncflora\repository\Base;
        $all = $repo0->get("_all_docs");
        foreach($all->rows as $r) {
          $repo0->delete($r->id);
        }
    }

    public static function tearDownAfterClass() {
        Utils::http_delete(COUCHDB."/cncflora_test",[]);
    }

    public function tearDown() {
        $repo0 = new \cncflora\repository\Base;
        $all = $repo0->get("_all_docs");
        foreach($all->rows as $r) {
            $repo0->delete($r->id);
        }
    }

    public function testCRUD() {
        $repo = new Species;
        $r = new \StdClass;
        $r->metadata = new \StdClass;
        $r->metadata->type = 'test';
        $r->_id = '1';
        $r->foo = 'bar';
        $p = $repo->put($r);
        $r = $repo->get("1");
        $this->assertEquals($r->foo,'bar');
        $r->foo='baz';
        $repo->put($r);
        $s =$repo->search("test","baz");
        $this->assertEquals($s[0]->foo,'baz');
        $repo->delete("1");
    }

    public function testMetalog() {
        $this->markTestIncomplete();
    }

}
