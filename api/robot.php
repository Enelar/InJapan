<?php

class robot extends api
{
  protected function Reserve()
  {
    for ($i = 0; $i < 20; $i++)
    {
      if ($this('api', 'urlparse')->ParseNext() == 'no tasks')
        break;
      sleep(1);
    }
  }
}