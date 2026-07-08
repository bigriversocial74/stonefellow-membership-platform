(function(){
  function openModal(id){
    var modal = id ? document.getElementById(id) : null;
    if (!modal) return;
    if (typeof modal.showModal === 'function') modal.showModal();
    else modal.setAttribute('open', 'open');
  }
  function closeModal(modal){
    if (!modal) return;
    if (typeof modal.close === 'function') modal.close();
    else modal.removeAttribute('open');
  }
  document.querySelectorAll('[data-storyboard-modal-open]').forEach(function(button){
    button.addEventListener('click', function(){ openModal(button.getAttribute('data-storyboard-modal-open')); });
  });
  document.querySelectorAll('[data-storyboard-modal-close]').forEach(function(button){
    button.addEventListener('click', function(){ closeModal(button.closest('dialog')); });
  });
  document.querySelectorAll('.sf-storyboard-modal').forEach(function(modal){
    modal.addEventListener('click', function(event){
      if (event.target === modal) closeModal(modal);
    });
  });
  document.addEventListener('keydown', function(event){
    if (event.key !== 'Escape') return;
    document.querySelectorAll('.sf-storyboard-modal[open]').forEach(closeModal);
  });
})();
