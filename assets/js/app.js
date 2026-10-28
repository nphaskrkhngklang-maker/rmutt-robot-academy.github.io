const drawer = document.getElementById('navigation');
const menuToggle = document.querySelector('.menu-button');
const compactNavigation = window.matchMedia('(max-width: 900px)');
function setDrawer(open, focus = true) {
  if (!drawer || !menuToggle) return;
  const visible = !compactNavigation.matches || open;
  drawer.inert = !visible;
  drawer.setAttribute('aria-hidden', String(!visible));
  drawer.classList.toggle('is-open', compactNavigation.matches && open);
  menuToggle.setAttribute('aria-expanded', String(visible));
  if (focus && compactNavigation.matches) {
    if (open) drawer.querySelector('.drawer-close')?.focus();
    else menuToggle.focus();
  }
}
setDrawer(false, false);
compactNavigation.addEventListener('change', () => setDrawer(false, false));
menuToggle?.addEventListener('click', () => setDrawer(menuToggle.getAttribute('aria-expanded') !== 'true'));
document.querySelector('.drawer-close')?.addEventListener('click', () => setDrawer(false));
document.addEventListener('keydown', event => {
  if (event.key === 'Escape' && compactNavigation.matches && drawer?.classList.contains('is-open')) setDrawer(false);
});
document.addEventListener('click', event => {
  if (compactNavigation.matches && drawer?.classList.contains('is-open') && !drawer.contains(event.target) && !menuToggle?.contains(event.target)) setDrawer(false);
});
const staffHeader = document.querySelector('.staff-sketch-header');
const pageHeading = document.querySelector('.main-content .page-heading');
if (staffHeader && pageHeading) staffHeader.append(pageHeading);

const linkInput = document.querySelector('[data-registration-link]');
if (linkInput) {
  linkInput.value = new URL(linkInput.value, window.location.href).href;
  document.querySelector('[data-copy-link]')?.addEventListener('click', async () => {
    const status = document.querySelector('[data-copy-status]');
    try {
      await navigator.clipboard.writeText(linkInput.value);
      status.textContent = 'คัดลอกลิงก์แล้ว พร้อมส่งให้โรงเรียน';
    } catch {
      linkInput.focus(); linkInput.select();
      status.textContent = 'เลือกลิงก์ให้แล้ว กด Ctrl+C หรือคัดลอกจากช่องนี้';
    }
  });
}

const registrationForm = document.querySelector('[data-registration-form]');
if (registrationForm) {
  const container = registrationForm.querySelector('[data-participant-rows]');
  const rowNotice = registrationForm.querySelector('[data-row-notice]');
  let nextIndex = container.querySelectorAll('[data-participant-row]').length;
  function updateSummary() {
    const totals = { student: 0, teacher: 0, people: 0, shirts: 0 };
    const sizes = {};
    container.querySelectorAll('[data-participant-row]').forEach(row => {
      if (!row.querySelector('[data-person-name]').value.trim()) return;
      totals[row.querySelector('[data-person-type]').value]++;
      totals.people++;
      const size = row.querySelector('[data-person-shirt]').value;
      if (size) { totals.shirts++; sizes[size] = (sizes[size] || 0) + 1; }
    });
    registrationForm.querySelectorAll('[data-total]').forEach(el => { el.textContent = String(totals[el.dataset.total]); });
    registrationForm.querySelectorAll('[data-size]').forEach(el => { el.textContent = String(sizes[el.dataset.size] || 0); });
  }
  registrationForm.querySelectorAll('[data-add-person]').forEach(button => {
    button.addEventListener('click', event => {
      event.preventDefault();
      if (container.children.length >= 150) { rowNotice.textContent = 'เพิ่มได้สูงสุด 150 คนต่อโรงเรียน'; return; }
      const template = document.getElementById('participant-template');
      const fragment = template.content.cloneNode(true);
      fragment.querySelectorAll('[name]').forEach(el => { el.name = el.name.replace('__INDEX__', String(nextIndex)); });
      nextIndex++;
      fragment.querySelector('[data-person-type]').value = button.dataset.addPerson;
      const field = fragment.querySelector('[data-person-name]');
      container.append(fragment); field.focus();
      rowNotice.textContent = 'เพิ่มแถวแล้ว อย่าลืมบันทึกข้อมูล';
    });
  });
  container.addEventListener('click', event => {
    const button = event.target.closest('[data-remove-person]');
    if (!button) return;
    const row = button.closest('[data-participant-row]');
    const nextRow = row.nextElementSibling || row.previousElementSibling;
    const nextSibling = row.nextSibling;
    row.remove(); updateSummary();
    rowNotice.textContent = 'นำแถวออกแล้ว การลบจะบันทึกเมื่อกดบันทึกร่างหรือส่งข้อมูล ';
    const undo = document.createElement('button');
    undo.type = 'button'; undo.className = 'text-link'; undo.textContent = 'เลิกทำ';
    undo.addEventListener('click', () => {
      if (container.children.length >= 150) { rowNotice.textContent = 'กรุณาลดแถวก่อนคืนรายการ'; return; }
      container.insertBefore(row, nextSibling?.parentNode === container ? nextSibling : null);
      rowNotice.textContent = 'คืนรายการแล้ว'; row.querySelector('[data-person-name]').focus(); updateSummary();
    });
    rowNotice.append(undo);
    nextRow?.querySelector('[data-person-name]')?.focus();
  });
  registrationForm.addEventListener('input', updateSummary);
  registrationForm.addEventListener('change', updateSummary);
  updateSummary();
}
const dutySelect = document.querySelector('[data-duty-type]');
if (dutySelect) {
  const refreshDutyFields = () => document.querySelectorAll('[data-duty-for]').forEach(field => {
    field.hidden = field.dataset.dutyFor !== dutySelect.value;
  });
  dutySelect.addEventListener('change', refreshDutyFields);
  refreshDutyFields();
}

