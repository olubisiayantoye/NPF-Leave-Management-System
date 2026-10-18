document.addEventListener('DOMContentLoaded', function () {
  // Confirm before any destructive action
  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('click', function (e) {
      if (!confirm(el.getAttribute('data-confirm'))) e.preventDefault();
    });
  });

  // Live photo preview on upload forms
  var photoInput = document.getElementById('photo_upload');
  var photoPreview = document.getElementById('photo_preview');
  if (photoInput && photoPreview) {
    photoInput.addEventListener('change', function () {
      if (this.files && this.files[0]) {
        photoPreview.src = URL.createObjectURL(this.files[0]);
      }
    });
  }

  // Auto-submit search filters on change
  document.querySelectorAll('.auto-submit').forEach(function (el) {
    el.addEventListener('change', function () { el.form.submit(); });
  });
});
