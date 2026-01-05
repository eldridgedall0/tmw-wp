(function(){
  document.addEventListener('click', function(e){
    const btn = e.target.closest('[data-gm-toggle-password]');
    if(!btn) return;
    const id = btn.getAttribute('data-gm-toggle-password');
    const input = document.getElementById(id);
    if(!input) return;
    input.type = (input.type === 'password') ? 'text' : 'password';
    btn.textContent = (input.type === 'password') ? 'Show' : 'Hide';
  });
})();
