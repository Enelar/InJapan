<?php

class main extends api
{
  protected function Reserve()
  {
    unset($this->addons['result']);
    return
    [
      'design' => 'main/body',
      'script' =>
      [
        '/engine/url_click.js',
      ],
    ];
  }

  protected function Home()
  {

  }
}