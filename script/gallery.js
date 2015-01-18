$(document).ready(function () {
    $('.galeri-page').photobox('a', {
        time:0,
        thumbs:false,
    });

    // Load navigator headbar and footer
    headbar.attachTo('nav#headbar');
    $('footer').load('part.footer.html #footer-inner');
});
