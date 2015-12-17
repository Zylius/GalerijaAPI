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
    $('#upload-form').ajaxForm({
        beforeSubmit: function (data) { addDropBoxAuth(data) },
        success:function (data) {addImage(data)}
});
    $('#resize-form, #collage-form, #watermark-form').ajaxForm({
        beforeSubmit: function (data) { addDropBoxAuth(data); beforeModifyRequest(data) },
        success: function (responseText, statusText) { onModifySuccess(responseText, statusText) },
        type: 'PATCH'
    });
    $.ajax({
        url: document.location + 'image/all',
        type: 'GET',
        data: {dropbox_auth: $.cookie("dropbox_auth")},
        success: function(result) {
            for(var item in result) {
                addImage(result[item]);
            }
        }
    });
});

function beforeModifyRequest(data) {
    data.push({
        name: "image_ids",
        required: false,
        type: "text",
        value: JSON.stringify(selected),
        dropbox_auth: $.cookie("dropbox_auth")
    });
    console.log('Submitting:', $.param(data));
}

function addDropBoxAuth(data) {
    data.push({
        name: "dropbox_auth",
        required: false,
        type: "text",
        value: $.cookie("dropbox_auth")
    });
}

function onModifySuccess(responseText, statusText) {
    console.log(responseText, statusText);
    responseText = responseText instanceof Array ? responseText : [responseText];

    for(var key in responseText) {
        removeImage(responseText[key].id);
        addImage(responseText[key]);
    }
}

function deleteImage(id)
{
    event.stopPropagation();
    $.ajax({
        url: document.location + 'images/' + id,
        type: 'DELETE',
        data: {dropbox_auth: $.cookie("dropbox_auth")},
        success: function(result) {
            selected.splice(selected.indexOf(id), 1);
            removeImage(id);
        }
    });
}

function addToSelected(id)
{
    event.stopPropagation();
    selected.indexOf(id) === -1 ? selected.push(id) : selected.splice(selected.indexOf(id), 1);
}

function addImage(image)
{
    $("#imageList").append('<div class="record pull-left" id="image-' + image.id + '">' +
        '<div class="image-with-controls list-item">' +
        '<div class="controls" >' +
        '<a href="#" class="control-photo" onclick="deleteImage(' + image.id + ');"><span class="glyphicon glyphicon-remove" ></span></a>' +
        '<a href="#" class="control-photo" onclick="$( \'#edit-dialog-' + image.id + '\').dialog( \'open\' );    event.stopPropagation();" ><span class="glyphicon glyphicon-pencil"></span></a>' +
        '<input class="control-photo" onclick="addToSelected(' + image.id + ')" type="checkbox" name="' + image.id + '" value="' + image.id + '">' +
        '</div>' +
        '<a class="imageHref" href="data:image/png;base64,' + image.imageData + '">' +
        '<img alt="' + image.title + '" src="data:image/png;base64,' + image.imageData + '"/>' +
        '</a></div></div>',
        '<div id="edit-dialog-' + image.id + '" title="Edit image title">' +
        '<form role="form" action="' + document.location + 'images/' + image.id + '" id="image-title-edit-form' + image.id + '"> \
        <div class="form-group"> \
        <label for="title-image-' + image.id + '">Title:</label> \
        <input type="text" class="form-control" name="title" id="title-image-' + image.id + '" value="'+ image.title +'"> \
        <input type="hidden" name="data" id="title-data-' + image.id + '" value="'+ image.imageData +'">\
        </div> \
        <button type="submit" class="btn btn-default">Submit</button> \
        </form>' +
        '</div>'
    );

    $('#image-title-edit-form' + image.id).ajaxForm({
        beforeSubmit: function (data) { addDropBoxAuth(data) },
        success:function (data, status) {
            onModifySuccess(data, status);
            $( '#edit-dialog-' + image.id ).dialog( 'close' );
        },
        type: 'PUT'
    });
    $( "#edit-dialog-" + image.id ).dialog({
        autoOpen: false
    });
    $("#watermark").append('<option id="image-watermark-' + image.id + '" value="' + image.id + '"> ' + image.title + '</option>');
}

function removeImage(id)
{
    $("#image-" + id).remove();
    $("#image-watermark-" + id).remove();
}