<?php

namespace cncflora;

include_once 'vendor/autoload.php';

use cncflora\Utils;

class UtilsTest extends \PHPUnit_Framework_TestCase {

    public function setup() {
        putenv("PHP_ENV=test");
    }

    public function testConfig() {
        $cfg = Utils::$config;
        $this->assertEquals($cfg['ENV'],"test");
        $this->assertNotNull($cfg['DATAHUB_URL']);
        $this->assertEquals($cfg['DATAHUB_URL'],DATAHUB_URL);
    }

    public function testHTTP() {
        Utils::http_put(DATAHUB_URL."/".DB."/foo",['foo'=>'bar','metadata'=>['type'=>'test']]);
        $r = Utils::http_get(DATAHUB_URL."/".DB."/foo");
        $this->assertEquals($r->foo,'bar');
        sleep(1);
        $s = Utils::search('test','bar');
        $this->assertEquals($s[0]->foo,'bar');
        $r->foo = 'baz';
        Utils::http_put(DATAHUB_URL."/".DB."/foo",$r);
        $r = Utils::http_get(DATAHUB_URL."/".DB."/foo");
        $this->assertEquals($r->foo,'baz');
        sleep(1);
        $s = Utils::search('test','baz');
        $this->assertEquals($s[0]->foo,'baz');
        Utils::http_delete(DATAHUB_URL."/".DB."/foo?rev=".$r->_rev);
    }

    public function testSchema() {
        $schema = Utils::schema();
        $this->assertNotNull($schema);
        $this->assertEquals($schema->properties->ecology->properties->habitats->items->enum[0],'1 Forest');
    }

}

