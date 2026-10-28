/* Restore only reloads and same-page form saves; ordinary navigation starts fresh. */
(() => {
  const storageKey = 'robot-camp:scroll';
  const page = url => url.searchParams.get('page') || 'home';
  const current = new URL(location.href);
  const samePage = value => {
    const other = new URL(value, location.href);
    return other.origin === current.origin && other.pathname === current.pathname && page(other) === page(current) &&
      (!other.searchParams.has('camp_id') || !current.searchParams.has('camp_id') || other.searchParams.get('camp_id') === current.searchParams.get('camp_id'));
  };
  const detailKey = (node, index) => {
    const form = node.querySelector('form');
    return [index, form?.querySelector('[name="group_id"]')?.value || '', form?.querySelector('[name="kind"]')?.value || ''].join(':');
  };
  const capture = reason => {
    try {
      sessionStorage.setItem(storageKey, JSON.stringify({url: location.href, reason, time: Date.now(), x: scrollX, y: scrollY,
        details: Array.from(document.querySelectorAll('main details')).map((node, index) => ({key: detailKey(node,index), open: node.open}))}));
    } catch (_) { /* Storage may be unavailable in private browser settings. */ }
  };
  let saved;
  try { saved = JSON.parse(sessionStorage.getItem(storageKey)); sessionStorage.removeItem(storageKey); } catch (_) {}
  const navigation = performance.getEntriesByType('navigation')[0]?.type;
  if (saved && Date.now() - saved.time < 300000 && samePage(saved.url) && !location.hash &&
      (navigation === 'reload' || saved.reason === 'submit')) {
    if ('scrollRestoration' in history) history.scrollRestoration = 'manual';
    Array.from(document.querySelectorAll('main details')).forEach((node,index) => {
      const previous = saved.details?.find(item => item.key === detailKey(node,index));
      // Keep server-opened validation / editing forms open as well.
      if (previous?.open) node.open = true;
    });
    let interrupted = false;
    const stop = () => { interrupted = true; };
    ['wheel','touchstart','pointerdown','keydown'].forEach(type => window.addEventListener(type,stop,{once:true,passive:true}));
    const restore = () => { if (!interrupted) window.scrollTo({left:saved.x,top:saved.y,behavior:'instant'}); };
    requestAnimationFrame(() => requestAnimationFrame(restore));
    if (document.readyState !== 'complete') window.addEventListener('load',restore,{once:true});
    document.fonts?.ready.then(restore);
    setTimeout(() => { interrupted = true; if ('scrollRestoration' in history) history.scrollRestoration = 'auto'; },2500);
  }
  let submitting = false;
  document.addEventListener('submit', event => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || form.method.toLowerCase() !== 'post') return;
    const action = new URL(event.submitter?.formAction || form.action || location.href,location.href);
    if (samePage(action.href) && !['login','logout','camp-reset'].includes(page(action))) {
      submitting = true; capture('submit');
      // AJAX handlers may cancel navigation; do not keep a pending restore indefinitely.
      setTimeout(() => { submitting = false; },1000);
    }
  },true);
  window.addEventListener('pagehide', () => capture(submitting ? 'submit' : 'leave'));
})();
