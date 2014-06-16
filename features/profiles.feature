Feature: Open and edit profiles

    Scenario: Open a specie without a profile, can not create
        Given I am on "/specie/taxon:1"
        When I login as "Diogo", "diogo@cncflora.net", "admin"
        Then I should see "Não há perfil"
        And I should not see "Iniciar perfil"

    Scenario: Open a specie without a profile, logged and role, 
        Given I am on "/specie/taxon:1"
        When I login as "Diogo", "diogo@cncflora.net", "admin,analyst"
        Then I should see "Não há perfil"
        And I should see "Iniciar perfil"
        
    Scenario: Can create profile
        Given I am on "/specie/taxon:1"
        When I login as "Diogo", "diogo@cncflora.net", "admin,analyst"
        Then I press "create-btn"
        Then I should see "Aphelandra longiflora"
        And I should see "Criador: Diogo"
        And I should see "Status: open"

    Scenario: Open a specie with profile, but can not edit with no login
        Given I am on "/specie/taxon:1"
        Then I should see "Aphelandra longiflora"
        And I should see "Aphelandra longiflora2"
        And I should see "Criador: Diogo"
        And I should see "Status: open"
        And I should not see "Editar"

    Scenario: Open a specie with profile can not edit
        Given I am on "/specie/taxon:1"
        When I login as "Diogo", "diogo@cncflora.net", "admin,analyst"
        Then I should see "Aphelandra longiflora"
        And I should not see "Editar"

    Scenario: Can edit a specie
        Given I am on "/specie/taxon:1"
        When I login as "Bruno", "bruno@cncflora.net", "admin,analyst", "ACANTHACEAE"
        Then I should see "Aphelandra longiflora"
        And I should see "Editar"

    Scenario: Edit a profile, changes and metadata
        Given I am on "/specie/taxon:1"
        When I login as "Bruno", "bruno@cncflora.net", "admin,analyst", "ACANTHACEAE"
        And I follow "Editar"
        Then I should see "Contributor: [ Bruno ]; Diogo"

