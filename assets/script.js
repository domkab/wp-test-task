jQuery(document).ready(function ($) {
  var debounceTimer

  $('#domain-search').on('keyup', function () {
    var query = $(this).val().trim()
    console.log('Search query:', query)

    clearTimeout(debounceTimer)

    if (query === '') {
      $.ajax({
        url: ajaxurl,
        method: 'POST',
        data: {
          action: 'search_top_sites',
          search_query: ''
        },
        success: function (response) {
          if (response.success) {
            var html = ''
            $.each(response.data, function (index, site) {
              html += '<tr>'
              html += '<td>' + site.page_rank + '</td>'
              html +=
                '<td class="top-sites__table--domain">' +
                site.domain_name +
                '</td>'
              html += '</tr>'
            })
            $('#sites-table tbody').html(html)
          } else {
            console.log('No results found.')
            $('#sites-table tbody').empty()
          }
        },
        error: function (err) {
          console.log('AJAX error:', err)
        }
      })
      return
    }

    if (query.length < 3) {
      return
    }

    debounceTimer = setTimeout(function () {
      $.ajax({
        url: ajaxurl,
        method: 'POST',
        data: {
          action: 'search_top_sites',
          search_query: query
        },
        success: function (response) {
          if (response.success) {
            var html = ''
            $.each(response.data, function (index, site) {
              html += '<tr>'
              html += '<td>' + site.page_rank + '</td>'
              html +=
                '<td class="top-sites__table--domain">' +
                site.domain_name +
                '</td>'
              html += '</tr>'
            })
            $('#sites-table tbody').html(html)
          } else {
            console.log('No results found.')
            $('#sites-table tbody').empty()
          }
        },
        error: function (err) {
          console.log('AJAX error:', err)
        }
      })
    }, 300)
  })
})
