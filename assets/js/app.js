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
