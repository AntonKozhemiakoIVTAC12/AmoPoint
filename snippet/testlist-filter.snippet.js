/*
 * testlist-filter.snippet.js
 * --------------------------
 * Одноразовый сниппет для DevTools-консоли. Можно вставить целиком
 * в Console (Chromium / Firefox) даже на чужой странице вроде
 *   http://test.amopoint-dev.ru/testzz/testlist.html
 *
 * Логика идентична testlist-filter.js, плюс подробный лог в консоль:
 * скрипт печатает, какие поля скрыты и какие показаны.
 *
 * Безопасен к повторному запуску: предыдущий экземпляр сначала
 * демонтируется, потом ставится новый.
 */
(() => {
  'use strict';

  if (window.__amopointTestlistFilter && typeof window.__amopointTestlistFilter.destroy === 'function') {
    window.__amopointTestlistFilter.destroy();
  }

  const TYPE_SELECT_REGEX = /тип|type/i;
  const WRAPPER_SELECTOR = '.form-row, .form-group, label';
  const FIELD_SELECTOR = 'input[name], textarea[name], select[name]';

  const wrapperCache = new WeakMap();
  const hiddenOriginalDisplay = new WeakMap();

  const findControllerSelect = (root) => {
    const selects = root.querySelectorAll('select[name]');
    for (const sel of selects) {
      if (TYPE_SELECT_REGEX.test(sel.name)) {
        return sel;
      }
    }
    return null;
  };

  const getWrapper = (el) => {
    if (wrapperCache.has(el)) {
      return wrapperCache.get(el);
    }
    let node = el.parentElement;
    while (node && node !== document.body) {
      if (node.matches && node.matches(WRAPPER_SELECTOR)) {
        wrapperCache.set(el, node);
        return node;
      }
      node = node.parentElement;
    }
    wrapperCache.set(el, el);
    return el;
  };

  const show = (wrapper) => {
    if (hiddenOriginalDisplay.has(wrapper)) {
      wrapper.style.display = hiddenOriginalDisplay.get(wrapper);
      hiddenOriginalDisplay.delete(wrapper);
    } else {
      wrapper.style.removeProperty('display');
    }
  };

  const hide = (wrapper) => {
    if (!hiddenOriginalDisplay.has(wrapper)) {
      hiddenOriginalDisplay.set(wrapper, wrapper.style.display || '');
    }
    wrapper.style.display = 'none';
  };

  const controllerSelect = findControllerSelect(document);
  if (!controllerSelect) {
    console.warn('[AmoPointFilter snippet] не нашёл <select> с name ~ /тип|type/i — выхожу');
    return;
  }
  const formRoot = controllerSelect.closest('form') || document;
  const controllerWrapper = getWrapper(controllerSelect);

  const apply = () => {
    const value = (controllerSelect.value || '').trim();
    const fields = formRoot.querySelectorAll(FIELD_SELECTOR);
    const hidden = [];
    const shown = [];

    fields.forEach((field) => {
      if (field === controllerSelect) {
        return;
      }
      const wrapper = getWrapper(field);
      if (wrapper === controllerWrapper) {
        return;
      }
      if (!value || (field.name && field.name.includes(value))) {
        show(wrapper);
        shown.push(field.name);
      } else {
        hide(wrapper);
        hidden.push(field.name);
      }
    });

    console.groupCollapsed(
      `[AmoPointFilter snippet] type="${value || '*'}" — показано ${shown.length}, скрыто ${hidden.length}`
    );
    console.log('shown :', shown);
    console.log('hidden:', hidden);
    console.groupEnd();
  };

  const reset = () => {
    formRoot.querySelectorAll(FIELD_SELECTOR).forEach((field) => show(getWrapper(field)));
  };

  const onChange = () => apply();
  controllerSelect.addEventListener('change', onChange);

  const observer = new MutationObserver((mutations) => {
    const hasNewFields = mutations.some((m) =>
      Array.from(m.addedNodes).some((node) => {
        if (node.nodeType !== 1) {
          return false;
        }
        if (node.matches && node.matches(FIELD_SELECTOR)) {
          return true;
        }
        return node.querySelector && node.querySelector(FIELD_SELECTOR);
      })
    );
    if (hasNewFields) {
      apply();
    }
  });
  observer.observe(formRoot, { childList: true, subtree: true });

  const destroy = () => {
    observer.disconnect();
    controllerSelect.removeEventListener('change', onChange);
    reset();
    delete window.__amopointTestlistFilter;
    delete window.AmoPointFilter;
    console.log('[AmoPointFilter snippet] демонтирован');
  };

  const api = { apply, reset, destroy };
  window.__amopointTestlistFilter = api;
  window.AmoPointFilter = api;

  apply();
  console.log(
    '[AmoPointFilter snippet] активирован. Доступно: AmoPointFilter.apply() / reset() / destroy()'
  );
})();
