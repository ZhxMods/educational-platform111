/**
 * js/xp-system.js — XP System with Animations & Confetti
 * Handles XP claiming, level-up modals, progress bar updates
 */

(function() {
  'use strict';

  // ═══════════════════════════════════════════════════════════════════════
  //  CONFIGURATION
  // ═══════════════════════════════════════════════════════════════════════

  const XP_PER_LEVEL = 100; // Must match backend constant

  // ═══════════════════════════════════════════════════════════════════════
  //  COMPLETE LESSON BUTTON HANDLER
  // ═══════════════════════════════════════════════════════════════════════

  const completeBtn = document.querySelector('[data-complete-lesson]');
  
  if (completeBtn) {
    completeBtn.addEventListener('click', async function() {
      if (this.disabled) return;

      const lessonId = parseInt(this.dataset.lessonId);
      const xpReward = parseInt(this.dataset.xpReward);

      if (!lessonId) {
        showToast('error', 'Invalid lesson ID');
        return;
      }

      // Disable button during request
      this.disabled = true;
      const originalText = this.innerHTML;
      this.innerHTML = '<i data-lucide="loader-2" width="20" height="20" class="spin"></i> Processing...';
      lucide.createIcons();

      try {
        const response = await fetch('/ajax/complete_lesson.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-Token': getCSRFToken()
          },
          body: new URLSearchParams({
            lesson_id: lessonId,
            csrf_token: getCSRFToken()
          })
        });

        const data = await response.json();

        if (data.success) {
          // Update XP counter
          updateXPDisplay(data.xp_points);

          // Update level badge
          updateLevelDisplay(data.current_level);

          // Update XP progress bar
          updateXPProgressBar(data.xp_progress, data.xp_points);

          // Check for level up
          if (data.leveled_up) {
            showLevelUpModal(data.old_level, data.new_level, data.xp_points);
          } else {
            showToast('success', `+${xpReward} XP earned! 🎉`, 5000);
          }

          // Update button to "completed" state
          this.classList.remove('btn-complete-lesson');
          this.classList.add('btn-lesson', 'done');
          this.innerHTML = '<i data-lucide="check-circle-2" width="20" height="20"></i> Leçon Terminée';
          this.disabled = true;
          lucide.createIcons();

        } else {
          // Handle errors
          if (data.message === 'already_completed') {
            showToast('info', 'You already completed this lesson', 3000);
            this.classList.add('btn-lesson', 'done');
            this.innerHTML = '<i data-lucide="check-circle-2" width="20" height="20"></i> Leçon Terminée';
            this.disabled = true;
          } else {
            showToast('error', data.message || 'Failed to claim XP');
            this.disabled = false;
            this.innerHTML = originalText;
          }
          lucide.createIcons();
        }

      } catch (error) {
        console.error('XP claim error:', error);
        showToast('error', 'Network error. Please try again.');
        this.disabled = false;
        this.innerHTML = originalText;
        lucide.createIcons();
      }
    });
  }

  // ═══════════════════════════════════════════════════════════════════════
  //  HELPER FUNCTIONS
  // ═══════════════════════════════════════════════════════════════════════

  /**
   * Get CSRF token from meta tag
   */
  function getCSRFToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
  }

  /**
   * Update XP counter displays
   */
  function updateXPDisplay(newXP) {
    const counters = document.querySelectorAll('[data-xp-counter]');
    counters.forEach(counter => {
      animateNumber(counter, parseInt(counter.textContent.replace(/,/g, '')), newXP);
    });
  }

  /**
   * Update level badge displays
   */
  function updateLevelDisplay(newLevel) {
    const badges = document.querySelectorAll('[data-level-display]');
    badges.forEach(badge => {
      badge.textContent = newLevel;
    });
  }

  /**
   * Update XP progress bar
   */
  function updateXPProgressBar(percentage, currentXP) {
    const fills = document.querySelectorAll('.xp-bar-fill');
    fills.forEach(fill => {
      fill.style.width = percentage + '%';
    });

    const labels = document.querySelectorAll('[data-xp-bar-label]');
    labels.forEach(label => {
      const xpToNext = XP_PER_LEVEL - (currentXP % XP_PER_LEVEL);
      label.textContent = `${currentXP.toLocaleString()} XP • ${Math.round(percentage)}% au niveau suivant`;
    });

    const toNextLabels = document.querySelectorAll('[data-xp-to-next]');
    toNextLabels.forEach(label => {
      const xpToNext = XP_PER_LEVEL - (currentXP % XP_PER_LEVEL);
      label.textContent = `${xpToNext} XP to next level`;
    });
  }

  /**
   * Animate number counter
   */
  function animateNumber(element, start, end) {
    const duration = 1500;
    const startTime = performance.now();

    function update(currentTime) {
      const elapsed = currentTime - startTime;
      const progress = Math.min(elapsed / duration, 1);

      // Easing function (ease-out-cubic)
      const easeOut = 1 - Math.pow(1 - progress, 3);
      const current = Math.floor(start + (end - start) * easeOut);

      element.textContent = current.toLocaleString();

      if (progress < 1) {
        requestAnimationFrame(update);
      }
    }

    requestAnimationFrame(update);
  }

  /**
   * Show level-up modal with confetti
   */
  function showLevelUpModal(oldLevel, newLevel, newXP) {
    // Create modal
    const modal = document.createElement('div');
    modal.className = 'levelup-modal-overlay';
    modal.innerHTML = `
      <div class="levelup-modal">
        <div class="levelup-icon">
          <i data-lucide="trophy" width="64" height="64" color="#f59e0b"></i>
        </div>
        <h2>🎉 Level Up! 🎉</h2>
        <p class="levelup-text">Congratulations! You've reached</p>
        <div class="levelup-badge">Level ${newLevel}</div>
        <p class="levelup-xp">${newXP.toLocaleString()} XP</p>
        <button class="btn-levelup-close" onclick="this.closest('.levelup-modal-overlay').remove();">
          Continue
        </button>
      </div>
    `;

    document.body.appendChild(modal);
    lucide.createIcons();

    // Trigger confetti
    setTimeout(() => {
      triggerConfetti();
    }, 300);

    // Play sound (optional - add your own sound file)
    // playSound('/sounds/levelup.mp3');
  }

  /**
   * Trigger confetti animation
   */
  function triggerConfetti() {
    const duration = 3000;
    const end = Date.now() + duration;

    (function frame() {
      confetti({
        particleCount: 3,
        angle: 60,
        spread: 55,
        origin: { x: 0 },
        colors: ['#3b82f6', '#f59e0b', '#10b981', '#8b5cf6']
      });
      confetti({
        particleCount: 3,
        angle: 120,
        spread: 55,
        origin: { x: 1 },
        colors: ['#3b82f6', '#f59e0b', '#10b981', '#8b5cf6']
      });

      if (Date.now() < end) {
        requestAnimationFrame(frame);
      }
    })();
  }

  /**
   * Show toast notification
   */
  function showToast(type, message, duration = 3000) {
    // Remove existing toasts
    const existing = document.querySelectorAll('.toast-notification');
    existing.forEach(t => t.remove());

    // Create toast
    const toast = document.createElement('div');
    toast.className = `toast-notification toast-${type}`;

    const icons = {
      success: 'check-circle-2',
      error: 'alert-circle',
      info: 'info',
      warning: 'alert-triangle'
    };

    toast.innerHTML = `
      <i data-lucide="${icons[type] || 'info'}" width="20" height="20"></i>
      <span>${message}</span>
    `;

    document.body.appendChild(toast);
    lucide.createIcons();

    // Animate in
    setTimeout(() => toast.classList.add('show'), 10);

    // Auto-remove
    setTimeout(() => {
      toast.classList.remove('show');
      setTimeout(() => toast.remove(), 300);
    }, duration);
  }

  // ═══════════════════════════════════════════════════════════════════════
  //  CONFETTI LIBRARY (Canvas Confetti)
  // ═══════════════════════════════════════════════════════════════════════

  /**
   * Lightweight confetti implementation
   * Based on canvas-confetti by catdad
   */
  window.confetti = (function() {
    const canvas = document.createElement('canvas');
    canvas.style.position = 'fixed';
    canvas.style.top = '0';
    canvas.style.left = '0';
    canvas.style.width = '100%';
    canvas.style.height = '100%';
    canvas.style.pointerEvents = 'none';
    canvas.style.zIndex = '9999';
    document.body.appendChild(canvas);

    const ctx = canvas.getContext('2d');
    let particles = [];
    let animationFrame;

    function resize() {
      canvas.width = window.innerWidth;
      canvas.height = window.innerHeight;
    }
    resize();
    window.addEventListener('resize', resize);

    function Particle(opts) {
      this.x = opts.x;
      this.y = opts.y;
      this.vx = opts.vx;
      this.vy = opts.vy;
      this.color = opts.color;
      this.size = opts.size;
      this.rotation = Math.random() * 360;
      this.rotationSpeed = (Math.random() - 0.5) * 10;
    }

    Particle.prototype.update = function() {
      this.x += this.vx;
      this.y += this.vy;
      this.vy += 0.3; // gravity
      this.rotation += this.rotationSpeed;
    };

    Particle.prototype.draw = function() {
      ctx.save();
      ctx.translate(this.x, this.y);
      ctx.rotate((this.rotation * Math.PI) / 180);
      ctx.fillStyle = this.color;
      ctx.fillRect(-this.size / 2, -this.size / 2, this.size, this.size);
      ctx.restore();
    };

    function animate() {
      ctx.clearRect(0, 0, canvas.width, canvas.height);

      for (let i = particles.length - 1; i >= 0; i--) {
        particles[i].update();
        particles[i].draw();

        if (particles[i].y > canvas.height) {
          particles.splice(i, 1);
        }
      }

      if (particles.length > 0) {
        animationFrame = requestAnimationFrame(animate);
      }
    }

    return function(opts) {
      opts = opts || {};
      const particleCount = opts.particleCount || 50;
      const angle = (opts.angle || 90) * (Math.PI / 180);
      const spread = (opts.spread || 45) * (Math.PI / 180);
      const origin = opts.origin || { x: 0.5, y: 0.5 };
      const colors = opts.colors || ['#f00', '#0f0', '#00f'];

      for (let i = 0; i < particleCount; i++) {
        const velocity = 5 + Math.random() * 5;
        const angleOffset = (Math.random() - 0.5) * spread;
        const finalAngle = angle + angleOffset;

        particles.push(new Particle({
          x: canvas.width * origin.x,
          y: canvas.height * origin.y,
          vx: Math.cos(finalAngle) * velocity,
          vy: -Math.sin(finalAngle) * velocity,
          color: colors[Math.floor(Math.random() * colors.length)],
          size: 8 + Math.random() * 4
        }));
      }

      if (!animationFrame) {
        animate();
      }
    };
  })();

  // ═══════════════════════════════════════════════════════════════════════
  //  EXPOSE GLOBAL API
  // ═══════════════════════════════════════════════════════════════════════

  window.XPSystem = {
    showToast: showToast,
    triggerConfetti: triggerConfetti,
    updateXPDisplay: updateXPDisplay,
    updateLevelDisplay: updateLevelDisplay
  };

})();
