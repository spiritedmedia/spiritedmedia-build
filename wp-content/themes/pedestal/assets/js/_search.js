jQuery(document).ready(function($) {

  var $body = $('body');
  var doingSearch = false;
  var $main = $('.js-main');
  var $form = $('.js-search-form');
  var $pagination = $('.js-pagination');
  var $stream = $('.js-stream');
  var $spinner = $('.js-spinner');

  // Simple feature detection for History Management (borrowed from Modernizr)
  function supportsHistory() {
    return !!(window.history && history.pushState);
  }
  if (!supportsHistory()) {
    return;
  }

  function scrollTop() {
    var formTop = $form.offset().top;
    var winTop = $(window).scrollTop();
    if (winTop < formTop) {
      return;
    }
    $('html, body').animate({scrollTop: formTop}, 250);
  }

  function startLoading() {
    $form.find('.js-search-input, .js-search-tools').addClass('is-loading');
    $stream.addClass('is-loading');
    $spinner.show();
    scrollTop();
  }

  function stopLoading() {
    $form.find('.is-loading').removeClass('is-loading');
    $stream.removeClass('is-loading');
    $spinner.removeAttr('style');
  }

  function showReset() {
    $form.find('.js-search-icon-reset').show();
  }

  function hideReset() {
    $form.find('.js-search-icon-reset').hide();
  }

  function resetForm() {
    $form.find('.js-search-field').val('').focus();
    $form.find('.js-search-icon-reset').hide();
    hideReset();
  }

  function doSearch(url) {
    // If a search is already being performed, then bail to prevent a pile-up of
    // AJAX requests
    if (doingSearch) {
      return;
    }
    // If no URL is provided to fetch then we bail
    if (!url) {
      stopLoading();
      return false;
    }

    $.ajax({
      type: 'GET',
      dataType: 'html',
      url: url,
      beforeSend: function() {
        // Block potentially concurrent requests
        doingSearch = true;
      },
      success: function(data) {
        // Remove any whitespace on the ends of the returned HTML string
        data = $.trim(data);

        // Need to scrape the returned HTML with regex to get the <title> value
        var newPageTitle = '';
        var titleMatches = data.match(/<title>(.+)<\/title>/);
        if (titleMatches[1]) {
          newPageTitle = titleMatches[1];
        }
        var $data = $(data);

        //  Take .js-stream from the AJAX'd page and inject it
        //  into the current page
        var newStreamHTML = $data.find('.js-stream').html();
        newStreamHTML = $.trim(newStreamHTML);
        var newSearchToolsHTML = $data.find('.js-search-tools').html();
        newSearchToolsHTML = $.trim(newSearchToolsHTML);

        $stream.html(newStreamHTML);
        $('.js-search-tools').html(newSearchToolsHTML);
        $main.addClass('is-active-search');

        window.history.pushState(null, newPageTitle, url);
        showReset();
        stopLoading();
      },
      error: function() {
        stopLoading();
      }
    }).always(function() {
      doingSearch = false;
      scrollTop();
    });
  }

  // Need to register the event on an element that doesn't get replaced
  $main.on('click', 'a.js-pagination-item', function(e) {
    if (!$main.is('.is-active-search')) {
      return;
    }
    var $this = $(this);
    // If the button clicked is disabled then abort
    if ($this.is('.js-is-disabled')) {
      return;
    }

    startLoading();
    var url = $this.attr('href');
    doSearch(url);
    e.preventDefault();
  });

  $form.on('submit', function(e) {
    var url = $form.attr('action');
    // Ensure url ends with a '/'
    url = url.replace(/\/?$/, '/');
    url += '?' + $form.serialize();
    // Ordering by relevance is default so we don't need to add
    // this query arg to the URL
    url = url.replace(/&orderby=relevance/ig, '');
    if (window.location.href === url) {
      e.preventDefault();
      return;
    }
    // Force the search box to lose focus in order to hide a virtual keyboard
    // Virtual keyboards block the loading search results
    $form.find('.js-search-field').blur();
    startLoading();
    doSearch(url);
    e.preventDefault();
  });

  $form.on('click', '.js-search-icon-reset', function(e) {
    resetForm();
    e.preventDefault();
  });

  $form.on('keyup', '.js-search-field', function(e) {
    if (this.value) {
      showReset();
    } else {
      hideReset();
    }
  });

  $('.js-search-tools').on('change', '.js-search-filters-radio', function() {
    $this = $(this);
    if ($this.is('selected')) {
      return;
    }

    $this.closest('.js-search-filters').find('.is-active')
      .removeClass('is-active button');
    $this.parent().addClass('is-active button');
    $form.submit();
  });

  // Init
  if ($form.find('.js-search-field').val()) {
    showReset();
  }
});
