jQuery(document).ready(function($) {
    // Handle Buy Now
    $('.gmp-buy-now').on('click', function(e) {
        e.preventDefault();
        const $btn = $(this);
        const termsId = $btn.data('terms');
        const $checkbox = $(termsId);

        if (!$checkbox.is(':checked')) {
            alert('Please accept Terms & Conditions before continuing.');
            return;
        }

        window.location.href = $btn.data('url');
    });

    // Handle Terms Link popup
    $('.gmp-terms-link').on('click', function(e) {
        e.preventDefault();
        const termsUrl = $(this).data('url');
        $.fancybox.open({
            src: termsUrl,
            type: 'iframe',
            iframe: { preload: false }
        });
    });
});
