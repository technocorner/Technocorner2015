$(document).ready(function () {
    $('.galeri-page').photobox('a', {
        time:0,
        thumbs:false,
    });

    $('nav#headbar').load('part.navigation.html #head-inner');
    $('footer').load('part.footer.html #footer-inner');
});