/* global ajaxurl, messagePreviewDefaults */

import Message from '../Message';
import PreviewView from './PreviewView';

export default class OverrideMessage extends Message {
  constructor() {
    super($('.fm-override'), PreviewView, messagePreviewDefaults.override);

    this.post = null;

    this.$el.on(
      'change',
      '.fm-autocomplete-hidden',
      (e) => this.onPostSelection(e)
    );
  }

  /**
   * Automatically populate the Alternative Headline field when selecting a post
   *
   * @param {Event} e
   */
  onPostSelection(e) {
    const $postSelect = $(e.target);
    const postId = parseInt($postSelect.val());
    const $group = $postSelect.closest('.fm-group-inner');
    const $body = $group.find('.fm-body .fm-element');
    const $url = $group.find('.fm-url .fm-element');

    if (!postId) {
      $body.val('');
      $url.val('');
      return;
    }

    const data = {
      'post_id': postId,
      'action': 'pedestal-message-spot-override'
    };

    $.post(ajaxurl, data, (response) => {
      if (!response.data) {
        return;
      }
      this.post = response.data;

      $body.val(this.post.title);
      this.setPreviewAttribute('body', this.post.title);
      // Store the post title as a default in case the entire body is deleted
      $group.find('.fm-post_title .fm-element').val(this.post.title);
      this.setPreviewAttribute('postTitle', this.post.title);

      const url = encodeURI(this.post.url);
      $url.val(url);
      this.setPreviewAttribute('url', url);
    });
  }
}
