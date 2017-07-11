/* global ga */

jQuery(document).ready(function($) {

  var $body = $('body');
  var doingSearch = false;
  var $main = $('.js-main');
  var $form = $('.js-search-form');
  var $stream = $('.js-stream');
  var $spinner = $('.js-spinner');

  $body.on('click keyup', '.js-sitewide-search-icon', function(e) {
    // Key up is to detect keyboard actions on the search icon for accessibility
    // Ignore any key event other than return/enter (keycode = 13)
    if (e.type === 'keyup' && e.which !== 13) {
      return;
    }

    $body.toggleClass('is-search-open');

    // Set the tab index to 1 so the search form has a natural
    // tab order even though it's at the end of the markup
    var targetID = $(this).attr('for');
    var $target = $('#' + targetID);
    $target.attr('tabindex', 1).select();
  });

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
    $form.find('.js-search-input').addClass('is-loading');
    $main.addClass('is-loading');
    $spinner.show();
    scrollTop();
  }

  function stopLoading() {
    $form.find('.is-loading').removeClass('is-loading');
    $main.removeClass('is-loading');
    $spinner.removeAttr('style');
  }

  function closeForm() {
    $body.removeClass('is-search-open');
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

        // We need to tag our pushState events because some browsers fire the
        // popstate event on page load
        // See http://stackoverflow.com/questions/10756893/how-to-ignore-popstate-initial-load-working-with-pjax
        window.history.pushState({spiritedSearch: true}, newPageTitle, url);

        // Send pageview to Google Analytics
        // See https://developers.google.com/analytics/devguides/collection/analyticsjs/pages
        if (typeof ga === 'function') {
          ga('send', {
            hitType: 'pageview',
            title: newPageTitle,
            location: url
          });
        }
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
    startLoading();
    // No stream, let the form submit normally
    if (!$stream.length) {
      // Show loading animation before page reloads
      return;
    }

    var url = $form.attr('action');
    // Ensure url ends with a '/'
    url = url.replace(/\/?$/, '/');
    url += '?' + $form.serialize();
    // Ordering by relevance is default so we don't need to add
    // this query arg to the URL
    url = url.replace(/&orderby=relevance/ig, '');
    if (window.location.href === url) {
      setTimeout(function() {
        stopLoading();
      }, 450);
      e.preventDefault();
      return;
    }
    // Force the search box to lose focus in order to hide a virtual keyboard
    // Virtual keyboards block the loading search results
    $form.find('.js-search-field').blur();
    doSearch(url);
    e.preventDefault();
  });

  $form.on('click', '.js-search-icon-close', function(e) {
    closeForm();
    e.preventDefault();
  });

  $('.js-search-tools').on('change', '.js-search-filters-radio', function() {
    var $this = $(this);
    if ($this.is('selected')) {
      return;
    }

    $this.closest('.js-search-filters').find('.is-active')
      .removeClass('is-active js-btn');
    $this.parent().addClass('is-active js-btn');
    $form.submit();
  });

  $(window).bind('popstate', function(e) {
    if (!e.originalEvent.state || !e.originalEvent.state.spiritedSearch) {
      // The popstate event was fired but it didn't come from us, so ignore
      return;
    }
    // Prevent endless redirects...
    if (window.location.href === document.referrer) {
      return;
    }
    startLoading();
    window.location = window.location.href;
  });
});
