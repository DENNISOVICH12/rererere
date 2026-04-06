(function () {
  const LOGOUT_SELECTOR = '[data-logout], #logout';

  function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  }

  async function confirmLogout() {
    if (typeof window.showConfirm !== 'function') {
      return false;
    }

    return window.showConfirm('¿Seguro que deseas cerrar sesión?', {
      title: 'Cerrar sesión',
      confirmText: 'Cerrar sesión',
      cancelText: 'Cancelar',
    });
  }

  async function logout() {
    const shouldLogout = await confirmLogout();
    if (!shouldLogout) return;

    const token = getCsrfToken();

    try {
      await fetch('/logout', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': token,
          Accept: 'application/json',
        },
      });
    } finally {
      window.location.href = '/login';
    }
  }

  function bindLogoutButtons() {
    document.querySelectorAll(LOGOUT_SELECTOR).forEach((btn) => {
      if (btn.dataset.logoutBound === '1') return;
      btn.dataset.logoutBound = '1';
      btn.addEventListener('click', (event) => {
        event.preventDefault();
        logout();
      });
    });
  }

  window.logout = logout;

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bindLogoutButtons);
  } else {
    bindLogoutButtons();
  }
})();
