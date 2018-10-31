/**
 * Add input field to hierarchical term metaboxes so terms can be filtered
 * as you type.
 */
export default function filterHierarchicalTerms() {
  var $boxes = $('.categorydiv');
  if ($boxes.length < 1) {
    return;
  }

  // Extend :contains to be case-insensitive
  // via http://stackoverflow.com/questions/187537/
  jQuery.expr[':'].contains = function(a,i,m) {
    var haystack = (a.textContent || a.innerText || '').toUpperCase();
    var needle = m[3].toUpperCase();
    return haystack.indexOf(needle) >= 0;
  };

  var filterClass = 'categorydiv-filter';
  var filterSelector = '.' + filterClass;

  // Create our filter input element
  var $filterInputElement = $('<input type="search" />')
    .addClass(filterClass)
    .css('width', '100%');

  $boxes.each(function(index, box) {
    var $box = $(box);
    if ($box.find('.categorychecklist li').length < 10) {
      return;
    }
    var boxTitle = $box.parent().siblings('.hndle').text();
    $filterInputElement.attr('placeholder', 'Filter ' + boxTitle);
    $box.prepend($filterInputElement);
  });

  // Attach events to the main <form> of the edit screen
  $('#post')
    // Keyup happens after the character has been entered
    // which we need for counting purposes
    .on('keyup', filterSelector, function() {
      var $this = $(this);
      var $checklists = $this.parent().find('.categorychecklist li');
      if ($this.val().length < 2) {
        $checklists.show();
      } else {
        $checklists
          .hide()
          .find('.selectit:contains(' + $this.val() + ')')
          .each(function(index, label) {
            $(label).parent().show();
          });
      }
    })
    // Keydown happens before the character is entered
    // so we can catch the return key being pressed
    // and cancel the event which submits the form
    .on('keydown', filterSelector, function(e) {
      // Prevent the return key from submitting the form
      if (e.keyCode == 13) {
        e.preventDefault();
        return false;
      }
    })
    // Clumsy way to detect when the x in the search input is
    // activated clearing the field and resetting the filter
    .on('click', filterSelector, function() {
      var $this = $(this);
      // Without a small delay the keyup event
      // thinks there are more than 0 characters
      setTimeout(function() {
        $this.trigger('keyup');
      }, 100, $this);
    });
}
