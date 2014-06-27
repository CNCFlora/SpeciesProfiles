<?php

namespace cncflora\repository;

include_once 'vendor/autoload.php';

use cncflora\Utils;

class ProfilesTest extends \PHPUnit_Framework_TestCase {

    public function setup() {
        putenv("PHP_ENV=test");

        $this->user = new \StdClass;
        $this->user->name = "Foo";
        $this->user->email = "foo@bar.com";

        $repo = new Species;

        $t1 = new \StdClass;
        $t1->metadata = new \StdClass;
        $t1->metadata->type = 'taxon';
        $t1->_id = '1';
        $t1->family = 'Acanthaceae';
        $t1->scientificName = 'Aphelandra longiflora';
        $t1->scientificNameAuthorship = 'S.Profice';
        $t1->taxonomicStatus = 'accepted';

        $t2 = new \StdClass;
        $t2->metadata = new \StdClass;
        $t2->metadata->type = 'taxon';
        $t2->_id = '2';
        $t2->family = 'Acanthaceae';
        $t2->scientificName = 'Aphelandra longiflora2';
        $t2->scientificNameAuthorship = 'S.Profice';
        $t2->taxonomicStatus = 'synonym';

        $t3 = new \StdClass;
        $t3->metadata = new \StdClass;
        $t3->metadata->type = 'taxon';
        $t3->_id = '3';
        $t3->family = 'Acanthaceae';
        $t3->scientificName = 'Aphelandra espirito-stantensis';
        $t3->scientificNameAuthorship = 'S.Profice';
        $t3->taxonomicStatus = 'accepted';

        $t4 = new \StdClass;
        $t4->metadata = new \StdClass;
        $t4->metadata->type = 'taxon';
        $t4->_id = '4';
        $t4->family = 'BROMELIACEAE';
        $t4->scientificName = 'Dickya whatevs';
        $t4->scientificNameAuthorship = 'Forzza';
        $t4->taxonomicStatus = 'accepted';

        $repo->put($t1);
        $repo->put($t2);
        $repo->put($t3);
        $repo->put($t4);
        sleep(1);
    }

    public function tearDown() {
        $repo0 = new \cncflora\repository\Base;
        $all = $repo0->get("_all_docs");
        foreach($all->rows as $r) {
            $repo0->delete($r->id);
        }
    }

    /**
     * @expectedException Exception 
     */
    public function testMustHaveUser() {
        $repo = new Profiles();

        $taxons = (new Species)->getSpecies('ACANTHACEAE');
        $taxon  = $taxons[0];
        $profile = $repo->create($taxon);
    }

    public function testCRUD() {
        $repo = new Profiles($this->user);

        $taxons = (new Species)->getSpecies('ACANTHACEAE');
        $taxon  = $taxons[0];
        $profile = $repo->create($taxon);
        sleep(1);

        $this->assertNotNull($profile);
        $this->assertNotNull($profile->_id);
        $this->assertEquals($profile->taxon->family,$taxon->family);
        $this->assertEquals($profile->taxon->scientificName,$taxon->scientificName);

        $profilePersisted = $repo->get($profile->_id);

        $this->assertNotNull($profilePersisted);
        $this->assertEquals($profile,$profilePersisted);

        $profile->ecology = new \StdClass;
        $profile->ecology->resume = "Hello, World!";
        $repo->update($profile);
        sleep(1);


        $profilePersisted = $repo->get($profile->_id);
        $this->assertEquals($profilePersisted->ecology->resume,"Hello, World!");

        $repo->delete($profile);
    }

    public function testListByFamily() {
        $repo = new Profiles($this->user);

        $taxons = (new Species)->getSpecies('ACANTHACEAE');
        $taxon  = $taxons[0];
        $profile = $repo->create($taxon);
        sleep(1);

        $profileList = $repo->listByFamily("ACANTHACEAE");
        $this->assertNotEmpty($profileList);
        //$this->assertCount(2,$profileList);
        foreach($profileList as $p) {
            $this->assertEquals($p->taxon->family,"ACANTHACEAE");
        }

        foreach($profileList as $p) {
            $repo->delete($p);
        }
    }

    public function testLatestByTaxon() {
        $repo = new Profiles($this->user);

        $taxons = (new Species)->getSpecies('ACANTHACEAE');
        $taxon  = $taxons[0];

        $profile1 = $repo->create($taxon);
        sleep(1);
        $profile2 = $repo->create($taxon);
        sleep(1);

        $profileSpp = $repo->latestByTaxon($taxon->scientificName);
        $this->assertEquals($profile2,$profileSpp);

        $taxon  = $taxons[1];
        $profileSpp = $repo->latestByTaxon($taxon->scientificName);
        $this->assertNull($profileSpp);

        $repo->delete($profile1);
        $repo->delete($profile2);
    }

    public function testMetadata() {
        $this->markTestIncomplete();
    }

    public function testPermissions() {
        $this->markTestIncomplete();
    }
}
