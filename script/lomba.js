$(document).ready(function() {
    $('#content-isi').hide();
    $('#download-isi').hide();
    $('#peraturan').addClass('lomba-menu-click');
    $('#peraturan').click(function(){
        $('#content-isi').hide(500);
        $('#download-isi').hide(500);
        $('#peraturan-isi').show(500);
        $('#content').removeClass('lomba-menu-click');
        $('#download').removeClass('lomba-menu-click');
        $('#peraturan').addClass('lomba-menu-click');
    });
    $('#content').click(function(){
        $('#peraturan-isi').hide(500);
        $('#download-isi').hide(500);
        $('#content-isi').show(500);
        $('#download').removeClass('lomba-menu-click');
        $('#peraturan').removeClass('lomba-menu-click');
        $('#content').addClass('lomba-menu-click');
    });
    $('#download').click(function(){
        $('#content-isi').hide(500);
        $('#peraturan-isi').hide(500);
        $('#download-isi').show(500);
        $('#peraturan').removeClass('lomba-menu-click');
        $('#content').removeClass('lomba-menu-click');
        $('#download').addClass('lomba-menu-click');
    });

    // Load navigator headbar and footer
    $('nav#headbar').load('part.navigation.html #head-inner');
    $('footer').load('part.footer.html #footer-inner');
});
