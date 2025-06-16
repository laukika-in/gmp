/**
 * GMP File Upload Script (AJAX Upload + Error Safe)
 * Handles file input changes, uploads to Media Library, and saves the URL in a hidden field
 */

function gmpHandleUpload(inputId, hiddenInputId) {
  const fileInput = document.getElementById(inputId);
  const hiddenInput = document.getElementById(hiddenInputId);

  // Exit silently if element not found (prevents JS error)
  if (!fileInput || !hiddenInput) return;

  fileInput.addEventListener("change", function () {
    const file = fileInput.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append("action", "gmp_upload_file");
    formData.append("file", file);
    formData.append("nonce", gmp_ajax.nonce);

    fetch(gmp_ajax.ajax_url, {
      method: "POST",
      body: formData,
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.success) {
          hiddenInput.value = data.data.url;
          console.log(`✅ File uploaded: ${data.data.url}`);
        } else {
          console.error(`❌ Upload failed: ${data.data.message}`);
          alert("Upload failed: " + data.data.message);
        }
      })
      .catch((err) => {
        console.error("❌ Upload error:", err);
        alert("Upload failed due to network error.");
      });
  });
}

// Initialize uploads once DOM is ready
document.addEventListener("DOMContentLoaded", () => {
  gmpHandleUpload("gmp_pan_upload", "gmp_pan_url");
  gmpHandleUpload("gmp_aadhar_upload", "gmp_aadhar_url");
  gmpHandleUpload("gmp_nominee_aadhar_upload", "gmp_nominee_aadhar_url");
});