const scoreEditor = document.querySelector('[data-score-editor]');
if (scoreEditor) {
  const rows = scoreEditor.querySelector('[data-score-rows]');
  const notice = scoreEditor.querySelector('[data-score-notice]');
  let index = rows.children.length;
  scoreEditor.querySelector('[data-score-add]').addEventListener('click', () => {
    if (rows.children.length >= 50) { notice.textContent = 'เพิ่มได้สูงสุด 50 เกณฑ์'; return; }
    const fragment = document.getElementById('score-template').content.cloneNode(true);
    fragment.querySelectorAll('[name]').forEach(el => { el.name = el.name.replace('__INDEX__', String(index)); });
    index++;
    rows.append(fragment);
    rows.lastElementChild.querySelector('input').focus();
    notice.textContent = 'เพิ่มเกณฑ์แล้ว กดบันทึกเกมเพื่อบันทึกการเปลี่ยนแปลง';
  });
  rows.addEventListener('click', event => {
    const button = event.target.closest('[data-score-remove]');
    if (!button) return;
    if (rows.children.length <= 1) { notice.textContent = 'ต้องมีอย่างน้อย 1 เกณฑ์'; return; }
    button.closest('[data-score-row]').remove();
    notice.textContent = 'ลบเกณฑ์ออกจากแบบฟอร์มแล้ว กดบันทึกเกมเพื่อยืนยัน';
  });
}

const randomForm = document.querySelector('[data-random-form]');
if (randomForm) {
  const preview = document.querySelector('[data-random-preview]');
  const notice = randomForm.querySelector('[data-random-notice]');
  let busy = false;
  randomForm.addEventListener('input', () => {
    preview.replaceChildren();
    notice.textContent = 'การตั้งค่าเปลี่ยนแล้ว กรุณาสุ่มตัวอย่างใหม่';
  });
  randomForm.addEventListener('submit', async event => {
    event.preventDefault();
    if (busy) return;
    busy = true;
    const data = new FormData(randomForm);
    const controls = [...randomForm.querySelectorAll('button,select,input')];
    controls.forEach(el => { el.disabled = true; });
    preview.replaceChildren();
    notice.textContent = 'กำลังสุ่ม…';
    try {
      const response = await fetch('api/randomize.php', { method: 'POST', body: data, credentials: 'same-origin' });
      const result = await response.json();
      if (!response.ok) throw new Error(result.error || 'สุ่มไม่สำเร็จ กรุณาลองใหม่');
      // HTML comes from our escaped, server-rendered preview template.
      preview.innerHTML = result.html;
      notice.textContent = 'ได้ตัวอย่างแล้ว ตรวจสอบตารางก่อนยืนยัน';
      preview.scrollIntoView({ behavior: 'smooth', block: 'start' });
    } catch (error) {
      notice.textContent = error.message || 'เชื่อมต่อไม่สำเร็จ กรุณาลองใหม่';
    } finally {
      busy = false;
      controls.forEach(el => { el.disabled = false; });
    }
  });
}
document.querySelectorAll('[data-readiness-form]').forEach(form => {
  form.addEventListener('submit', async event => {
    event.preventDefault();
    const button = form.querySelector('button');
    if (button.disabled) return;
    button.disabled = true;
    const notice = form.querySelector('[data-readiness-notice]');
    notice.textContent = 'กำลังบันทึก…';
    try {
      const response = await fetch('api/readiness.php', { method: 'POST', body: new FormData(form), credentials: 'same-origin' });
      const result = await response.json();
      if (!response.ok) throw new Error(result.error || 'บันทึกไม่สำเร็จ');
      window.location.reload();
    } catch (error) {
      notice.textContent = error.message || 'เชื่อมต่อไม่สำเร็จ';
      button.disabled = false;
    }
  });
});

