<?php

error_reporting(E_ERROR | E_WARNING | E_PARSE);
ini_set('display_errors','On');

include_once('utils/config.php');

include_once('phpsql/phpsql.php');
include_once('phpsql/pgsql.php');
$sql = new phpsql();
$pg = $sql->Connect("pgsql://postgres@localhost/motoimperia");
include_once('phpsql/wrapper.php');

include_once('phpsql/db.php');
db::Bind(new phpsql\utils\wrapper($pg));

function real_ip()
{
  global $_SERVER;
  $words = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];

  foreach ($words as $word)
    if (isset($_SERVER[$word]))
      return $_SERVER[$word];
  die();
}

function phoxy_conf()
{
  $ret = phoxy_default_conf();
  global $_SERVER;
  $ret["ip"] = real_ip();
  $ret['adminip'] = false;
  if (!$ret['adminip'])
    ini_set('display_errors','Off');
  return $ret;
}

function default_addons( $name )
{
  $ret =
  [
    "cache" => ["no"],
    "result" => "canvas",
  ];
  return $ret;
}

include('phoxy/index.php');