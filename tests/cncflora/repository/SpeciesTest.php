<?php

namespace cncflora\repository ;

include_once 'vendor/autoload.php';

use cncflora\Utils;

class SpeciesTest extends \PHPUnit_Framework_TestCase {

    public function setup() {
        putenv("PHP_ENV=test");

        $repo = new Species;

        $t1 = new \StdClass;
        $t1->metadata = new \StdClass;
        $t1->metadata->type = 'taxon';
        $t1->_id = '1';
        $t1->family = 'Acanthaceae';
        $t1->scientificName = 'Aphelandra longiflora';
        $t1->taxonomicStatus = 'accepted';

        $t2 = new \StdClass;
        $t2->metadata = new \StdClass;
        $t2->metadata->type = 'taxon';
        $t2->_id = '2';
        $t2->family = 'Acanthaceae';
        $t2->scientificName = 'Aphelandra longiflora2';
        $t2->taxonomicStatus = 'synonym';

        $t3 = new \StdClass;
        $t3->metadata = new \StdClass;
        $t3->metadata->type = 'taxon';
        $t3->_id = '3';
        $t3->family = 'Acanthaceae';
        $t3->scientificName = 'Aphelandra espirito-stantensis';
        $t3->taxonomicStatus = 'accepted';

        $t4 = new \StdClass;
        $t4->metadata = new \StdClass;
        $t4->metadata->type = 'taxon';
        $t4->_id = '4';
        $t4->family = 'BROMELIACEAE';
        $t4->scientificName = 'Dickya whatevs';
        $t4->taxonomicStatus = 'accepted';

        $repo->put($t1);
        $repo->put($t2);
        $repo->put($t3);
        $repo->put($t4);
        sleep(1);
    }

    public function tearDown() {
        $repo = new Species;
        $repo->delete("1");
        $repo->delete("2");
        $repo->delete("3");
        $repo->delete("4");
    }

    public function testTaxons() {
        $repo = new Species;

        $families = $repo->getFamilies();
        $this->assertNotEmpty($families,'Families from Species should not come empty');
        foreach($families as $family) {
            $this->assertRegExp("/^[A-Z]+$/",$family,"Families should come as strings and all uppercase");
        }

        $species  = $repo->getSpecies('ACANTHACEAE');
        $this->assertNotEmpty($species,"Species from a valid family should come as a non-empty array");
        foreach($species as $spp) {
            $this->assertEquals($spp->family,'ACANTHACEAE','Return only species of provided family');
        }

        $species  = $repo->getSpecies('NONEXISTENTFAMILY');
        $this->assertEmpty($species,'Species from a invalid family should come empty');
    }

    public function testPermissions() {
        $this->markTestIncomplete();
    }

}


