Feature: Login and logout 

    Scenario: Login and logout
        Given I am on "/"
        Then I should see "Login"
        Then I should not see "Logout"
        Then I should not see "Workflow"

