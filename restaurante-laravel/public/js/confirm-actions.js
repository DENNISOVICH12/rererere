(function () {
  function getOptionsFromDataset(dataset) {
    return {
      title: dataset.confirmTitle,
      confirmText: dataset.confirmAccept,
      cancelText: dataset.confirmCancel,
    };
  }

  function bindConfirmSubmit(form) {
    if (form.dataset.confirmBound === '1') return;
    form.dataset.confirmBound = '1';

    form.addEventListener('submit', async function (event) {
      if (form.dataset.confirmed === '1') {
        form.dataset.confirmed = '0';
        return;
      }

      event.preventDefault();

      if (typeof window.showConfirm !== 'function') return;

      const ok = await window.showConfirm(form.dataset.confirmMessage || '¿Seguro que deseas continuar?', getOptionsFromDataset(form.dataset));
      if (!ok) return;

      form.dataset.confirmed = '1';
      form.requestSubmit();
    });
  }

  function bindConfirmClick(element) {
    if (element.dataset.confirmBound === '1') return;
    element.dataset.confirmBound = '1';

    element.addEventListener('click', async function (event) {
      event.preventDefault();

      if (typeof window.showConfirm !== 'function') return;

      const ok = await window.showConfirm(element.dataset.confirmMessage || '¿Seguro que deseas continuar?', getOptionsFromDataset(element.dataset));
      if (!ok) return;

      const form = element.closest('form');
      if (form) {
        form.requestSubmit();
      }
    });
  }

  function initConfirmActions() {
    document.querySelectorAll('form[data-confirm-message]').forEach(bindConfirmSubmit);
    document.querySelectorAll('[data-confirm-click="true"]').forEach(bindConfirmClick);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initConfirmActions);
  } else {
    initConfirmActions();
  }
})();
