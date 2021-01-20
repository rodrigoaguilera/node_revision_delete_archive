/**
 * @file
 * Attaches the show/hide functionality to checkboxes in the "Archive storage"
 * form.
 */

(function ($) {

  'use strict';

  Drupal.behaviors.persistedQueries = {
    attach: function (context, settings) {
      $('.archive-storage-wrapper input.form-checkbox', context).each(function () {
        var $checkbox = $(this);
        var plugin_id = $checkbox.data('id');
        var tab = $('.archive-storage-settings--' + plugin_id, context).data('verticalTab');

        $checkbox.on('click.archiveStorageUpdate', function () {
          if ($checkbox.is(':checked')) {
            if (tab) {
              tab.tabShow().updateSummary();
            }
          }
          else {
            if (tab) {
              tab.tabHide().updateSummary();
            }
          }
        });

        // Trigger our bound click handler to update elements to initial state.
        $checkbox.triggerHandler('click.archiveStorageUpdate');
      });
    }
  };

})(jQuery);
