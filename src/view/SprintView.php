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

    $form
        ->appendChild(
            id(new AphrontFormSubmitControl())
                ->setValue(pht('Filter By Project')));

    $filter = new AphrontListFilterView();
    $filter->appendChild($form);

    return $filter;
  }
}