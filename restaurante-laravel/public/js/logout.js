(function () {
  const LOGOUT_SELECTOR = '[data-logout], #logout';

  function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  }

  function buildModal() {
    const overlay = document.createElement('div');
    overlay.id = 'logout-confirm-overlay';
    overlay.innerHTML = `
      <div class="logout-confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="logout-confirm-title">
        <h3 id="logout-confirm-title">Cerrar sesión</h3>
        <p>¿Seguro que deseas cerrar sesión?</p>
        <div class="logout-confirm-actions">
          <button type="button" class="logout-btn logout-btn-cancel">Cancelar</button>
          <button type="button" class="logout-btn logout-btn-danger">Cerrar sesión</button>
        </div>
      </div>
    `;

    const style = document.createElement('style');
    style.textContent = `
      #logout-confirm-overlay {
        position: fixed;
        inset: 0;
        background: rgba(7, 10, 16, 0.7);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        padding: 20px;
      }
      .logout-confirm-dialog {
        width: min(420px, 100%);
        background: linear-gradient(180deg, #141a23, #10151d);
        color: #ecf1f9;
        border: 1px solid rgba(148, 163, 184, 0.2);
        border-radius: 14px;
        box-shadow: 0 20px 45px rgba(0, 0, 0, 0.45);
        padding: 22px;
      }
      .logout-confirm-dialog h3 {
        margin: 0;
        font-size: 1.1rem;
      }
      .logout-confirm-dialog p {
        margin: 10px 0 0;
        color: #bfc9d8;
      }
      .logout-confirm-actions {
        margin-top: 18px;
        display: flex;
        gap: 10px;
        justify-content: flex-end;
      }
      .logout-btn {
        border: 0;
        border-radius: 8px;
        padding: 9px 14px;
        font-weight: 600;
        cursor: pointer;
      }
      .logout-btn-cancel {
        background: #283244;
        color: #e7edf9;
      }
      .logout-btn-danger {
        background: #dc2626;
        color: #fff;
      }
    `;

    document.head.appendChild(style);
    document.body.appendChild(overlay);

    return {
      overlay,
      cancelBtn: overlay.querySelector('.logout-btn-cancel'),
      confirmBtn: overlay.querySelector('.logout-btn-danger'),
    };
  }

  function confirmLogout() {
    return new Promise((resolve) => {
      const { overlay, cancelBtn, confirmBtn } = buildModal();

      const close = (value) => {
        overlay.remove();
        resolve(value);
      };

      cancelBtn.addEventListener('click', () => close(false));
      confirmBtn.addEventListener('click', () => close(true));
      overlay.addEventListener('click', (event) => {
        if (event.target === overlay) close(false);
      });
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
