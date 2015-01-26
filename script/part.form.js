/*
 * Toggle input element between disable and enable
 * @param element: jQuery selector
 * @param status: "on" and "off" value
 */
function toggleElement(element, status) {
    if (status == 'on') {
        $(element).removeAttr("disabled");
    } else {
        $(element).attr("disabled", "");
    }
}

$(document).ready(function () {
    alertify.set('notifier','delay', 10);
    alertify.set('notifier','position', 'bottom-right');

    $('#captcha').slideToCAPTCHA();

    /*
     * Set submit form callback
     */
    $('form').submit(function(event){
        event.preventDefault();

        // Captcha response is either blank or true
        // If captcha blank (wrong)
        if ($(this).attr('data-valid') !== 'true') {
            alertify.error('Captcha wajib diisi');
            return;
        }

        submitForm('#' + $(this)[0].id);
    });
});

function submitForm(form) {
    alertify.notify('Formulir sedang dikirim dan diproses...');
    var formData = new FormData($(form)[0]);
    formData.append("formId", form);

    // Send ajax request
    var request = $.ajax({
        url: "script/form-processor.php",
        type: "POST",
        data: formData,
        dataType: "json",
        success: onFormSuccessSubmission,
        error: onFormErrorSubmission,
        // Options to tell jQuery not to process data or worry about content-type.
        cache: false,
        contentType: false,
        processData: false
    });
}

function onFormSuccessSubmission(response) {
    // Check form submission
    if (response.success) {
        alertify.success('Terima kasih, form telah diterima!');
    } else {
        alertify.error('Mohon ulangi pengiriman');
    }

    grecaptcha.reset();
}

function onFormErrorSubmission(msg) {
    alertify.error('Mohon ulangi pengiriman');
}
