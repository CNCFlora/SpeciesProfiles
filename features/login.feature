Feature: Login and logout 

    Scenario: Non user should not see certain links
        Given I am on "/"
        Then I should see "Login"
        And I should not see "Logout"
        And I should not see "Workflow"

    Scenario: I can login
        Given I am on "/"
        Then I login as "Diogo", "diogo@diogok.net", "admin"
        Then I should see "Logout"
        And I should see "Workflow"
        And I should not see "Login"
        Then I logout

