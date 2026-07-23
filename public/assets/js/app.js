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
});
