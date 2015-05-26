var url_click = {};

url_click.hook_url_click = function()
{
  $("body").on("click", "a", function()
  {
    var url = $(this).attr('href');
    
    if (url == undefined || $(this).is('[not-phoxy]'))
      return true;

    return url_click.url_hook(url, false);
  });

  window.onpopstate = function()
  {
    url_click.on_pop_state.apply(this, arguments);
    if (typeof analytics != 'undefined')
      analytics.page();
  }
}

url_click.url_hook = function (url, not_push)
{
  if (url.indexOf('#') != -1)
    return true;
  

  if (url[0] == '/')
    url = url.substring(1);
  
  phoxy.MenuCall(url, undefined, undefined, not_push);
  return false; 
}

url_click.on_pop_state = function(e)
{
  var path = e.target.location.pathname;
  var hash = e.target.location.hash;

  url_click.url_hook(path, true);
}

url_click.hook_url_click();