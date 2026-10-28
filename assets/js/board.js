(() => {
 const composer=document.querySelector('[data-board-compose]'); if(!composer || typeof HTMLDialogElement==='undefined')return;
 const dialog=document.createElement('dialog');dialog.className='board-dialog';composer.before(dialog);dialog.append(composer);
 const close=composer.querySelector('[data-board-close]');close.hidden=false;close.addEventListener('click',()=>dialog.close());
 document.querySelectorAll('[data-board-open]').forEach(link=>link.addEventListener('click',e=>{e.preventDefault();dialog.showModal();}));
 if(document.querySelector('.memory-board .alert.error'))dialog.showModal();
})();
