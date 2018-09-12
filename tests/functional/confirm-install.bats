#!/usr/bin/env bats

#
# confirm-install.bats
#
# Ensure that Terminus and the Composer plugin have been installed correctly
#

@test "confirm terminus version" {
  terminus --version
}

@test "get help on alpha:aliases command" {
  run terminus help alpha:aliases
  [[ $output == *"Write Drush alias files"* ]]
  [ "$status" -eq 0 ]
}
