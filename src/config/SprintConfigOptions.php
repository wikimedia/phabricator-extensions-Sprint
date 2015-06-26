<?php

final class SprintConfigOptions
    extends PhabricatorApplicationConfigOptions {

  public function getName() {
    return pht('Sprint');
  }

  public function getDescription() {
    return pht('Configure Sprint.');
  }

  public function getFontIcon() {
    return 'fa-puzzle-piece';
  }

  public function getGroup() {
    return 'apps';
  }

  public function getOptions() {

    return array(
        $this->newOption('sprint.show-events-table', 'bool', true)
            ->setBoolOptions(
                array(
                    pht('Show Events Table'),
                    pht('Hide Events Table'),
                ))
            ->setSummary(pht('Show or Hide Events Table.'))
            ->setDescription(
                pht(
                    "The Events Table is optional".
                    "\n\n.")),
        $this->newOption('sprint.show-tasks-table', 'bool', true)
            ->setBoolOptions(
                array(
                    pht('Show Tasks Table'),
                    pht('Hide Tasks Table'),
                ))
            ->setSummary(pht('Show or Hide Tasks Table.'))
            ->setDescription(
                pht(
                    "The Tasks Table is optional".
                    "\n\n.")),
        $this->newOption('sprint.show-burndown', 'bool', true)
            ->setBoolOptions(
                array(
                    pht('Show Burndown Chart'),
                    pht('Hide Burndown Chart'),
                ))
            ->setSummary(pht('Show or Hide Burndown Chart.'))
            ->setDescription(
                pht(
                    "The Burndown Chart is optional".
                    "\n\n.")),
        $this->newOption('sprint.show-board-data', 'bool', true)
            ->setBoolOptions(
                array(
                    pht('Show Board Data'),
                    pht('Hide Board Data'),
                ))
            ->setSummary(pht('Show or Hide Board Data.'))
            ->setDescription(
                pht(
                    "Board Data is optional".
                    "\n\n.")),
        $this->newOption('sprint.show-pies', 'bool', true)
            ->setBoolOptions(
                array(
                    pht('Show Pie Charts'),
                    pht('Hide Pie Charts'),
                ))
            ->setSummary(pht('Show or Hide Pie Charts.'))
            ->setDescription(
                pht(
                    "The Pie Charts are optional".
                    "\n\n.")),
        $this->newOption('sprint.enable-sprint-board', 'bool', true)
            ->setBoolOptions(
                array(
                    pht('Enable Sprint Board'),
                    pht('Disble Sprint Board'),
                ))
            ->setSummary(pht('Enable or Disable Sprint Board.'))
            ->setDescription(
                pht(
                    "The Sprint Board is optional".
                    "\n\n.")),
    );
  }

}
