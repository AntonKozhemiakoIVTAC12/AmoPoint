/*
 * testlist-filter.js
 * ------------------
 * Подключаемая версия фильтра полей по значению селекта "Тип".
 *
 * Подключение:
 *   <script src="./testlist-filter.js"></script>
 *
 * После загрузки доступен глобальный API window.AmoPointFilter:
 *   AmoPointFilter.apply()    — применить фильтр заново
 *   AmoPointFilter.reset()    — показать все поля
 *   AmoPointFilter.destroy()  — снять обработчики и MutationObserver
 *
 * Принцип работы описан в README.md, секция «Алгоритм».
 */
(function () {
  'use strict';

  if (window.__amopointTestlistFilter) {
    window.__amopointTestlistFilter.destroy();
  }

  const TYPE_SELECT_REGEX = /тип|type/i;
  const WRAPPER_SELECTOR = '.form-row, .form-group, label';
  const FIELD_SELECTOR = 'input[name], textarea[name], select[name]';

  const wrapperCache = new WeakMap();
  const hiddenOriginalDisplay = new WeakMap();

  let controllerSelect = null;
  let formRoot = null;
  let observer = null;
  let onChangeHandler = null;

  function findControllerSelect(root) {
    const selects = root.querySelectorAll('select[name]');
    for (const sel of selects) {
      if (TYPE_SELECT_REGEX.test(sel.name)) {
        return sel;
      }
    }
    return null;
  }

  function getWrapper(el) {
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
  }

  function show(wrapper) {
    if (hiddenOriginalDisplay.has(wrapper)) {
      wrapper.style.display = hiddenOriginalDisplay.get(wrapper);
      hiddenOriginalDisplay.delete(wrapper);
    } else {
      wrapper.style.removeProperty('display');
    }
  }

  function hide(wrapper) {
    if (!hiddenOriginalDisplay.has(wrapper)) {
      hiddenOriginalDisplay.set(wrapper, wrapper.style.display || '');
    }
    wrapper.style.display = 'none';
  }

  function apply() {
    if (!controllerSelect) {
      return;
    }
    const value = (controllerSelect.value || '').trim();
    const fields = (formRoot || document).querySelectorAll(FIELD_SELECTOR);
    const controllerWrapper = getWrapper(controllerSelect);

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
      } else {
        hide(wrapper);
      }
    });
  }

  function reset() {
    const fields = (formRoot || document).querySelectorAll(FIELD_SELECTOR);
    fields.forEach((field) => show(getWrapper(field)));
  }

  function destroy() {
    if (observer) {
      observer.disconnect();
      observer = null;
    }
    if (controllerSelect && onChangeHandler) {
      controllerSelect.removeEventListener('change', onChangeHandler);
    }
    reset();
    controllerSelect = null;
    formRoot = null;
    onChangeHandler = null;
    delete window.__amopointTestlistFilter;
    delete window.AmoPointFilter;
  }

  function init() {
    controllerSelect = findControllerSelect(document);
    if (!controllerSelect) {
      console.warn('[AmoPointFilter] не найден <select> с name, похожим на "type" / "тип"');
      return;
    }
    formRoot = controllerSelect.closest('form') || document;

    onChangeHandler = () => apply();
    controllerSelect.addEventListener('change', onChangeHandler);

    observer = new MutationObserver((mutations) => {
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

    apply();
  }

  const api = { apply, reset, destroy };
  window.__amopointTestlistFilter = api;
  window.AmoPointFilter = api;

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }
})();
