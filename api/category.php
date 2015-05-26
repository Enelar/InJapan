<?php

class category extends api
{
  protected function Reserve($id=26312)
  {
    return $this->GetChildsOf($id);
  }

  protected function GetChildsOf($id)
  {
    $me = db::Query("SELECT *, nlevel(tree) FROM categories WHERE category=$1", [$id], true);
    $categories = db::Query("SELECT * 
      FROM categories 
      WHERE nlevel(tree) = $2 + 1
        AND tree <@ $1", [$me->tree, $me->nlevel]);

    return
    [
      'design' => 'category/viewer',
      'data' =>
      [
        'categories' => $categories,
        'current' => $me,
        'parents' => db::Query("SELECT * FROM categories WHERE tree @> $1 ORDER BY tree ASC", [$me->tree]),
      ]
    ];
  }

  protected function Items($id)
  {
    $items = db::Query("WITH
      parents AS
      (
        SELECT category FROM categories WHERE tree @> (SELECT tree FROM categories WHERE category=$1)
      )
      SELECT 
          id, name, price, images, enddate
          FROM auctions, parents
          WHERE auctions.category=parents.category ORDER BY name ASC", [$id]);
    return
    [
      'design' => 'category/items',
      'data' =>
      [
        'items' => $items,
      ],
    ];
  }
}