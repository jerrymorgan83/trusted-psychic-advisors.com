document.addEventListener('click', function (event) {
  var burger = event.target.closest('.tpa-burger');
  if (!burger) { return; }
  var nav = document.getElementById('tpa-nav');
  if (!nav) { return; }
  var open = nav.classList.toggle('is-open');
  burger.setAttribute('aria-expanded', open ? 'true' : 'false');
});
