<?php
define("PHPANTOMJSDIR", "/phpantomjs");

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

  protected function BeginTrackCategory( $url )
  {
    $category = $this('api', 'urlparse')->GetCategoryId($url);
    db::Query("INSERT INTO track(category) VALUES ($1)", [$category]);
  }

  private function GetNext()
  {
    $res = db::Query("
      WITH helper AS
      (
        SELECT * FROM to_parse WHERE is_category = false AND \"lock\" IS NULL OR now() - \"lock\" > '10 min'::INTERVAL LIMIT 1
      ) UPDATE to_parse as a SET lock=now() WHERE id=(SELECT id FROM helper)
        RETURNING (SELECT url FROM urls as b WHERE a.id=b.id)
      ", [], true);
    return db::Query("SELECT * FROM urls WHERE url=$1", [$res->url], true);
  }

  protected function ScanNext()
  {
    do
    {
      $row = $this->GetNext();
      if (!$row())
        return "no tasks";
    } while (strpos($row->url, "https://injapan.ru/auction/") === false);

    $this->ScanAd($row->url);
    db::Query("DELETE FROM to_parse WHERE id=$1", [$row->id]);
  }

  protected function ScanAd($url = 'https://injapan.ru/auction/x403149817.html')
  {
// $('#content div[align="center"] div')
    include('phpantomjs/phpantomjs.php');

    $script = <<<EOT
// Not working since some issue at injapan
//$('#SwitchCurrRUR').click();
//$('#dynInpWeight1').val(1);
//$('#buttonCalc').click();

ret.text = {};
ret.text.ja = $('#ja').html();
ret.text.ru = $('#russian').html();

ret.name = $('#rowInfoPrice').parents('table').first().find('tr:first-child .l').html();

ret.img = [];
$('.lot_images img').each(function()
{
  ret.img.push($(this).attr('src'));
})

ret.breadcrumbs = [];
$('#breadcrumbs .small').each(function()
{
  var id = $(this).attr('href');
  if (!id)
    return;
  ret.breadcrumbs.push
  ({
    id: id.replace(/[^\d]/g, ''),
    title: $(this).html()
  })
})

ret.category = $('[name="scope"] [selected]').val();
ret.id = $('#txtQuery').val();


ret.end_date = $('#spanInfoEnddate').html();
ret.remain = $('#datevalue').html();
ret.price = {};
ret.price.base = $('#spanInfoPrice .CurrRUR').text().replace(/[^\d]/g, '');


ret.price.in_japan = $('#dynSpanSubtotalConvRUR1').text().replace(/[^\d]/g, '');
//ret.price.delivery = $('#spanSubtotalConvRUR').text().replace(/[^\d]/g, '');
//ret.price.total = $('#spanTotalConvRUR').text().replace(/[^\d]/g, '');

EOT;

    $phpantomjs = new phpantomjs();
    $parsed  = $phpantomjs->Inject($url, $script);

    $res = $parsed ['inject'];

    var_dump($res);

    // 27/05/2015 14:44
    $date = date_parse_from_format("d/m/Y G:i", $res['end_date']);
    // 2001-02-16 21:28:30
    $pgdate = "{$date['year']}-{$date['month']}-{$date['day']} {$date['hour']}:{$date['minute']}:00";


    $query = "INSERT INTO auctions
        (id, name, price, images, object, enddate, category)
      VALUES
        ($1, $2, $3,
         $4, $5, $6,
         $7)
      RETURNING id";

    $data =
      [
        $res['id'], $res['name'], $res['price']['in_japan'],
        $res['img'], json_encode($res), $pgdate, 
        $res['category']
      ];

    var_dump($query, $data);

    $ret = db::Query($query, $data, true);

    $tree = "root";
    foreach ($res['breadcrumbs'] as $value)
    {
      if (!$value['id'])
        continue;
      $tree .= ".".$value['id'];
      db::Query("INSERT INTO categories(category, name, tree) VALUES ($1, $2, $3)",
        [$value['id'], $value['title'], $tree]);
    }
  }
}