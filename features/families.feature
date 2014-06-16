Feature: Listing families and species 

    Scenario: Open families listing
        Given I am on "/families"
        Then I should see "ACANTHACEAE"
        Then I should see "BROMELIACEAE"

    Scenario: Open a family 
        Given I am on "/family/ACANTHACEAE"
        Then I should see "Aphelandra longiflora"
        Then I should see "Aphelandra espirito-santensis"
        Then I should not see "Aphelandra longiflora2"
        Then I follow "Aphelandra longiflora"
        Then I should see "Aphelandra longiflora"
        And I should see "S.Profice"

    Scenario: Open a specie without a profile, not logged
        Given I am on "/specie/taxon:1"
        Then I should see "Aphelandra longiflora"
        And I should see "S.Profice"
        And I should see "Não há perfil cadastrado para essa espécie."
        And I should not see "Iniciar perfil"

    Scenario: Open a specie without a profile, logged no role
        Given I am on "/specie/taxon:1"
        When I login as "Diogo", "diogo@cncflora.net", "admin"
        Then I should see "Não há perfil"
        And I should not see "Iniciar perfil"

    Scenario: Open a specie without a profile, logged and role
        Given I am on "/specie/taxon:1"
        When I login as "Diogo", "diogo@cncflora.net", "admin,analyst"
        Then I should see "Não há perfil"
        And I should see "Iniciar perfil"
        Then I press "create-btn"

    Scenario: Open a specie with profile
        Given I am on "/specie/taxon:1"
        Then I should see "Aphelandra longiflora"
        And I should see "Aphelandra longiflora2"
        And I should see "Criador: Diogo"
        And I should see "Status: open"

