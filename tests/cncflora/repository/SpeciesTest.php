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
        $t1->scientificName = 'Aphelandra longiflora S.Profice';
        $t1->scientificNameWithoutAuthorship = 'Aphelandra longiflora';
        $t1->scientificNameAuthorship = 'S.Profice';
        $t1->taxonomicStatus = 'accepted';

        $t2 = new \StdClass;
        $t2->metadata = new \StdClass;
        $t2->metadata->type = 'taxon';
        $t2->_id = '2';
        $t2->family = 'Acanthaceae';
        $t2->scientificName = 'Aphelandra longiflora2 S.Profice';
        $t2->scientificNameWithoutAuthorship = 'Aphelandra longiflora2';
        $t2->scientificNameAuthorship = 'S.Profice';
        $t2->taxonomicStatus = 'synonym';
        $t2->acceptedNameUsage = 'Aphelandra longiflora';

        $t3 = new \StdClass;
        $t3->metadata = new \StdClass;
        $t3->metadata->type = 'taxon';
        $t3->_id = '3';
        $t3->family = 'Acanthaceae';
        $t3->scientificName = 'Aphelandra espirito-stantensis S.Profice';
        $t3->scientificNameWithoutAuthorship = 'Aphelandra espirito-stantensis';
        $t3->scientificNameAuthorship = 'S.Profice';
        $t3->taxonomicStatus = 'accepted';

        $t4 = new \StdClass;
        $t4->metadata = new \StdClass;
        $t4->metadata->type = 'taxon';
        $t4->_id = '4';
        $t4->family = 'BROMELIACEAE';
        $t4->scientificName = 'Dickya whatevs Forzza';
        $t4->scientificNameWithoutAuthorship = 'Dickya whatevs';
        $t4->scientificNameAuthorship = 'Forzza';
        $t4->taxonomicStatus = 'accepted';

        $repo->put($t1);
        $repo->put($t2);
        $repo->put($t3);
        $repo->put($t4);
        sleep(2);
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
        $this->assertEquals(2,count($species));
        $this->assertEquals("Aphelandra longiflora",$species[1]->scientificName);
        $this->assertEquals("Aphelandra espirito-santensis",$species[0]->scientificName);

        $species  = $repo->getSpecies('NONEXISTENTFAMILY');
        $this->assertEmpty($species,'Species from a invalid family should come empty');
    }

    public function testSynonyns() {
        $repo = new Species;

        $syns = $repo->getSynonyms("Aphelandra longiflora");
        $this->assertEquals(1,count($syns));
        $this->assertEquals("Aphelandra longiflora2",$syns[0]->scientificName);
        $this->assertEquals("Aphelandra longiflora",$syns[0]->acceptedNameUsage);

        $syns = $repo->getSynonyms("Aphelandra espirito-santensis");
        $this->assertEmpty($syns);
    }

    public function testPermissions() {
        $this->markTestIncomplete();
    }

}


