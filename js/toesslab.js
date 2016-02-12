/**
 * Created by daniel on 1/7/16.
 */
(function ($) {
    $(document).ready(function(){

    });
    $(document).on('click', '#delete_page', function (e) {
        e.preventDefault();
        if (window.confirm('The page will be deleted permanently. Are you sure?')) {
            document.location.href = $(this).attr('href');
        }
    });
}(jQuery));