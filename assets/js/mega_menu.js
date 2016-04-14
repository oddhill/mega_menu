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
   *   - mega-menu:opening: Event is triggered when the mega menu is opening
   *     from a completely closed state.
   *   - mega-menu:closing: Event will be triggered when the menu is closing
   *     a panel and not opening up another one.
   *   - mega-menu:changing: Event is triggered when the mega menu changes
   *     the active content without completely closing the menu.
   */
  $.fn.megamenu = function() {
    var $element = this;

    var $content = $element.find('[data-mega-menu-content]');
    var $listItems = $element.find('[data-mega-menu-content-target]');
    var $links = $listItems.children('a');

    /**
     * Public method to close the mega menu.
     */
    $.fn.megamenu.close = function () {
      triggerClosingEvent($element);
    };

    /**
     * Public method to open the mega menu.
     */
    $.fn.megamenu.open = function () {
      triggerOpeningEvent($element);
    };

    // Return to expose public methods.
    return this.each(function() {
      $links.on('click.mega-menu', onMenuItemClick);
      $(document).on('click.mega-menu', onOutsideClick);
    });

    /**
     * Trigger an opening event. This is used when the mega menu has no
     * visible content ans is opened for the "first" time.
     *
     * @param {Object} target - The target element.
     */
    function triggerOpeningEvent(target) {
      var event = jQuery.Event('mega-menu:opening', {
        target: target
      });

      $element.trigger(event);

      if (!event.isDefaultPrevented()) {
        showContent(target);
        activateListItem(target);
      }
    }

    /**
     * Triggers a changing event. This event is used when one menu items
     * content is closed but another menu items content is being opened,
     * toggling between different mega menu drop downs.
     *
     * @param {Object} target - The target element.
     */
    function triggerChangingEvent(target) {
      var previousTarget = $content.filter('.visible');

      var event = jQuery.Event('mega-menu:changing', {
        megaMenu: {
          previousTarget: previousTarget,
          currentTarget: target
        },
        target: target
      });

      $element.trigger(event);

      if (event.isDefaultPrevented()) {
        return;
      }

      hideOtherContent(target);
      showContent(target);
      deactivateListItem(previousTarget);
      activateListItem(target);
    }

    /**
     * Trigger the mega menu closing event, this is used when all menus are
     * being closed without opening another menu items content.
     *
     * @param {Object} target - The target element.
     */
    function triggerClosingEvent(target) {
      var event = jQuery.Event('mega-menu:closing', {
        target: target || $element
      });

      $element.trigger(event);

      if (!event.isDefaultPrevented()) {
        hideContent();
        deactivateListItems();
      }
    }

    /**
     * Handle clicks outside of the mega menu.
     *
     * @param {Object} event
     */
    function onOutsideClick(event) {
      if (!$(event.target).closest($element).length && menuIsOpen()) {
        triggerClosingEvent(event.target);
      }
    }

    /**
     * Check to see if the menu is open based on class values.
     *
     * @returns {Boolean}
     */
    function menuIsOpen() {
      return ($listItems.filter('.active').length || $content.filter('.visible').length) ? true : false;
    }

    /**
     * Hide every menus content except the specified one.
     *
     * @param {Object} current - The content element to keep visible.
     */
    function hideOtherContent(current) {
      var elements = $content.not(current);
      elements.removeClass('visible');
    }

    /**
     * Hide all visible menu content.
     */
    function hideContent() {
      $content.removeClass('visible');
      deactivateListItems();
    }

    /**
     * Show the specified content element.
     *
     * @param {Object} element - The content element to show.
     */
    function showContent(element) {
      activateListItem(element);
      element.addClass('visible');
    }

    /**
     * Get the specified content elements target list item.
     *
     * @param {Object} element
     * @returns {*|HTMLElement}
     */
    function getTargetListItem(element) {
      var targetId = element.data('mega-menu-content');
      return $('[data-mega-menu-content-target="'+targetId+'"]');
    }

    /**
     * De-activate all list items.
     */
    function deactivateListItems() {
      $listItems.removeClass('active');
    }

    /**
     * Activate the specified content elements list item.
     *
     * @param {Object} element
     */
    function activateListItem(element) {
      getTargetListItem(element).addClass('active');
    }

    /**
     * De-activate the specified content elements list item.
     *
     * @param {Object} element
     */
    function deactivateListItem(element) {
      getTargetListItem(element).removeClass('active');
    }

    /**
     * Handle clicks on mega menu link items.
     *
     * @param {Object} event - The event that triggered the click.
     */
    function onMenuItemClick(event) {
      var element = $(event.target);
      var menuTarget = element.closest('li').data('mega-menu-content-target');

      // If the menu does not have a content target then return and let the
      // normal click action take place.
      if (!menuTarget) {
        return;
      }

      var content = $content.filter('[data-mega-menu-content="' + menuTarget + '"]');

      // If no menu content was found let the normal action take place.
      if (!content.length) {
        return;
      }

      event.preventDefault();

      triggerMenuEvents(content);
    }

    /**
     * Decide on what events to trigger based on the current mega menu state.
     *
     * @param {Object} content - The menu content that an action should be taken for.
     */
    function triggerMenuEvents(content) {
      // If no content element has the visible class then the menu is opening
      // for the first time so lets trigger the opening event.
      if (!$content.hasClass('visible')) {
        triggerOpeningEvent(content);
      }
      else if (content.hasClass('visible')) {
        triggerClosingEvent(content);
      }
      else {
        triggerChangingEvent(content);
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
