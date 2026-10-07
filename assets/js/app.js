// Small UI behaviours: mobile menu, dismissible flash, confirm modal.
document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.querySelector('.nav-toggle');
    const links = document.getElementById('nav-links');
    if (toggle && links) {
        toggle.addEventListener('click', () => {
            const open = links.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', open);
        });
    }

    const flash = document.querySelector('.flash');
    if (flash) {
        flash.querySelector('.flash-close')?.addEventListener('click', () => flash.remove());
        setTimeout(() => flash.remove(), 5000);
    }

    // data-modal-open="#id" opens that <dialog>; data-modal-close closes it.
    document.querySelectorAll('[data-modal-open]').forEach(btn =>
        btn.addEventListener('click', () => document.querySelector(btn.dataset.modalOpen)?.showModal()));
    document.querySelectorAll('[data-modal-close]').forEach(btn =>
        btn.addEventListener('click', () => btn.closest('dialog')?.close()));
});


// Card rails (prev/next) and profile menu (close on outside click).
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-rail]').forEach(btn => btn.addEventListener('click', () => {
        const rail = document.querySelector(btn.dataset.rail);
        rail?.scrollBy({ left: (btn.dataset.dir === 'next' ? 1 : -1) * rail.clientWidth * 0.85, behavior: 'smooth' });
    }));
    document.addEventListener('click', e => {
        const open = document.querySelector('.profile[open]');
        if (open && !open.contains(e.target)) open.removeAttribute('open');
    });
});
