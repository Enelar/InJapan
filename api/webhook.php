<?php

class webhook extends api
{
  protected function StatusChange()
  {
    db::Query("INSERT INTO webhook(globals) VALUES ($1)", [var_export($GLOBALS, true)]);
  }

  protected function NewOrder()
  {
    db::Query("INSERT INTO webhook(globals) VALUES ($1)", [var_export($GLOBALS, true)]);
  }
}