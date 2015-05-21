<?php

class main extends api
{
  protected function Reserve()
  {
    unset($this->addons['result']);
    return
    [
      'design' => 'main/body',
    ];
  }

  protected function Home()
  {

  }
}