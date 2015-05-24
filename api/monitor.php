<?php
class monitor extends api
{
  protected function Reserve()
  {
    return
    [
      "design" => "monitor/entry",
    ];
  }

  protected function Active()
  {
    return
    [
      "design" => "monitor/active",
      "data" =>
      [
        "list" => db::Query("SELECT * FROM categories ORDER BY name"),
      ],
    ];
  }

  protected function AddCategory()
  {
    //$dom->load('<div class="all"><p>Hey bro, <a href="google.com">click here</a><br /> :)</p></div>');
    //$a = $dom->find('a')[0];
    //echo $a->text; // "click here"
  }
}