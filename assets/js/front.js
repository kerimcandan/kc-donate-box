(function(){
  function ready(fn){ if(document.readyState!=='loading'){ fn(); } else { document.addEventListener('DOMContentLoaded', fn); } }
  ready(function(){
    document.addEventListener('click', function(e){
      var btn = e.target.closest('.kc-copy');
      if(!btn) return;
      e.preventDefault();
      var txt = btn.getAttribute('data-copy') || '';
      if(!txt) return;

      var done = function(){
        try { btn.textContent = 'Copied ✓'; } catch(e){}
        setTimeout(function(){ try { btn.textContent = 'Copy'; } catch(e){} }, 1500);
      };

      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(txt).then(done, done);
      } else {
        var ta = document.createElement('textarea');
        ta.value = txt;
        ta.style.position = 'fixed';
        ta.style.left = '-9999px';
        document.body.appendChild(ta);
        ta.focus(); ta.select();
        try { document.execCommand('copy'); } catch(e) {}
        document.body.removeChild(ta);
        done();
      }
    });
  });
})();