const scoreEntryForm = document.querySelector('[data-score-entry-form]');
if (scoreEntryForm) {
  const fields = [...scoreEntryForm.querySelectorAll('[data-entry]')];
  const refresh = () => scoreEntryForm.querySelectorAll('[data-score-unit]').forEach(unit => {
    let sum = 0;
    let complete = true;
    unit.querySelectorAll('[data-entry]').forEach(input => {
      if (!input.value || !input.checkValidity() || !/^-?\d+(\.\d{1,2})?$/.test(input.value)) { complete = false; return; }
      const negative = input.value.startsWith('-');
      const parts = input.value.replace('-', '').split('.');
      const cents = Number(parts[0]) * 100 + Number((parts[1] || '').padEnd(2, '0'));
      sum += negative ? -cents : cents;
    });
    unit.querySelector('[data-score-total]').textContent = complete ? (sum / 100).toFixed(2) : '—';
  });
  scoreEntryForm.addEventListener('input', refresh);
  refresh();
  scoreEntryForm.addEventListener('submit', async event => {
    event.preventDefault();
    const button = scoreEntryForm.querySelector('button');
    if (button.disabled || !scoreEntryForm.reportValidity()) return;
    const payload = Object.fromEntries([...new FormData(scoreEntryForm)].filter(([key]) => !key.startsWith('scores[')));
    payload.scores = {};
    fields.forEach(input => {
      payload.scores[input.dataset.entry] ||= {};
      payload.scores[input.dataset.entry][input.dataset.criterion] = input.value;
    });
    button.disabled = true;
    const notice = scoreEntryForm.querySelector('[data-score-save-notice]');
    notice.textContent = 'กำลังบันทึกคะแนน…';
    try {
      const response = await fetch(window.location.href, {method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload)});
      if (!(response.headers.get('Content-Type') || '').includes('application/json')) throw new Error('เซสชันหมดอายุหรือระบบไม่พร้อม กรุณาเปิดหน้าใหม่ก่อนบันทึก');
      const result = await response.json();
      if (!response.ok) throw new Error(result.error || 'บันทึกไม่สำเร็จ');
      window.location.assign(result.url);
    } catch(error) {
      notice.textContent = error.message;
      button.disabled = false;
    }
  });
}
document.querySelector('[data-print-score]')?.addEventListener('click', () => window.print());

const menuSearch = document.querySelector('[data-menu-search]');
if (menuSearch && drawer) {
  const menuGroups = Array.from(drawer.querySelectorAll('.sidebar-group'));
  const initialOpen = menuGroups.map(group => group.open);
  menuSearch.addEventListener('input', () => {
    const term = menuSearch.value.trim().toLocaleLowerCase();
    if (term) menuGroups.forEach(group => group.removeAttribute('name'));
    menuGroups.forEach((group,index) => {
      let matches = false;
      group.querySelectorAll('a.nav-link').forEach(link => {
        const match = !term || (link.textContent+' '+(link.dataset.searchKeywords||'')).toLocaleLowerCase().includes(term);
        link.hidden = !match; matches ||= match;
      });
      group.hidden = !matches;
      group.open = term ? matches : initialOpen[index];
      // Search results can expand several groups simultaneously.
      if (term) group.removeAttribute('name');
      else group.setAttribute('name','sidebar-sections');
    });
    if (term && compactNavigation.matches) setDrawer(true, false);
  });
}
