$(document).ready(function () {
  $.ajax({
    type: 'GET',
    url: 'http://desarrollo.biblored.gov.co/api-agenda/eventos/7/45/11/2017',
    success: function (data) {
      {
        var date = new Date(data.date * 1000);
        $('body').append('' +
          '<h1 class="name">' + data.random_node.title + '</h1>' +
          '<content class="body">' + data.random_node.body + '</content>' +
          '<div class="date">' + date  + '</div>' +
          '<h2 class="email">' + data.site_name + '</h2>'
        );
      }
    }
  });
});