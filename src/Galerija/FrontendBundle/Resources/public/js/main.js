$("#input-id").fileinput();
function imageToBase64() {
    var file = document.querySelector('#input-id').files[0];
    var reader  = new FileReader();

    reader.onloadend = function () {
        $("#hidden-base64").val(reader.result.split('base64,')[1]);
    };

    if (file) {
        reader.readAsDataURL(file);
    }
}
document.getElementById('imageList').onclick = function (event) {
    event = event || window.event;
    var target = event.target || event.srcElement,
        link = target.src ? target.parentNode : target,
        options = {index: link, event: event},
        links = this.getElementsByClassName('imageHref');
    blueimp.Gallery(links, options);
};
$(document).ready(function() {
    // bind 'myForm' and provide a simple callback function
    $('#upload-form').ajaxForm(function() {
        location.reload();
    });
});
function deleteImage(id)
{
    event.stopPropagation();
    $.ajax({
        url: 'http://awesome.dev/app_dev.php/images/' + id,
        type: 'DELETE',
        success: function(result) {
            $("#image-" + id).remove();
        }
    });
}