<?php

class robot extends api
{
  protected function Reserve()
  {
    $urlparse = $this('api', 'urlparse');
    for ($i = 0; $i < 20; $i++)
    {
      if ($urlparse->ParseNext() == 'no tasks')
        break;
      sleep(1);
    }
  }

  protected function ScanAuctions()
  {
    $monitor = $this('api', 'monitor');
    for ($i = 0; $i < 20; $i++)
    {
      if ($monitor->ScanNext() == 'no tasks')
      {
        echo "No tasks";
        break;
      }
      sleep(1);
    }
  }

  protected function DailyRescan()
  {
    $categories = db::Query("SELECT * FROM track");
    $urlfind = $this('api', 'urlfind');
    foreach ($categories as $category)
    {
      $url = "https://injapan.ru/category/{$category->category}/currency-RUR/mode-1/store-private/condition-used/page-1/sort-enddate/order-ascending.html";
      $urlfind->QueryUrl($url, 'daily rescan');
    }
  }
}