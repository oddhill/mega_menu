/**
 * @file
 * Behaviours for mega menus.
 */

(function ($, window) {

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
    attach: function (context, settings) {
      var $megaMenuLinks = $('ul.mega-menu > li').once('mega-menu-toggler');

      /**
       * Toggle menu link content on click.
       */
      function toggleContent(event) {
        var element = $(event.currentTarget);
        var content = $('> div', element);

        if (content.length) {
          event.preventDefault();

          content
            .toggle()
            .toggleClass('visible');
        }
      }

      $megaMenuLinks.on('click.mega-menu', toggleContent);
    }
  };

})(jQuery, window);
