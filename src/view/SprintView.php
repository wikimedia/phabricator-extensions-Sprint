<?php


abstract class SprintView extends AphrontView
{
  public function renderReportFilters(array $tokens) {

    $form = id(new AphrontFormView())
        ->setUser($this->user)
        ->appendChild(
            id(new AphrontFormTokenizerControl())
                ->setDatasource(new PhabricatorProjectDatasource())
                ->setLabel(pht('Project'))
                ->setLimit(1)
                ->setName('set_project')
                ->setValue($tokens));

    //if ($has_window) {
    //  list($window_str, $ignored, $window_error) = $this->getWindow();
    //  $form
    //      ->appendChild(
    //          id(new AphrontFormTextControl())
    //              ->setLabel(pht('Recently Means'))
    //              ->setName('set_window')
    //              ->setCaption(
    //                  pht('Configure the cutoff for the "Recently Closed" column.'))
    //              ->setValue($window_str)
    //              ->setError($window_error));
    // }

    $form
        ->appendChild(
            id(new AphrontFormSubmitControl())
                ->setValue(pht('Filter By Project')));

    $filter = new AphrontListFilterView();
    $filter->appendChild($form);

    return $filter;
  }
}