<?php

final class SprintBoardCardToken extends Phobject {
  private $viewer;

  public function setViewer ($viewer) {
    $this->viewer = $viewer;
    return $this;
  }

  public function getTokensForTask($task) {
    $tokens_given = id(new PhabricatorTokenGivenQuery())
        ->setViewer($this->viewer)
        ->withObjectPHIDs(array($task->getPHID()))
        ->execute();

    if (!$tokens_given) {
      return null;
    }

    $tokens = id(new PhabricatorTokenQuery())
        ->setViewer($this->viewer)
        ->withPHIDs(mpull($tokens_given, 'getTokenPHID'))
        ->execute();
    $tokens = mpull($tokens, null, 'getPHID');

    $author_phids = mpull($tokens_given, 'getAuthorPHID');
    $handles = id(new PhabricatorHandleQuery())
        ->setViewer($this->viewer)
        ->withPHIDs($author_phids)
        ->execute();

    Javelin::initBehavior('phabricator-tooltips');

    $list = array();
    foreach ($tokens_given as $token_given) {
      if (!idx($tokens, $token_given->getTokenPHID())) {
        continue;
      }

      $token = $tokens[$token_given->getTokenPHID()];
      $aural = javelin_tag(
          'span',
          array(
              'aural' => true,
          ),
          pht(
              '"%s" token, awarded by %s.',
              $token->getName(),
              $handles[$token_given->getAuthorPHID()]->getName()));

      $tokenslabel = 'Tokens:';
      $tokensvalue = phutil_tag(
          'dd',
          array(
              'class' => 'phui-card-list-value',
          ),
          array($token->renderIcon(), ' '));

      $tokenskey = phutil_tag(
          'dt',
          array(
              'class' => 'phui-card-list-key',
          ),
          array($tokenslabel, ' '));

      $list[] = javelin_tag(
          'dl',
          array(
              'sigil' => 'has-tooltip',
              'class' => 'token-icon',
              'meta' => array(
                  'tip' => $handles[$token_given->getAuthorPHID()]->getName(),
              ),
          ),
          array(
              $aural,
              $tokenskey,
              $tokensvalue,
          ));
    }
    return $list;
  }
}
