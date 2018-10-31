export default (e) => {
  const $title = $('#title');
  if ($title.val().length < 1) {
    $(`
      <div class="notice notice-error">
        <p>A headline is required!</p>
      </div>
    `).insertAfter('.wp-header-end');
    $title.focus();
    e.preventDefault();
  }
};
