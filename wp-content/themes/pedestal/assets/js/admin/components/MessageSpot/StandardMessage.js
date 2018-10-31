import Message from 'Message';

class StandardMessage extends Message {
  constructor($el) {
    super($el);

    const $sortableContainer = this.$el.parent();
    $sortableContainer.one('sortstart', () => this.destroyPreview());
    $sortableContainer.one('sortstop', () => this.createPreview());
  }
}

export default StandardMessage;
