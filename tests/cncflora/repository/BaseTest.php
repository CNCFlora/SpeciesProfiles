<?php

namespace cncflora\repository ;

include_once 'vendor/autoload.php';

use cncflora\Utils;

class Repo extends Base {
}

class BaseTest extends \PHPUnit_Framework_TestCase {

    public function setup() {
        putenv("PHP_ENV=test");
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
        sleep(1);
        $s =$repo->search("test","baz");
        $this->assertEquals($s[0]->foo,'baz');
        $repo->delete("1");
    }

    public function testMetalog() {
        $this->markTestIncomplete();
    }

}
