/**
 * @file
 * Behaviours for mega menus.
 */

(function ($, document) {

  'use strict';

  /**
   * The main mega menu functionality as a jQuery plugin.
   *
   * Available events:
   *   - mega-menu:closing: Event will be triggered when the menu is closing a
   *     panel and not opening up another one.
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
      triggerClosingEvent();
    };

    /**
     * Trigger the on closing event.
     */
    function triggerClosingEvent(target) {
      var event = jQuery.Event('mega-menu:closing', {
        target: target || $element
      });

      $element.trigger(event);

      if (!event.isDefaultPrevented()) {
        hideContent();
      }
    }

    /**
     * Handle clicks outside of the mega menu.
     *
     * @param event
     */
    function onOutsideClick(event) {
      if (!$(event.target).closest($element).length) {
        triggerClosingEvent(event.target);
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

      if (!content.length) {
        return;
      }

      // If the content that is being toggles is already visible then the menu
      // should be completely closed to trigger a closing event to close
      // the menu.
      if (content.is(':visible')) {
        triggerClosingEvent();
      }
      else {
        hideContent(content);
        content.show().addClass('visible');
      }

      event.preventDefault();
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
