// NPF-LMS landing page — small progressive enhancements only.
// Nothing here is required for the page to work.

document.addEventListener('DOMContentLoaded', () => {
    // Smooth-scroll anchor links (native CSS already handles most of this;
    // this just accounts for the sticky header height on shorter viewports).
    document.querySelectorAll('a[href^="#"]').forEach((link) => {
        link.addEventListener('click', (e) => {
            const target = document.querySelector(link.getAttribute('href'));
            if (!target) return;
            e.preventDefault();
            const headerOffset = 72;
            const top = target.getBoundingClientRect().top + window.scrollY - headerOffset;
            window.scrollTo({ top, behavior: 'smooth' });
        });
    });
});
