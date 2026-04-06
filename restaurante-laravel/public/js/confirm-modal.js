(function () {
  const MODAL_ID = 'modal-confirm';
  const STYLE_ID = 'modal-confirm-styles';

  function ensureStyles() {
    if (document.getElementById(STYLE_ID)) return;

    const style = document.createElement('style');
    style.id = STYLE_ID;
    style.textContent = `
      .modal-confirm.hidden { display: none; }
      .modal-confirm {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(2, 6, 23, 0.72);
        backdrop-filter: blur(6px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        padding: 16px;
      }

      .modal-box {
        background: linear-gradient(180deg, #0f172a, #0b1220);
        border: 1px solid rgba(148, 163, 184, 0.24);
        border-radius: 16px;
        padding: 24px;
        width: min(360px, 100%);
        box-shadow: 0 10px 40px rgba(0,0,0,0.5);
        text-align: center;
        color: #f8fafc;
      }

      .modal-box h3 {
        margin: 0;
        font-size: 1.1rem;
      }

      .modal-box p {
        margin: 10px 0 0;
        color: #cbd5e1;
      }

      .modal-actions {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        margin-top: 20px;
      }

      .btn-secondary,
      .btn-danger {
        border: 0;
        padding: 10px 16px;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        min-width: 110px;
      }

      .btn-secondary {
        background: transparent;
        border: 1px solid #334155;
        color: #f8fafc;
      }

      .btn-danger {
        background: #dc2626;
        color: white;
      }

      .btn-secondary:hover { background: rgba(51, 65, 85, 0.45); }
      .btn-danger:hover { background: #b91c1c; }
    `;

    document.head.appendChild(style);
  }

  function ensureModal() {
    let modal = document.getElementById(MODAL_ID);
    if (modal) return modal;

    modal = document.createElement('div');
    modal.id = MODAL_ID;
    modal.className = 'modal-confirm hidden';
    modal.innerHTML = `
      <div class="modal-box" role="dialog" aria-modal="true" aria-labelledby="modal-title">
        <h3 id="modal-title">Confirmación</h3>
        <p id="modal-message">¿Seguro que deseas continuar?</p>

        <div class="modal-actions">
          <button id="btn-cancel" class="btn-secondary" type="button">Cancelar</button>
          <button id="btn-confirm" class="btn-danger" type="button">Aceptar</button>
        </div>
      </div>
    `;

    document.body.appendChild(modal);
    return modal;
  }

  window.showConfirm = function showConfirm(message, options = {}) {
    ensureStyles();
    const modal = ensureModal();
    const messageNode = document.getElementById('modal-message');
    const titleNode = document.getElementById('modal-title');
    const confirmBtn = document.getElementById('btn-confirm');
    const cancelBtn = document.getElementById('btn-cancel');

    messageNode.textContent = message || '¿Seguro que deseas continuar?';
    titleNode.textContent = options.title || 'Confirmación';
    confirmBtn.textContent = options.confirmText || 'Aceptar';
    cancelBtn.textContent = options.cancelText || 'Cancelar';

    modal.classList.remove('hidden');

    return new Promise((resolve) => {
      const close = (value) => {
        modal.classList.add('hidden');
        confirmBtn.removeEventListener('click', onConfirm);
        cancelBtn.removeEventListener('click', onCancel);
        modal.removeEventListener('click', onBackdropClick);
        document.removeEventListener('keydown', onEsc);
        resolve(value);
      };

      const onConfirm = () => close(true);
      const onCancel = () => close(false);
      const onBackdropClick = (event) => {
        if (event.target === modal) close(false);
      };
      const onEsc = (event) => {
        if (event.key === 'Escape') close(false);
      };

      confirmBtn.addEventListener('click', onConfirm, { once: true });
      cancelBtn.addEventListener('click', onCancel, { once: true });
      modal.addEventListener('click', onBackdropClick);
      document.addEventListener('keydown', onEsc);
    });
  };
})();
