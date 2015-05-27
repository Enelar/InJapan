<?php

class image extends api
{
  protected function Compress($url)
  {
    $cached = $this->CachedName($url);
    if (!file_exists($cached))
    {
      $str = file_get_contents($url);
      $img = imagecreatefromstring($str);

      imageinterlace($img, true);
      imagejpeg($img, $cached, 50);
    }

    header("HTTP/1.1 301 Moved Permanently"); 
    header("Location: /$cached"); 
    exit();
  }

  private function CachedName($url)
  {
    return "img/cached/".base64_encode($url).".jpg";
  }
}