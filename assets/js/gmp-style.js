jQuery(document).ready(function($) {
    // ✅ BUY NOW with Terms check
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

    // ✅ TERMS LINK - use Fancybox 4 style
    $('.gmp-terms-link').on('click', function(e) {
        e.preventDefault();
        const url = $(this).data('url');
        Fancybox.show([{ src: url, type: 'iframe' }]);
    });
});
