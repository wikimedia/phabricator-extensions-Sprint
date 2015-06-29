<?php

final class SprintHandleIconView extends AphrontTagView {

  const SPRITE_TOKENS = 'tokens';
  const SPRITE_LOGIN = 'login';
  const SPRITE_PROJECTS = 'projects';

  const HEAD_SMALL = 'phuihead-small';
  const HEAD_MEDIUM = 'phuihead-medium';

  private $href = null;
  private $image;
  private $text;
  private $headSize = null;

  private $spriteIcon;
  private $spriteSheet;
  private $iconFont;
  private $iconColor;
  private $iconStyle;

  public function setHref($href) {
    $this->href = $href;
    return $this;
  }

  public function setIconStyle($style) {
    $this->iconStyle = $style;
    return $this;
  }

  public function setImage($image) {
    $this->image = $image;
    return $this;
  }

  public function setText($text) {
    $this->text = $text;
    return $this;
  }

  public function setHeadSize($size) {
    $this->headSize = $size;
    return $this;
  }

  public function setSpriteIcon($sprite) {
    $this->spriteIcon = $sprite;
    return $this;
  }

  public function setSpriteSheet($sheet) {
    $this->spriteSheet = $sheet;
    return $this;
  }

  public function setIconFont($icon, $color = null) {
    $this->iconFont = $icon;
    $this->iconColor = $color;
    return $this;
  }

  protected function getTagName() {
    $tag = 'span';
    if ($this->href) {
      $tag = 'a';
    }
    return $tag;
  }

  protected function getTagAttributes() {
    $classes = array();
    $classes[] = 'phui-object-item-handle-icon';

    return array(
        'href' => $this->href,
        'style' => $this->iconStyle,
        'aural' => false,
        'class' => $classes,
    );
  }

}
