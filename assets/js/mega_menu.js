/**
 * @file
 * Behaviours for mega menus.
 */

(function ($, document) {

  'use strict';

  /**
   * Toggles a mega menus content visibility depending on what was set in the
   * mega menu configuration.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for the block filtering.
   */
  Drupal.behaviors.megaMenuToggler = {
    attach: function () {
      var $megaMenuLinks = $('ul.mega-menu a').once('mega-menu-toggler');

      $megaMenuLinks
        .once('mega-menu-link-click')
        .on('click.mega-menu', toggleContent);

      $(document)
        .once('mega-menu-document-click')
        .on('click.mega-menu', onOutsideClick);

      /**
       * Handle clicks outside of the mega menu.
       *
       * @param event
       */
      function onOutsideClick(event) {
        if (!$(event.target).closest('ul.mega-menu').length) {
          hideContent();
        }
      }

      /**
       * Hide every menu except the current one.
       */
      function hideContent(current) {
        var menuItemsContent = $($megaMenuLinks).siblings('div').not(current);

        menuItemsContent
          .hide()
          .removeClass('visible');
      }

      /**
       * Toggle menu link content on click.
       */
      function toggleContent(event) {
        var element = $(event.target);
        var content = element.siblings('div');

        if (content.length) {
          event.preventDefault();

          hideContent(content);

          content
            .toggle()
            .toggleClass('visible');
        }
      }
    }
  };

})(jQuery, document);
