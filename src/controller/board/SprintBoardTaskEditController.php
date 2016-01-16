<?php

final class SprintBoardTaskEditController extends ManiphestController {

  public function handleRequest(AphrontRequest $request) {
    return id(new SprintManiphestEditEngine())
        ->setController($this)
        ->addContextParameter('ungrippable')
        ->addContextParameter('responseType')
        ->addContextParameter('columnPHID')
        ->addContextParameter('order')
        ->buildResponse();
  }

}
