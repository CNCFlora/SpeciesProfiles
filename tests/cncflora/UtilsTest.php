<?php

namespace cncflora;

include_once 'vendor/autoload.php';
include_once 'tests/cncflora/error_handler.php';

use cncflora\Utils;

class UtilsTest extends \PHPUnit_Framework_TestCase {


    public static function setUpBeforeClass() {
        putenv("PHP_ENV=test");
        //Init variables
        Utils::init();
        set_error_handler('defaultErrorHandler');
        // Delete CouchDB
        try {
            Utils::http_delete(COUCHDB."/cncflora_test",[]);
        }
        catch (Exception $e){
            // Database doesn't exist, no need to delete it
        }
        // Delete ES
        try {
            Utils::http_delete(ELASTICSEARCH."/cncflora_test",[]);
        }
        catch (Exception $e){
            // Database doesn't exist, no need to delete it
        }

        Utils::http_put(COUCHDB."/cncflora_test",[]);

        // Wait until ES docker is up
        $r = NULL;
        $tries = 1;
        while ($r === NULL && $tries < 10) {
            sleep(5);
            $es_check = ELASTICSEARCH.'/_cluster/health?wait_for_status=yellow&timeout=50s';
            $r = Utils::http_get($es_check);
            $tries += 1;
        }
        if ($r === NULL){
            trigger_error("Couldn't get a green or yellow status from ElasticSearch.",
                          E_USER_ERROR);
        }
    }

    public static function tearDownAfterClass() {
        Utils::http_delete(COUCHDB."/cncflora_test",[]);
        Utils::http_delete(ELASTICSEARCH."/cncflora_test",[]);
    }

    public function tearDown() {
        $repo0 = new \cncflora\repository\Base;
        $all = $repo0->get("_all_docs");
        foreach($all->rows as $r) {
            $repo0->delete($r->id);
        }
    }

    public function testConfig() {
        $cfg = Utils::$config;
        //$this->assertEquals($cfg['ENV'],"test");
        $this->assertEquals(TEST, True);
        $this->assertEquals(DB, "cncflora_test");
        $this->assertNotNull($cfg['COUCHDB']);
        $this->assertEquals($cfg['COUCHDB'],COUCHDB);
    }

    public function testHTTP() {
        $r = Utils::http_put(COUCHDB."/".DB."/foo",['foo'=>'bar','metadata'=>['type'=>'test']]);
        $r = Utils::http_get(COUCHDB."/".DB."/foo");
        $this->assertEquals($r->foo,'bar');
        //Commented since ES insertion is done separatelly from CouchDB
        //$s = Utils::search('test','bar');
        //$this->assertEquals($s[0]->foo,'bar');
        $r->foo = 'baz';
        Utils::http_put(COUCHDB."/".DB."/foo",$r);
        $r = Utils::http_get(COUCHDB."/".DB."/foo");
        $this->assertEquals($r->foo,'baz');
        //$s = Utils::search('test','baz');
        //$this->assertEquals($s[0]->foo,'baz');
        Utils::http_delete(COUCHDB."/".DB."/foo?rev=".$r->_rev);
    }

    public function testSchema() {
        $schema = Utils::schema();
        $this->assertNotNull($schema);
        $this->assertEquals($schema->properties->ecology->properties->habitats->items->enum[0],'1 Forest');
    }

}

