Feature: Login and logout 

    Scenario: Non user should not see certain links
        Given I am on "/"
        Then I logout
        Then I should see "Login"
        And I should not see "Recortes"
        And I should not see "Workflow"
        And I should see "Faça login para começar."

    Scenario: I can login
        Given I am on "/"
        Then I login as "Diogo", "diogo@diogok.net", "cncflora_test", "admin"
        And I should see "Bem vindo, Diogo."
        And I should see "Logout"
        And I should see "Recortes"
        And I should not see "Workflow"
        And I should see "CNCFLORA"
        And I should see "CNCFLORA TEST"
        And I follow "CNCFLORA TEST"
        Then I should see "Workflow"
        Then I should see "Familias"
        Then I should see "Recorte: CNCFLORA TEST"

