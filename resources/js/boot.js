/**
 * Switches the theme by storing it to the localStorage and applying it
 *
 * @param {string|null} theme The theme to switch to (dark|light|null), where null uses the preferred color scheme
 */
window.switchTheme = function (theme) {
  if (theme === null) {
    localStorage.removeItem('theme');
  } else {
    localStorage.theme = theme;
  }

  applyTheme();
};

/**
 * Applies the stored theme by appending/removing a "dark" class to/from the document-root-element
 */
window.applyTheme = function () {
  if (localStorage.theme === 'dark') {
    document.documentElement.classList.add('dark-theme');
    document.documentElement.classList.remove('light-theme');
    document.documentElement.classList.remove('system-theme');
  } else if (localStorage.theme === 'light') {
    document.documentElement.classList.remove('dark-theme');
    document.documentElement.classList.add('light-theme');
    document.documentElement.classList.remove('system-theme');
  } else {
    document.documentElement.classList.remove('dark-theme');
    document.documentElement.classList.remove('light-theme');
    document.documentElement.classList.add('system-theme');
  }

  if (
    localStorage.theme === 'dark' ||
    (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)
  ) {
    document.documentElement.classList.add('dark');
  } else {
    document.documentElement.classList.remove('dark');
  }
};

window.matchMedia('(prefers-color-scheme: dark)').onchange = applyTheme;

// Apply the theme on startup
applyTheme();
