$(document).ready(function () {
    $('.galeri-page').photobox('a', {
        time:0,
        thumbs:false,
    });

    // Load navigator headbar and footer
    headbarAttachTo('nav#headbar');
    $('footer').load('part.footer.html #footer-inner');
});
