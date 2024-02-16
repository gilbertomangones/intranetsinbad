<?php

namespace Drupal\custom\biblored_module\Plugin\Block;

use Drupal\Core\Block\BlockBase;

class GraficaBlock extends BlockBase {

  public function build() {
    return array(
      '#markup' => $this->t("Hello, World!"),
    );
  }

}