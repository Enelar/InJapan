<?php

class urlfind extends api
{
  protected function Reserve()
  {
    return
    [
      'design' => 'urlfind/entry',
    ];
  }

  protected function ParseAndStore($url)
  {
    return $this->MeetAndParse($url);
  }

  public function MeetUrl($url)
  {
    $id = $this->MeetOnlyUpdate($url);
    if ($id)
      return $id;
    return $this->MeetOnlyInsert($url);
  }

  private function MeetOnlyUpdate($url)
  {
    $id = db::Query("UPDATE urls SET last_seen=now() WHERE url=$1 RETURNING id", [$url], true);
    if ($id())
      return $id->id;
    return 0;
  }

  private function MeetOnlyInsert($url)
  {
    $trans = db::Begin();
    $id = db::Query("UPDATE urls SET last_seen=now() WHERE url=$1 RETURNING id", [$url], true);
    if (!$id())
      $id = db::Query("INSERT INTO urls(url) VALUES($1) RETURNING id", [$url], true);

    $trans->Commit();
    return $id->id;
  }

  private function AddParseTask($id, $is_category = false)
  {
    db::Query("INSERT INTO to_parse(id, is_category) VALUES ($1, $2)", [$id, $is_category ? 't' : 'f']);
  }

  public function MeetAndParse($url, $is_category = false)
  {
    if ($id = $this->MeetOnlyUpdate($url))
      return $id;
    $id = $this->MeetOnlyInsert($url);
    $this->AddParseTask($id, $is_category);
    return $id;
  }

  public function AddRelation($parent, $child)
  {
    db::Query("INSERT INTO relations(container, contains) VALUES ($1, $2) RETURNING snap", [$parent, $child], true);
  }

  protected function QueryUrl($category, $url)
  {
    $res = db::Query("INSERT INTO query(category, url) VALUES ($1, $2) RETURNING id", [$category, $url], true);
    $this->MeetAndParse($category);
    return (int)$res->id;
  }

  protected function QueryStat($id, $recurse = false)
  {
    $query = db::Query("SELECT *  FROM query WHERE id=$1", [$id], true);
    if ($query->res)
    {
      return
      [
        "data" =>
        [
          "url" => db::Query("SELECT url FROM urls WHERE id=$1", [$query->res], true)->url,
        ],
      ];
    }

    $known = db::Query("SELECT * FROM urls WHERE url=$1", [$query->url], true);
    if (!$known() || $recurse)
    {
      $parse = $this('api', 'urlparse');
      $parse->ParseNext();
      return $parse->ParseStats();
    }
    
    $result = db::Query("SELECT * FROM urls, relations
      WHERE relations.container=urls.id
        AND relations.contains=(SELECT id FROM urls WHERE url=$1)",
      [$query->url], true);

    $a = db::Query("UPDATE query SET restime=now(), res=$2 WHERE id=$1 RETURNING id", [$query->id, $result->id], true);

    return $this->QueryStat($id, true);
  }
}
