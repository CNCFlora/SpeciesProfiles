<?php

namespace cncflora;

include_once 'vendor/autoload.php';

use cncflora\Utils;

class UtilsTest extends \PHPUnit_Framework_TestCase {


    public function setup() {
        putenv("PHP_ENV=test");
        putenv("DB=cncflora_test");
        Utils::http_put(COUCHDB."/cncflora_test",[]);
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
        $this->assertEquals($cfg['ENV'],"test");
        $this->assertNotNull($cfg['COUCHDB']);
        $this->assertEquals($cfg['COUCHDB'],COUCHDB);
    }

    public function testHTTP() {
        $r = Utils::http_put(COUCHDB."/".DB."/foo",['foo'=>'bar','metadata'=>['type'=>'test']]);
        $r = Utils::http_get(COUCHDB."/".DB."/foo");
        $this->assertEquals($r->foo,'bar');
        $s = Utils::search('test','bar');
        $this->assertEquals($s[0]->foo,'bar');
        $r->foo = 'baz';
        Utils::http_put(COUCHDB."/".DB."/foo",$r);
        $r = Utils::http_get(COUCHDB."/".DB."/foo");
        $this->assertEquals($r->foo,'baz');
        $s = Utils::search('test','baz');
        $this->assertEquals($s[0]->foo,'baz');
        Utils::http_delete(COUCHDB."/".DB."/foo?rev=".$r->_rev);
    }

    public function testSchema() {
        $schema = Utils::schema();
        $this->assertNotNull($schema);
        $this->assertEquals($schema->properties->ecology->properties->habitats->items->enum[0],'1 Forest');
    }

}

