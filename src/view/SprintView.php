<?php


abstract class SprintView extends AphrontView {
  public function renderReportFilters(array $tokens, $has_window, $request,
                                      $user) {

    $form = id(new AphrontFormView())
        ->setUser($user)
        ->appendControl(
            id(new AphrontFormTokenizerControl())
                ->setDatasource(new PhabricatorProjectDatasource())
                ->setLabel(pht('Project'))
                ->setLimit(1)
                ->setName('set_project')
                ->setValue(mpull($tokens, 'getPHID')));

    if ($has_window) {
      list($window_str, , $window_error) = $this->getWindow($request);
      $form
          ->appendChild(
              id(new AphrontFormTextControl())
                  ->setLabel(pht('Recently Means'))
                  ->setName('set_window')
                  ->setCaption(
                      pht('Configure the cutoff for the "Recently Closed" column.'))
                  ->setValue($window_str)
                  ->setError($window_error));
    }

    $form
        ->appendChild(
            id(new AphrontFormSubmitControl())
                ->setValue(pht('Filter By Project')));

    $filter = new AphrontListFilterView();
    $filter->appendChild($form);

    return $filter;
  }


  public function getWindow($request) {
    $window_str = $request->getStr('window', '12 AM 7 days ago');

    $error = null;

    // Do locale-aware parsing so that the user's timezone is assumed for
    // time windows like "3 PM", rather than assuming the server timezone.

    $window_epoch = PhabricatorTime::parseLocalTime($window_str, $this->user);
    if (!$window_epoch) {
      $error = 'Invalid';
      $window_epoch = time() - (60 * 60 * 24 * 7);
    }

    // If the time ends up in the future, convert it to the corresponding time
    // and equal distance in the past. This is so users can type "6 days" (which
    // means "6 days from now") and get the behavior of "6 days ago", rather
    // than no results (because the window epoch is in the future). This might
    // be a little confusing because it casues "tomorrow" to mean "yesterday"
    // and "2022" (or whatever) to mean "ten years ago", but these inputs are
    // nonsense anyway.

    if ($window_epoch > time()) {
      $window_epoch = time() - ($window_epoch - time());
    }

    return array($window_str, $window_epoch, $error);
  }

  public function buildFilter($request) {
    $handle = null;
    $project_phid = $request->getStr('project');
    if ($project_phid) {
      $phids = array($project_phid);
      $handle = $this->getProjectHandle ($phids, $project_phid, $request);
    }
    $tokens = array();
    if ($handle) {
      $tokens = $this->getTokens($handle);
    }
    $filter = $this->renderReportFilters($tokens, $has_window = false,
        $request, $this->user);
    return $filter;
  }

  private function getTokens($handle) {
    $tokens = array($handle);
    return $tokens;
  }

  public function getProjectHandle($phids, $project_phid, $request) {
    $query = id(new SprintQuery())
        ->setPHID($project_phid);

    $handles = $query->getViewerHandles($request, $phids);
    $handle = $handles[$project_phid];
    return $handle;
  }
}
