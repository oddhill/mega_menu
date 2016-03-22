/**
 * @file
 * Behaviours for mega menus.
 */

(function ($, document) {

  'use strict';

  /**
   * The main mega menu functionality as a jQuery plugin.
   */
  $.fn.megamenu = function() {
    var $element = this;
    var $links = $element.find('[data-mega-menu-content-target] > a');
    var $content = $element.find('[data-mega-menu-content]');

    // Initialize the content as hidden.
    $content.hide();

    // Bind events.
    $links.on('click.mega-menu', toggleContent);
    $(document).on('click.mega-menu', onOutsideClick);

    /**
     * Public method to close the mega menu.
     */
    this.close = function () {
      $element.trigger('closing');
      hideContent();
    };

    /**
     * Handle clicks outside of the mega menu.
     *
     * @param event
     */
    function onOutsideClick(event) {
      if (!$(event.target).closest($element).length) {
        hideContent();
      }
    }

    /**
     * Hide every menu except the current one.
     */
    function hideContent(current) {
      var elements = $content.not(current);
      elements.hide().removeClass('visible');
    }

    /**
     * Toggle menu link content on click.
     */
    function toggleContent(event) {
      var element = $(event.target);
      var menuTarget = element.closest('li').data('mega-menu-content-target');

      if (!menuTarget.length) {
        return;
      }

      var content = $content.filter('[data-mega-menu-content="' + menuTarget + '"]');

      if (content.length) {
        event.preventDefault();

        hideContent(content);

        content.toggle().toggleClass('visible');
      }
    }
  };

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
      $('[data-mega-menu]').once('mega-menu').each(function (key, item) {
        $(item).megamenu();
      });
    }
  };

})(jQuery, document);
