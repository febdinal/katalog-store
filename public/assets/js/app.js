// public/assets/js/app.js

document.addEventListener('DOMContentLoaded', () => {
  // Confirm Delete Actions
  const deleteForms = document.querySelectorAll('.form-confirm-delete');
  deleteForms.forEach(form => {
    form.addEventListener('submit', (e) => {
      const message = form.dataset.confirm || 'Apakah Anda yakin ingin menghapus data ini?';
      if (!confirm(message)) {
        e.preventDefault();
      }
    });
  });

  // Image Upload Preview
  const imageInput = document.querySelector('input[type="file"][name="image"]');
  const imagePreviewContainer = document.getElementById('image-preview-container');
  if (imageInput && imagePreviewContainer) {
    imageInput.addEventListener('change', (e) => {
      const file = e.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = (event) => {
          imagePreviewContainer.innerHTML = `
            <img src="${event.target.result}" alt="Preview" style="max-height: 160px; border-radius: 8px; border: 1px solid #E2E8F0; object-fit: cover;">
          `;
        };
        reader.readAsDataURL(file);
      }
    });
  }

  // Active Category Scroll-Into-View on Mobile
  const activeCategoryPill = document.querySelector('.category-pill.active');
  if (activeCategoryPill) {
    activeCategoryPill.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
  }

  // ---------------------------------------------------------------------------
  // Background Music Controller (Glassmorphism Floating Player)
  // ---------------------------------------------------------------------------
  const musicAudio = document.getElementById('bg-music-player');
  const musicBtn = document.getElementById('floating-music-btn');

  if (musicAudio && musicBtn) {
    const playIcon = musicBtn.querySelector('.icon-play');
    const pauseIcon = musicBtn.querySelector('.icon-pause');

    // Clean legacy 'paused' state from old code if present
    if (sessionStorage.getItem('bg_music_state') === 'paused') {
      sessionStorage.removeItem('bg_music_state');
    }

    function updateUI(isPlaying) {
      if (isPlaying) {
        musicBtn.classList.add('is-playing');
        if (playIcon) playIcon.style.display = 'none';
        if (pauseIcon) pauseIcon.style.display = 'block';
        musicBtn.setAttribute('title', 'Hentikan Musik Latar');
      } else {
        musicBtn.classList.remove('is-playing');
        if (playIcon) playIcon.style.display = 'block';
        if (pauseIcon) pauseIcon.style.display = 'none';
        musicBtn.setAttribute('title', 'Putar Musik Latar');
      }
    }

    function playAudio() {
      const playPromise = musicAudio.play();
      if (playPromise !== undefined) {
        playPromise.then(() => {
          sessionStorage.setItem('bg_music_state', 'playing');
          updateUI(true);
        }).catch(err => {
          console.warn('Autoplay terhalang oleh kebijakan browser. Musik akan diputar saat interaksi pertama:', err);
          updateUI(false);
        });
      }
    }

    function pauseAudio() {
      musicAudio.pause();
      sessionStorage.setItem('bg_music_state', 'user_paused');
      updateUI(false);
    }

    function toggleAudio() {
      if (musicAudio.paused) {
        sessionStorage.setItem('bg_music_state', 'playing');
        playAudio();
      } else {
        pauseAudio();
      }
    }

    musicBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      toggleAudio();
    });

    const savedState = sessionStorage.getItem('bg_music_state');
    const savedTime = sessionStorage.getItem('bg_music_time');
    const isAutoplayEnabled = musicAudio.dataset.autoplay === '1';

    // Restore saved playback timestamp if available
    if (savedTime && !isNaN(parseFloat(savedTime))) {
      try {
        musicAudio.currentTime = parseFloat(savedTime);
      } catch (e) {}
    }

    // Save playback timestamp periodically
    musicAudio.addEventListener('timeupdate', () => {
      if (!musicAudio.paused) {
        sessionStorage.setItem('bg_music_time', musicAudio.currentTime);
      }
    });

    // If user has not explicitly paused, attempt autoplay
    if (savedState !== 'user_paused' && isAutoplayEnabled) {
      playAudio();
    } else if (savedState === 'playing') {
      playAudio();
    } else {
      updateUI(false);
    }

    // Auto-start audio on the very first user interaction anywhere on the website (click, tap, scroll, keypress)
    const handleFirstUserInteraction = () => {
      if (musicAudio.paused && sessionStorage.getItem('bg_music_state') !== 'user_paused') {
        playAudio();
      }
    };

    ['click', 'touchstart', 'pointerdown', 'scroll', 'keydown'].forEach(evt => {
      document.addEventListener(evt, handleFirstUserInteraction, { passive: true });
    });
  }

  // ---------------------------------------------------------------------------
  // Seamless SPA Navigation (Category Filters & Pagination Links)
  // Keeps background music playing continuously without page reload!
  // ---------------------------------------------------------------------------
  function attachDynamicNavigation() {
    const mainWrapper = document.querySelector('.main-wrapper');
    if (!mainWrapper) return;

    const navLinks = mainWrapper.querySelectorAll('a.category-pill, a.page-item, .empty-state a');
    navLinks.forEach(link => {
      link.addEventListener('click', function (e) {
        const href = this.getAttribute('href');
        if (!href || href.startsWith('http') || href.startsWith('//') || this.getAttribute('target') === '_blank' || href.startsWith('#')) {
          return;
        }

        e.preventDefault();
        loadCatalogPage(href);
      });
    });
  }

  function loadCatalogPage(url, isPopState = false) {
    const mainWrapper = document.querySelector('.main-wrapper');
    if (!mainWrapper) {
      window.location.href = url;
      return;
    }

    // Subtle loading feedback
    mainWrapper.style.opacity = '0.55';
    mainWrapper.style.transition = 'opacity 0.15s ease';

    fetch(url)
      .then(res => res.text())
      .then(html => {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const newMainWrapper = doc.querySelector('.main-wrapper');

        if (newMainWrapper) {
          mainWrapper.innerHTML = newMainWrapper.innerHTML;
          if (!isPopState) {
            history.pushState({}, '', url);
          }
          // Scroll active category into view
          const activePill = mainWrapper.querySelector('.category-pill.active');
          if (activePill) {
            activePill.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
          }
          // Re-bind dynamic SPA link listeners
          attachDynamicNavigation();
        } else {
          window.location.href = url;
        }
      })
      .catch(() => {
        window.location.href = url;
      })
      .finally(() => {
        mainWrapper.style.opacity = '1';
        window.scrollTo({ top: 0, behavior: 'smooth' });
      });
  }

  // Handle Browser Back & Forward Buttons
  window.addEventListener('popstate', () => {
    loadCatalogPage(window.location.pathname + window.location.search, true);
  });

  attachDynamicNavigation();
});


