var selected = [];

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
    $('#upload-form').ajaxForm(function() {location.reload();});
    $('#resize-form, #collage-form, #watermark-form').ajaxForm({
        beforeSubmit: function (data) { beforeModifyRequest(data) },
        success: function (responseText, statusText) { onModifySuccess(responseText, statusText) },
        type: 'PATCH'
    });
});

function beforeModifyRequest(data) {
    data.push({
        name: "image_ids",
        required: false,
        type: "text",
        value: JSON.stringify(selected)
    });
    console.log('Submitting:', $.param(data));
}

function onModifySuccess(responseText, statusText) {
    console.log(responseText, statusText);
    location.reload();
}

function deleteImage(id)
{
    event.stopPropagation();
    $.ajax({
        url: 'http://awesome.dev/app_dev.php/images/' + id,
        type: 'DELETE',
        success: function(result) {
            selected.splice(selected.indexOf(id), 1);
            $("#image-" + id).remove();
        }
    });
}

function addToSelected(id)
{
    event.stopPropagation();
    selected.indexOf(id) === -1 ? selected.push(id) : selected.splice(selected.indexOf(id), 1);
}