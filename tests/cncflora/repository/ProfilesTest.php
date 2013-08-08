<?php

namespace cncflora\repository;

include_once 'vendor/autoload.php';

class ProfilesTest extends \PHPUnit_Framework_TestCase {

    public function setup() {
        $this->user = new \StdClass;
        $this->user->name = "Foo";
        $this->user->email = "foo@bar.com";
    }

    public function testProfiles() {
        $repo = new Profiles();
        $this->assertEquals($repo,$repo);
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

        $profilePersisted = $repo->get($profile->_id);
        $this->assertEquals($profilePersisted->ecology->resume,"Hello, World!");

        $repo->delete($profile);
        $profilePersisted = $repo->get($profile->_id);
        $this->assertNull($profilePersisted);
    }

    public function testListByFamily() {
        $repo = new Profiles($this->user);

        $taxons = (new Species)->getSpecies('ACANTHACEAE');
        $taxon  = $taxons[0];
        $profile = $repo->create($taxon);

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

        $profileSpp = $repo->latestByTaxon($taxon->_id);
        $this->assertEquals($profile2,$profileSpp);

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
