$(document).ready(function() {

    $('#excerpt').superslides({});

    $('.galeri-photobox').photobox('a', {
        time:0,
        thumbs:false,
    });

    // Load navigator headbar and footer
    $('nav#headbar').load('part.navigation.html #head-inner');
    $('footer').load('part.footer.html #footer-inner');
});
