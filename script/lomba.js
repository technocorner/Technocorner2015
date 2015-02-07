$(document).ready(function() {
    // Load navigator headbar and footer
    headbar.attachTo('nav#headbar');
    $('footer').load('part.footer.html #footer-inner');

    /*
     * Technical README:
     * Each page contains many section e.g. Description, Rules, Download, etc.
     *
     * Each section has menu-item (on the leftside of page, has class "lomba-menu-item sectionname-here") and it's
     * corresponding article <div> element (on the right of page, contains text, has class "article sectionname-here").
     * See the HTML for implementation.
     */

    /*
     * Once one of menu-item is clicked, it does the following
     */
    $('.lomba-menu-item').click(function(){
        // Detect what section class is it? e.g. is it download?
        sectionClass = '.'                           // Add dot as class notation
                     + $(this).attr('class')         // Get element class list e.g. "lomba-menu-item download"
                     .replace('lomba-menu-item', '') // Remove .lomba-menu-item class from list e.g. " download"
                     .replace('lomba-menu-item-click', '')
                     .replace(/\s/g, '');              // Remove spaces remaining e.g. "download"

        // Hide all article sections
        $('.article').hide(500);

        // Remove menuitem activate class
        $('.lomba-menu-item').removeClass('lomba-menu-item-click');

        // Show a section only, the one we want
        $('.article' + sectionClass).show(500);

        // Activate clicked menu-item
        $(this).addClass('lomba-menu-item-click');

        // Modify address displayed
        history.pushState({/* Empty Object */}, "", "#" + sectionClass.replace('.', ''));
    });

    // Hide all section immediately on load
    $('.article').hide();
    triggerSectionClick();

    // Bind to url hash (e.g. index.html#overview) change
    $(window).bind('hashchange', triggerSectionClick);
});

/*
 * It is a specific function to handle initial section show and hashtag change
 */
function triggerSectionClick() {
    /* Handling url hashtag */
    hash = window.location.hash.replace('#', '');
    target = '.lomba-menu-item.' + hash;

    if (hash == "" || $(target).length == 0) {  // When no hashtag
        // Then simulate first time click to show the first section only
        $('.lomba-menu-item')[0].click();
       // Notify link not found
       alertify.alert("Tautan tidak tersedia", "Mohon maaf, halaman yang berkaitan dengan tautan ini belum tersedia.");
}
}
