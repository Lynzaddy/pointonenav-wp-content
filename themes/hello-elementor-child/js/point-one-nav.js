// header-sticky.js
window.addEventListener('scroll', () => {
  const header = document.querySelector('.site-header');
  header.classList.toggle('is-sticky', window.scrollY > 100);
});
