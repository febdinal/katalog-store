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
      sessionStorage.setItem('bg_music_state', 'paused');
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
    const isAutoplayEnabled = musicAudio.dataset.autoplay === '1';

    // If user has not explicitly paused, attempt autoplay
    if (savedState !== 'paused' && isAutoplayEnabled) {
      playAudio();
    } else if (savedState === 'playing') {
      playAudio();
    } else {
      updateUI(false);
    }

    // Auto-start audio on the very first user interaction anywhere on the website if browser blocked initial load play
    const handleFirstUserInteraction = () => {
      if (musicAudio.paused && sessionStorage.getItem('bg_music_state') !== 'paused') {
        playAudio();
      }
      removeInteractionListeners();
    };

    function removeInteractionListeners() {
      ['click', 'touchstart', 'pointerdown', 'scroll', 'keydown'].forEach(evt => {
        document.removeEventListener(evt, handleFirstUserInteraction);
      });
    }

    if (savedState !== 'paused') {
      ['click', 'touchstart', 'pointerdown', 'scroll', 'keydown'].forEach(evt => {
        document.addEventListener(evt, handleFirstUserInteraction, { once: true, passive: true });
      });
    }
  }
});

