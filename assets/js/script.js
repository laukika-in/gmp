function gmpHandleUpload(inputId, hiddenInputId) {
    const fileInput = document.getElementById(inputId);
    const hiddenInput = document.getElementById(hiddenInputId);

    fileInput.addEventListener('change', function () {
        const file = fileInput.files[0];
        if (!file) return;

        const formData = new FormData();
        formData.append('action', 'gmp_upload_file');
        formData.append('file', file);
        formData.append('nonce', gmp_ajax.nonce);

        fetch(gmp_ajax.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                hiddenInput.value = data.data.url;
            } else {
                alert('Upload failed: ' + data.data.message);
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    gmpHandleUpload('gmp_pan_upload', 'gmp_pan_url');
    gmpHandleUpload('gmp_aadhar_upload', 'gmp_aadhar_url');
    gmpHandleUpload('gmp_nominee_aadhar_upload', 'gmp_nominee_aadhar_url');
});
