import './bootstrap';

// Lazy image loader
function initLazyImages() {
    const images = document.querySelectorAll('img.lazy-img[data-src]');
    if (!images.length) return;
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            const img = entry.target;
            img.src = img.dataset.src;
            img.onload = () => { img.classList.add('loaded'); img.removeAttribute('data-src'); };
            observer.unobserve(img);
        });
    }, { rootMargin: '200px 0px', threshold: 0.01 });
    images.forEach(img => observer.observe(img));
}

document.addEventListener('DOMContentLoaded', initLazyImages);
