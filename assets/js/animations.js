/**
 * MindBridge: Animations & Micro-Interactions
 * Modern UI transitions, number counter tweens, and modal helpers.
 */

document.addEventListener('DOMContentLoaded', () => {
  // 1. Number Counter Animation for elements with [data-counter] or numeric stat cards
  const counterElements = document.querySelectorAll('[data-counter], #dash-total-students, #dash-admin-open-requests');
  
  counterElements.forEach(el => {
    const rawValue = el.textContent.trim().replace(/,/g, '');
    const target = parseInt(rawValue, 10);
    if (isNaN(target) || target <= 0) return;

    let start = 0;
    const duration = 800; // ms
    const stepTime = 16;  // ~60fps
    const totalSteps = Math.ceil(duration / stepTime);
    const stepIncrement = target / totalSteps;

    const timer = setInterval(() => {
      start += stepIncrement;
      if (start >= target) {
        el.textContent = target.toLocaleString();
        clearInterval(timer);
      } else {
        el.textContent = Math.floor(start).toLocaleString();
      }
    }, stepTime);
  });

  // 2. SVG Bridge Pulse and Interactive Hover Effects
  const bridgeSvg = document.querySelector('.bridge-illustration-svg');
  if (bridgeSvg) {
    bridgeSvg.classList.add('transition-transform', 'duration-500');
    bridgeSvg.addEventListener('mouseenter', () => {
      bridgeSvg.style.transform = 'scale(1.02)';
    });
    bridgeSvg.addEventListener('mouseleave', () => {
      bridgeSvg.style.transform = 'scale(1)';
    });
  }

  // 3. Close open modals on Escape key
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      const modals = document.querySelectorAll('[id$="-modal"]:not(.hidden)');
      modals.forEach(m => m.classList.add('hidden'));
    }
  });
});
