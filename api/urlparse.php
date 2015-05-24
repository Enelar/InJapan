<?php

class urlparse extends api
{
  private function GetNext()
  {
    $res = db::Query("
      WITH helper AS
      (
        SELECT * FROM to_parse WHERE \"lock\" IS NULL OR now() - \"lock\" > '10 min'::INTERVAL LIMIT 1
      ) UPDATE to_parse as a SET lock=now() WHERE id=(SELECT id FROM helper)
        RETURNING (SELECT url FROM urls as b WHERE a.id=b.id)
      ", [], true);
    return db::Query("SELECT * FROM urls WHERE url=$1", [$res->url], true);
  }

  protected function ParseNext()
  {
    do
    {
      $row = $this->GetNext();
      if (!$row())
        return "no tasks";
    } while (strpos($row->url, "https://injapan.ru/auction/") !== false);

    //var_dump($row->url);
    $category = $row->url; //$this->GetCategoryId($row->url);
    $urls = ["https://injapan.ru/category/2084016521/currency-RUR/mode-1/store-private/condition-used/page-1/sort-enddate/order-ascending.html"];
    $urls = $this->GetUrls($row->url);
    $stripped = $this->StripSearch($category, $urls);

    //var_dump($stripped);

    $ret = [];
    $module = $this('api', 'urlfind');
    foreach ($stripped as list($parse, $url))
    {
      if ($parse)
        $id = $module->MeetAndParse($url);
      else
        $id = $module->MeetUrl($url);
      $module->AddRelation($row->id, $id);

      $ret[$id] = $url;
    }

    db::Query("DELETE FROM to_parse WHERE id=$1", [$row->id]);
    return $stripped;
  }

  private function GetUrls($url)
  {
    $escaped_url = escapeshellarg($url);
    $command = "lynx -dump -listonly $escaped_url | awk  '{print $2}'";
    var_dump($command);
    exec($command, $result);
    //var_dump($result);
    return $result;
  }

  private function StripSearch($category, $array)
  {
    $ret = [];

    foreach ($array as $url)
      if (strpos($url, "https://injapan.ru/auction/") !== false)
        $ret[] = [false, $url];
      else if ($this->IsSameCategory($url, $category))
        $ret[] = [true, $url];

    //var_dump($ret);
    return $ret;
  }

  private function IsSameCategory($url, $base_url)
  {
    $base_array = $this->PregMatch($base_url, 'http.*?category.(\d*)(.*?)page-(\d+).(.*?).html');
    $array = $this->PregMatch($url, 'http.*?category.(\d*)(.*?)page-(\d+).(.*?).html');

    if ($base_array[0] != $array[0])
      return false; // category id not equal
    if ($base_array[1] != $array[1])
      return false; // first part of search
    if ($base_array[3] != $array[3])
      return false;
    return true;
  }

  private function GetCategoryId($url)
  {
    $array = $this->PregMatch($url, '.*category.(\d+)');
    return (int)$array[0];
  }

  private function PregMatch($url, $pattern)
  {
    $ret = [];
    $res = preg_match('/'.$pattern.'/', $url, $ret);
//    var_dump(["pregmatch = $res", [$pattern, $url, $ret]]);
    array_shift($ret);
    return $ret;
  }

  protected function ParseStats()
  {
    return
    [
      "data" =>
      [
        "tasks" => db::Query("SELECT count(*) FROM to_parse WHERE \"lock\" IS NULL", [], true)->count,
      ]
    ];
  }
}