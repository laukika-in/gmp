/**
 * Gold Money Plan File Upload with Progress & Preview
 */

function gmpHandleUpload(inputId, hiddenInputId) {
  const fileInput = document.getElementById(inputId);
  const hiddenInput = document.getElementById(hiddenInputId);

  // Exit if the element doesn't exist
  if (!fileInput || !hiddenInput) return;

  fileInput.addEventListener("change", function () {
    const file = fileInput.files[0];
    if (!file) return;

    // Create progress bar
    const progressBar = document.createElement("progress");
    progressBar.max = 100;
    progressBar.value = 0;
    progressBar.style.display = "block";
    progressBar.style.marginTop = "6px";
    fileInput.parentElement.appendChild(progressBar);

    const formData = new FormData();
    formData.append("action", "gmp_upload_file");
    formData.append("file", file);
    formData.append("nonce", gmp_ajax.nonce);

    const xhr = new XMLHttpRequest();
    xhr.open("POST", gmp_ajax.ajax_url, true);

    xhr.upload.onprogress = function (e) {
      if (e.lengthComputable) {
        const percent = (e.loaded / e.total) * 100;
        progressBar.value = percent;
      }
    };

    xhr.onload = function () {
      const res = JSON.parse(xhr.responseText);
      if (res.success) {
        hiddenInput.value = res.data.url;

        // Hide progress bar
        progressBar.style.display = "none";

        // Show preview link
        const preview = document.createElement("a");
        preview.href = res.data.url;
        preview.target = "_blank";
        preview.textContent = "View Uploaded File";
        preview.style.display = "block";
        preview.style.marginTop = "4px";
        fileInput.parentElement.appendChild(preview);
      } else {
        alert("Upload failed: " + (res.data.message || "Unknown error"));
        progressBar.style.display = "none";
      }
    };

    xhr.onerror = function () {
      alert("Upload failed due to network error.");
      progressBar.style.display = "none";
    };

    xhr.send(formData);
  });
}

document.addEventListener("DOMContentLoaded", () => {
  gmpHandleUpload("gmp_pan_upload", "gmp_pan_url");
  gmpHandleUpload("gmp_aadhar_upload", "gmp_aadhar_url");
  gmpHandleUpload("gmp_nominee_aadhar_upload", "gmp_nominee_aadhar_url");
});
