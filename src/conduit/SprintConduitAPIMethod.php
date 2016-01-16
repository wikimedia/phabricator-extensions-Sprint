<?php

abstract class SprintConduitAPIMethod extends ConduitAPIMethod {

  final public function getApplication() {
    return PhabricatorApplication::getByClass('SprintApplication');
  }

  protected function buildProjectInfoDictionary(PhabricatorProject $project) {
    $results = $this->buildProjectInfoDictionaries(array($project));
    return idx($results, $project->getPHID());
  }

  protected function buildSprintInfoDictionary(PhabricatorProject $project, $user) {
    $result = array();
    $query = id(new SprintQuery())
        ->setViewer($user);
    $dates = $this->getStartEndDates($query, $project);
    $member_phids = $project->getMemberPHIDs();
    $member_phids = array_values($member_phids);
    $project_slugs = $project->getSlugs();
    $project_slugs = array_values(mpull($project_slugs, 'getSlug'));
    $issprint = $this->isSprint($project->getPHID());
    $project_icon = substr($project->getIcon(), 3);

    $result[$project->getPHID()] = array(
        'id'               => $project->getID(),
        'phid'             => $project->getPHID(),
        'name'             => $project->getName(),
        'profileImagePHID' => $project->getProfileImagePHID(),
        'icon'             => $project_icon,
        'color'            => $project->getColor(),
        'members'          => $member_phids,
        'slugs'            => $project_slugs,
        'issprint'         => $issprint,
        'startDate'        => $dates['start'],
        'endDate'          => $dates['end'],
        'dateCreated'      => $project->getDateCreated(),
        'dateModified'     => $project->getDateModified(),
    );
    return $result;
  }

  protected function buildProjectInfoDictionaries(array $projects) {
    assert_instances_of($projects, 'PhabricatorProject');
    if (empty($projects)) {
      return array();
    }

    $result = array();
    foreach ($projects as $project) {

      $member_phids = $project->getMemberPHIDs();
      $member_phids = array_values($member_phids);
      $project_slugs = $project->getSlugs();
      $project_slugs = array_values(mpull($project_slugs, 'getSlug'));
      $issprint = $this->isSprint($project->getPHID());
      $project_icon = substr($project->getIcon(), 3);

      $result[$project->getPHID()] = array(
        'id'               => $project->getID(),
        'phid'             => $project->getPHID(),
        'name'             => $project->getName(),
        'profileImagePHID' => $project->getProfileImagePHID(),
        'icon'             => $project_icon,
        'color'            => $project->getColor(),
        'members'          => $member_phids,
        'slugs'            => $project_slugs,
        'issprint'         => $issprint,
        'dateCreated'      => $project->getDateCreated(),
        'dateModified'     => $project->getDateModified(),
      );
    }

    return $result;
  }

  protected function isSprint($project_phid) {
    $validator = new SprintValidator();
    $issprint = call_user_func(array($validator, 'checkForSprint'),
        array($validator, 'isSprint'), $project_phid);
    return $issprint;
  }

  protected function getStartEndDates($query, $project) {
    $query->setProject($project);
    $field_list = $query->getCustomFieldList();
    $aux_fields = $query->getAuxFields($field_list);
    $dates['start'] = $query->getStartDate($aux_fields);
    $dates['end'] = $query->getEndDate($aux_fields);
    return $dates;
  }
}
