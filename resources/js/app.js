import './bootstrap';

/**
 * Changes the class of an element.
 * If the timeout is > 0, it will revert the class after the amount of ms
 *
 * @param {HTMLElement} el
 * @param {string} className
 * @param {number} timeout
 */
window.changeClass = function (el, className, timeout = 0) {
  let current = el.className;
  el.className = className;

  if (timeout > 0) {
    setTimeout(function () {
      el.className = current;
    }, timeout);
  }
};
