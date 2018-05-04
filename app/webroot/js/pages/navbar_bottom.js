$(function(){
  // Goes from genomeconfigs/:id/uploads or genomeconfigs/:id/uploads/ to
  $('#to-config').on('click', function(){
    var url = window.location.href;
    url = url.replace(/uploads(|.)$/, '');
    window.location.href = url;
  });

  // Goes from genomeconfigs/:id or genomeconfigs/:id/ to uploads
  $('#to-uploads').on('click', function(){
    var url = window.location.href;
    url = url.replace(/(|\\|\/)$/, '/uploads');
    window.location.href = url;
  });
});
