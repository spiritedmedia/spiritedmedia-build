/**
 * Manage P2P connection boxes
 */
export default function connectionMetabox(connection, generalType) {
  var toType;
  var toClusters = connection.to;
  for (var i = toClusters.length - 1; i >= 0; i--) {
    toType = toClusters[i];
    var specificType = connection.from + '_to_' + toType;
    var $box = $('[data-p2p_type=' + specificType + '].p2p-box');
    var connectionTabPanelSelector = '#fm-pedestal_' + generalType +
        '_connections-0-' + toType + '-0-tab .fm-group-inner';
    var $connectionTabPanel = $(connectionTabPanelSelector);

    // Move box to tab panel
    if ($box.length !== 0 && $connectionTabPanel.length !== 0) {
      $connectionTabPanel.append($box.parent().html());
      $box.closest('.postbox').remove();
    }
  }
}
