(function () {
  const memoryKey = 'cooperFoxLastPageState';

  function readState() {
    try {
      return JSON.parse(localStorage.getItem(memoryKey) || '{}');
    } catch (error) {
      return {};
    }
  }

  function writeState(state) {
    try {
      localStorage.setItem(memoryKey, JSON.stringify(state));
    } catch (error) {
      console.warn('Could not save page memory', error);
    }
  }

  function currentPath() {
    return window.location.pathname + window.location.search;
  }

  function savePageState() {
    const current = {
      path: currentPath(),
      scrollY: Math.max(0, Math.round(window.scrollY || 0)),
      scrollX: Math.max(0, Math.round(window.scrollX || 0)),
      updatedAt: Date.now()
    };

    writeState(current);
  }

  function restorePageState() {
    const saved = readState();
    if (!saved || !saved.path || saved.path !== currentPath()) {
      return;
    }

    const y = Number(saved.scrollY || 0);
    const x = Number(saved.scrollX || 0);

    if (y >= 0 || x >= 0) {
      window.scrollTo({
        left: x,
        top: y,
        behavior: 'auto'
      });
    }
  }

  window.addEventListener('beforeunload', savePageState);
  window.addEventListener('pagehide', savePageState);
  document.addEventListener('visibilitychange', function () {
    if (document.visibilityState === 'hidden') {
      savePageState();
    }
  });

  window.addEventListener('load', function () {
    restorePageState();
  });

  window.addEventListener('pageshow', function () {
    restorePageState();
  });
})();
