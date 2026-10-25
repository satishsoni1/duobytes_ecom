//custom jquery method for toggle attr
$.fn.toggleAttr = function (attr, attr1, attr2) {
    return this.each(function () {
        var self = $(this);
        if (self.attr(attr) == attr1) self.attr(attr, attr2);
        else self.attr(attr, attr1);
    });
};
(function ($) {
    // USE STRICT
    "use strict";

    AIZ.data = {
        csrf: $('meta[name="csrf-token"]').attr("content"),
        appUrl: $('meta[name="app-url"]').attr("content"),
        fileBaseUrl: $('meta[name="file-base-url"]').attr("content"),
    };
    AIZ.uploader = {
        data: {
            selectedFiles: [],
            selectedFilesObject: [],
            clickedForDelete: null,
            allFiles: [],
            multiple: false,
            type: "all",
            next_page_url: null,
            prev_page_url: null,
        },
        removeInputValue: function (id, array, elem) {
            var selected = array.filter(function (item) {
                return item !== id;
            });
            if (selected.length > 0) {
                $(elem)
                    .find(".file-amount")
                    .html(AIZ.uploader.updateFileHtml(selected));
            } else {
                elem.find(".file-amount").html(AIZ.local.choose_file);
            }
            $(elem).find(".selected-files").val(selected);
        },
        removeAttachment: function () {
            $(document).on("click", ".remove-attachment", function () {
                var value = $(this).closest(".file-preview-item").data("id");
                var selected = $(this)
                    .closest(".file-preview")
                    .prev('[data-toggle="aizuploader"]')
                    .find(".selected-files")
                    .val()
                    .split(",")
                    .map(Number);

                AIZ.uploader.removeInputValue(
                    value,
                    selected,
                    $(this)
                        .closest(".file-preview")
                        .prev('[data-toggle="aizuploader"]')
                );
                $(this).closest(".file-preview-item").remove();
            });
        },
        deleteUploaderFile: function () {
            $(".aiz-uploader-delete").each(function () {
                $(this).on("click", function (e) {
                    e.preventDefault();
                    var id = $(this).data("id");
                    AIZ.uploader.data.clickedForDelete = id;
                    $("#aizUploaderDelete").modal("show");

                    $(".aiz-uploader-confirmed-delete").on(
                        "click",
                        function (e) {
                            e.preventDefault();
                            if (e.detail === 1) {
                                var clickedForDeleteObject =
                                    AIZ.uploader.data.allFiles[
                                        AIZ.uploader.data.allFiles.findIndex(
                                            (x) =>
                                                x.id ===
                                                AIZ.uploader.data
                                                    .clickedForDelete
                                        )
                                    ];
                                $.ajax({
                                    url:
                                        AIZ.data.appUrl +
                                        "/aiz-uploader/destroy/" +
                                        AIZ.uploader.data.clickedForDelete,
                                    type: "DELETE",
                                    dataType: "JSON",
                                    data: {
                                        id: AIZ.uploader.data.clickedForDelete,
                                        _method: "DELETE",
                                        _token: AIZ.data.csrf,
                                    },
                                    success: function () {
                                        AIZ.uploader.data.selectedFiles =
                                            AIZ.uploader.data.selectedFiles.filter(
                                                function (item) {
                                                    return (
                                                        item !==
                                                        AIZ.uploader.data
                                                            .clickedForDelete
                                                    );
                                                }
                                            );
                                        AIZ.uploader.data.selectedFilesObject =
                                            AIZ.uploader.data.selectedFilesObject.filter(
                                                function (item) {
                                                    return (
                                                        item !==
                                                        clickedForDeleteObject
                                                    );
                                                }
                                            );
                                        AIZ.uploader.updateUploaderSelected();
                                        AIZ.uploader.getAllUploads(
                                            AIZ.data.appUrl +
                                                "/aiz-uploader/get-uploaded-files"
                                        );
                                        AIZ.uploader.data.clickedForDelete =
                                            null;
                                        $("#aizUploaderDelete").modal("hide");
                                    },
                                });
                            }
                        }
                    );
                });
            });
        },
        uploadSelect: function () {
            $(".aiz-uploader-select").each(function () {
                var elem = $(this);
                elem.on("click", function (e) {
                    var value = $(this).data("value");
                    var valueObject =
                        AIZ.uploader.data.allFiles[
                            AIZ.uploader.data.allFiles.findIndex(
                                (x) => x.id === value
                            )
                        ];
                    // console.log(valueObject);

                    elem.closest(".aiz-file-box-wrap").toggleAttr(
                        "data-selected",
                        "true",
                        "false"
                    );
                    if (!AIZ.uploader.data.multiple) {
                        elem.closest(".aiz-file-box-wrap")
                            .siblings()
                            .attr("data-selected", "false");
                    }
                    if (!AIZ.uploader.data.selectedFiles.includes(value)) {
                        if (!AIZ.uploader.data.multiple) {
                            AIZ.uploader.data.selectedFiles = [];
                            AIZ.uploader.data.selectedFilesObject = [];
                        }
                        AIZ.uploader.data.selectedFiles.push(value);
                        AIZ.uploader.data.selectedFilesObject.push(valueObject);
                    } else {
                        AIZ.uploader.data.selectedFiles =
                            AIZ.uploader.data.selectedFiles.filter(function (
                                item
                            ) {
                                return item !== value;
                            });
                        AIZ.uploader.data.selectedFilesObject =
                            AIZ.uploader.data.selectedFilesObject.filter(
                                function (item) {
                                    return item !== valueObject;
                                }
                            );
                    }
                    AIZ.uploader.addSelectedValue();
                    AIZ.uploader.updateUploaderSelected();
                });
            });
        },
        updateFileHtml: function (array) {
            var fileText = "";
            if (array.length > 1) {
                var fileText = AIZ.local.files_selected;
            } else {
                var fileText = AIZ.local.file_selected;
            }
            return array.length + " " + fileText;
        },
        updateUploaderSelected: function () {
            $(".aiz-uploader-selected").html(
                AIZ.uploader.updateFileHtml(AIZ.uploader.data.selectedFiles)
            );
        },
        clearUploaderSelected: function () {
            $(".aiz-uploader-selected-clear").on("click", function () {
                AIZ.uploader.data.selectedFiles = [];
                AIZ.uploader.addSelectedValue();
                AIZ.uploader.addHiddenValue();
                AIZ.uploader.resetFilter();
                AIZ.uploader.updateUploaderSelected();
                AIZ.uploader.updateUploaderFiles();
            });
        },
        resetFilter: function () {
            $('[name="aiz-uploader-search"]').val("");
            $('[name="aiz-show-selected"]').prop("checked", false);
            $('[name="aiz-uploader-sort"] option[value=newest]').prop(
                "selected",
                true
            );
        },
        getAllUploads: function (url, search_key = null, sort_key = null) {
            $(".aiz-uploader-all").html(
                '<div class="align-items-center d-flex h-100 justify-content-center w-100"><div class="spinner-border" role="status"></div></div>'
            );
            var params = {};
            if (search_key != null && search_key.length > 0) {
                params["search"] = search_key;
            }
            if (sort_key != null && sort_key.length > 0) {
                params["sort"] = sort_key;
            } else {
                params["sort"] = "newest";
            }
            $.get(url, params, function (data, status) {
                //console.log(data);
                if (typeof data == "string") {
                    data = JSON.parse(data);
                }
                AIZ.uploader.data.allFiles = data.data;
                AIZ.uploader.allowedFileType();
                AIZ.uploader.addSelectedValue();
                AIZ.uploader.addHiddenValue();
                //AIZ.uploader.resetFilter();
                AIZ.uploader.updateUploaderFiles();
                if (data.next_page_url != null) {
                    AIZ.uploader.data.next_page_url = data.next_page_url;
                    $("#uploader_next_btn").removeAttr("disabled");
                } else {
                    $("#uploader_next_btn").attr("disabled", true);
                }
                if (data.prev_page_url != null) {
                    AIZ.uploader.data.prev_page_url = data.prev_page_url;
                    $("#uploader_prev_btn").removeAttr("disabled");
                } else {
                    $("#uploader_prev_btn").attr("disabled", true);
                }
            });
        },
        showSelectedFiles: function () {
            $('[name="aiz-show-selected"]').on("change", function () {
                if ($(this).is(":checked")) {
                    // for (
                    //     var i = 0;
                    //     i < AIZ.uploader.data.allFiles.length;
                    //     i++
                    // ) {
                    //     if (AIZ.uploader.data.allFiles[i].selected) {
                    //         AIZ.uploader.data.allFiles[
                    //             i
                    //         ].aria_hidden = false;
                    //     } else {
                    //         AIZ.uploader.data.allFiles[
                    //             i
                    //         ].aria_hidden = true;
                    //     }
                    // }
                    AIZ.uploader.data.allFiles =
                        AIZ.uploader.data.selectedFilesObject;
                } else {
                    // for (
                    //     var i = 0;
                    //     i < AIZ.uploader.data.allFiles.length;
                    //     i++
                    // ) {
                    //     AIZ.uploader.data.allFiles[
                    //         i
                    //     ].aria_hidden = false;
                    // }
                    AIZ.uploader.getAllUploads(
                        AIZ.data.appUrl + "/aiz-uploader/get-uploaded-files"
                    );
                }
                AIZ.uploader.updateUploaderFiles();
            });
        },
        searchUploaderFiles: function () {
            $('[name="aiz-uploader-search"]').on("keyup", function () {
                var value = $(this).val();
                AIZ.uploader.getAllUploads(
                    AIZ.data.appUrl + "/aiz-uploader/get-uploaded-files",
                    value,
                    $('[name="aiz-uploader-sort"]').val()
                );
                // if (AIZ.uploader.data.allFiles.length > 0) {
                //     for (
                //         var i = 0;
                //         i < AIZ.uploader.data.allFiles.length;
                //         i++
                //     ) {
                //         if (
                //             AIZ.uploader.data.allFiles[
                //                 i
                //             ].file_original_name
                //                 .toUpperCase()
                //                 .indexOf(value) > -1
                //         ) {
                //             AIZ.uploader.data.allFiles[
                //                 i
                //             ].aria_hidden = false;
                //         } else {
                //             AIZ.uploader.data.allFiles[
                //                 i
                //             ].aria_hidden = true;
                //         }
                //     }
                // }
                //AIZ.uploader.updateUploaderFiles();
            });
        },
        sortUploaderFiles: function () {
            $('[name="aiz-uploader-sort"]').on("change", function () {
                var value = $(this).val();
                AIZ.uploader.getAllUploads(
                    AIZ.data.appUrl + "/aiz-uploader/get-uploaded-files",
                    $('[name="aiz-uploader-search"]').val(),
                    value
                );

                // if (value === "oldest") {
                //     AIZ.uploader.data.allFiles = AIZ.uploader.data.allFiles.sort(
                //         function(a, b) {
                //             return (
                //                 new Date(a.created_at) - new Date(b.created_at)
                //             );
                //         }
                //     );
                // } else if (value === "smallest") {
                //     AIZ.uploader.data.allFiles = AIZ.uploader.data.allFiles.sort(
                //         function(a, b) {
                //             return a.file_size - b.file_size;
                //         }
                //     );
                // } else if (value === "largest") {
                //     AIZ.uploader.data.allFiles = AIZ.uploader.data.allFiles.sort(
                //         function(a, b) {
                //             return b.file_size - a.file_size;
                //         }
                //     );
                // } else {
                //     AIZ.uploader.data.allFiles = AIZ.uploader.data.allFiles.sort(
                //         function(a, b) {
                //             a = new Date(a.created_at);
                //             b = new Date(b.created_at);
                //             return a > b ? -1 : a < b ? 1 : 0;
                //         }
                //     );
                // }
                //AIZ.uploader.updateUploaderFiles();
            });
        },
        addSelectedValue: function () {
            for (var i = 0; i < AIZ.uploader.data.allFiles.length; i++) {
                if (
                    !AIZ.uploader.data.selectedFiles.includes(
                        AIZ.uploader.data.allFiles[i].id
                    )
                ) {
                    AIZ.uploader.data.allFiles[i].selected = false;
                } else {
                    AIZ.uploader.data.allFiles[i].selected = true;
                }
            }
        },
        addHiddenValue: function () {
            for (var i = 0; i < AIZ.uploader.data.allFiles.length; i++) {
                AIZ.uploader.data.allFiles[i].aria_hidden = false;
            }
        },
        allowedFileType: function () {
            if (AIZ.uploader.data.type !== "all") {
                let type = AIZ.uploader.data.type.split(",");
                AIZ.uploader.data.allFiles = AIZ.uploader.data.allFiles.filter(
                    function (item) {
                        return type.includes(item.type);
                    }
                );
            }
        },
        updateUploaderFiles: function () {
            $(".aiz-uploader-all").html(
                '<div class="align-items-center d-flex h-100 justify-content-center w-100"><div class="spinner-border" role="status"></div></div>'
            );

            var data = AIZ.uploader.data.allFiles;

            setTimeout(function () {
                $(".aiz-uploader-all").html(null);

                if (data.length > 0) {
                    for (var i = 0; i < data.length; i++) {
                        var thumb = "";
                        var hidden = "";
                        if (data[i].type === "image") {
                            thumb =
                                '<img src="' +
                                AIZ.data.fileBaseUrl +
                                data[i].file_name +
                                '" class="img-fit">';
                        } else if(data[i].type === 'video') {
                            thumb = '<video width="100%" height="100%" controls><source src="'+AIZ.data.fileBaseUrl +  data[i].file_name +'" type="video/mp4">Your browser does not support the video tag.</video>'
                        } else {
                            thumb = '<i class="la la-file-text"></i>';
                        }
                        var html =
                            '<div class="aiz-file-box-wrap" aria-hidden="' +
                            data[i].aria_hidden +
                            '" data-selected="' +
                            data[i].selected +
                            '">' +
                            '<div class="aiz-file-box">' +
                            // '<div class="dropdown-file">' +
                            // '<a class="dropdown-link" data-toggle="dropdown">' +
                            // '<i class="la la-ellipsis-v"></i>' +
                            // "</a>" +
                            // '<div class="dropdown-menu dropdown-menu-right">' +
                            // '<a href="' +
                            // AIZ.data.fileBaseUrl +
                            // data[i].file_name +
                            // '" target="_blank" download="' +
                            // data[i].file_original_name +
                            // "." +
                            // data[i].extension +
                            // '" class="dropdown-item"><i class="la la-download mr-2"></i>Download</a>' +
                            // '<a href="#" class="dropdown-item aiz-uploader-delete" data-id="' +
                            // data[i].id +
                            // '"><i class="la la-trash mr-2"></i>Delete</a>' +
                            // "</div>" +
                            // "</div>" +
                            '<div class="card card-file aiz-uploader-select" title="' +
                            data[i].file_original_name +
                            "." +
                            data[i].extension +
                            '" data-value="' +
                            data[i].id +
                            '">' +
                            '<div class="card-file-thumb">' +
                            thumb +
                            "</div>" +
                            '<div class="card-body ">' +
                            '<h6 class="d-flex">' +
                            '<span class="text-truncate title">' +
                            data[i].file_original_name +
                            "</span>" +
                            '<span class="ext flex-shrink-0">.' +
                            data[i].extension +
                            "</span>" +
                            "</h6>" +
                            "<p>" +
                            AIZ.extra.bytesToSize(data[i].file_size) +
                            "</p>" +
                            "</div>" +
                            "</div>" +
                            "</div>" +
                            "</div>";

                        $(".aiz-uploader-all").append(html);
                    }
                } else {
                    $(".aiz-uploader-all").html(
                        '<div class="align-items-center d-flex h-100 justify-content-center w-100 nav-tabs"><div class="text-center"><h3>No files found</h3></div></div>'
                    );
                }
                AIZ.uploader.uploadSelect();
                AIZ.uploader.deleteUploaderFile();
            }, 300);
        },
        inputSelectPreviewGenerate: function (elem) {
            elem.find(".selected-files").val(AIZ.uploader.data.selectedFiles);
            elem.next(".file-preview").html(null);

            if (AIZ.uploader.data.selectedFiles.length > 0) {
                $.post(
                    AIZ.data.appUrl + "/aiz-uploader/get_file_by_ids",
                    {
                        _token: AIZ.data.csrf,
                        ids: AIZ.uploader.data.selectedFiles.toString(),
                    },
                    function (data) {
                        elem.next(".file-preview").html(null);

                        if (data.length > 0) {
                            elem.find(".file-amount").html(
                                AIZ.uploader.updateFileHtml(data)
                            );
                            for (var i = 0; i < data.length; i++) {
                                var thumb = "";
                                if (data[i].type === "image") {
                                    thumb =
                                        '<img src="' +
                                        data[i].file_name +
                                        '" class="img-fit">';
                                }else if(data[i].type === 'video') {
                                    thumb = '<video width="320" height="240" controls><source src="'+ data[i].file_name +'" type="video/mp4">Your browser does not support the video tag.</video>'
                                } else {
                                    thumb = '<i class="la la-file-text"></i>';
                                }
                                var html =
                                    '<div class="d-flex justify-content-between align-items-center mt-2 file-preview-item" data-id="' +
                                    data[i].id +
                                    '" title="' +
                                    data[i].file_original_name +
                                    "." +
                                    data[i].extension +
                                    '">' +
                                    '<div class="align-items-center align-self-stretch d-flex justify-content-center thumb">' +
                                    thumb +
                                    "</div>" +
                                    '<div class="col body">' +
                                    '<h6 class="d-flex">' +
                                    '<span class=" text-truncate " >' +
                                    data[i].file_original_name +
                                    "</span>" +
                                    '<span class="flex-shrink-0 ext">.' +
                                    data[i].extension +
                                    "</span>" +
                                    "</h6>" +
                                    "<p>" +
                                    AIZ.extra.bytesToSize(data[i].file_size) +
                                    "</p>" +
                                    "</div>" +
                                    '<div class="remove">' +
                                    '<button class="btn btn-sm btn-link remove-attachment" type="button">' +
                                    '<i class="la la-close"></i>' +
                                    "</button>" +
                                    "</div>" +
                                    "</div>";

                                elem.next(".file-preview").append(html);
                            }
                        } else {
                            elem.find(".file-amount").html(
                                AIZ.local.choose_file
                            );
                        }
                    }
                );
            } else {
                elem.find(".file-amount").html(AIZ.local.choose_file);
            }

            // if (AIZ.uploader.data.selectedFiles.length > 0) {
            //     elem.find(".file-amount").html(
            //         AIZ.uploader.updateFileHtml(AIZ.uploader.data.selectedFiles)
            //     );
            //     for (
            //         var i = 0;
            //         i < AIZ.uploader.data.selectedFiles.length;
            //         i++
            //     ) {
            //         var index = AIZ.uploader.data.allFiles.findIndex(
            //             (x) => x.id === AIZ.uploader.data.selectedFiles[i]
            //         );
            //         var thumb = "";
            //         if (AIZ.uploader.data.allFiles[index].type == "image") {
            //             thumb =
            //                 '<img src="' +
            //                 AIZ.data.appUrl +
            //                 "/public/" +
            //                 AIZ.uploader.data.allFiles[index].file_name +
            //                 '" class="img-fit">';
            //         } else {
            //             thumb = '<i class="la la-file-text"></i>';
            //         }
            //         var html =
            //             '<div class="d-flex justify-content-between align-items-center mt-2 file-preview-item" data-id="' +
            //             AIZ.uploader.data.allFiles[index].id +
            //             '" title="' +
            //             AIZ.uploader.data.allFiles[index].file_original_name +
            //             "." +
            //             AIZ.uploader.data.allFiles[index].extension +
            //             '">' +
            //             '<div class="align-items-center align-self-stretch d-flex justify-content-center thumb">' +
            //             thumb +
            //             "</div>" +
            //             '<div class="col body">' +
            //             '<h6 class="d-flex">' +
            //             '<span class="text-truncate title">' +
            //             AIZ.uploader.data.allFiles[index].file_original_name +
            //             "</span>" +
            //             '<span class="ext">.' +
            //             AIZ.uploader.data.allFiles[index].extension +
            //             "</span>" +
            //             "</h6>" +
            //             "<p>" +
            //             AIZ.extra.bytesToSize(
            //                 AIZ.uploader.data.allFiles[index].file_size
            //             ) +
            //             "</p>" +
            //             "</div>" +
            //             '<div class="remove">' +
            //             '<button class="btn btn-sm btn-link remove-attachment" type="button">' +
            //             '<i class="la la-close"></i>' +
            //             "</button>" +
            //             "</div>" +
            //             "</div>";

            //         elem.next(".file-preview").append(html);
            //     }
            // } else {
            //     elem.find(".file-amount").html("Choose File");
            // }
        },
        editorImageGenerate: function (elem) {
            if (AIZ.uploader.data.selectedFiles.length > 0) {
                for (
                    var i = 0;
                    i < AIZ.uploader.data.selectedFiles.length;
                    i++
                ) {
                    var index = AIZ.uploader.data.allFiles.findIndex(
                        (x) => x.id === AIZ.uploader.data.selectedFiles[i]
                    );
                    var thumb = "";
                    if (AIZ.uploader.data.allFiles[index].type === "image") {
                        thumb =
                            '<img src="' +
                            AIZ.data.fileBaseUrl +
                            AIZ.uploader.data.allFiles[index].file_name +
                            '">';
                        elem[0].insertHTML(thumb);
                        // console.log(elem);
                    }
                }
            }
        },
        dismissUploader: function () {
            $("#aizUploaderModal").on("hidden.bs.modal", function () {
                $(".aiz-uploader-backdrop").remove();
                $("#aizUploaderModal").remove();
            });
        },
        trigger: function (
            elem = null,
            from = "",
            type = "all",
            selectd = "",
            multiple = false,
            callback = null
        ) {
            // $("body").append('<div class="aiz-uploader-backdrop"></div>');

            var elem = $(elem);
            var multiple = multiple;
            var type = type;
            var oldSelectedFiles = selectd;
            if (oldSelectedFiles !== "") {
                AIZ.uploader.data.selectedFiles = oldSelectedFiles
                    .split(",")
                    .map(Number);
            } else {
                AIZ.uploader.data.selectedFiles = [];
            }
            if ("undefined" !== typeof type && type.length > 0) {
                AIZ.uploader.data.type = type;
            }

            if (multiple) {
                AIZ.uploader.data.multiple = true;
            } else {
                AIZ.uploader.data.multiple = false;
            }

            // setTimeout(function() {
            $.post(
                AIZ.data.appUrl + "/aiz-uploader",
                { _token: AIZ.data.csrf },
                function (data) {
                    $("body").append(data);
                    $("#aizUploaderModal").modal("show");
                    AIZ.plugins.aizUppy();
                    AIZ.uploader.getAllUploads(
                        AIZ.data.appUrl + "/aiz-uploader/get-uploaded-files",
                        null,
                        $('[name="aiz-uploader-sort"]').val()
                    );
                    AIZ.uploader.updateUploaderSelected();
                    AIZ.uploader.clearUploaderSelected();
                    AIZ.uploader.sortUploaderFiles();
                    AIZ.uploader.searchUploaderFiles();
                    AIZ.uploader.showSelectedFiles();
                    AIZ.uploader.dismissUploader();

                    $("#uploader_next_btn").on("click", function () {
                        if (AIZ.uploader.data.next_page_url != null) {
                            $('[name="aiz-show-selected"]').prop(
                                "checked",
                                false
                            );
                            AIZ.uploader.getAllUploads(
                                AIZ.uploader.data.next_page_url
                            );
                        }
                    });

                    $("#uploader_prev_btn").on("click", function () {
                        if (AIZ.uploader.data.prev_page_url != null) {
                            $('[name="aiz-show-selected"]').prop(
                                "checked",
                                false
                            );
                            AIZ.uploader.getAllUploads(
                                AIZ.uploader.data.prev_page_url
                            );
                        }
                    });

                    $(".aiz-uploader-search i").on("click", function () {
                        $(this).parent().toggleClass("open");
                    });

                    $('[data-toggle="aizUploaderAddSelected"]').on(
                        "click",
                        function () {
                            if (from === "input") {
                                AIZ.uploader.inputSelectPreviewGenerate(elem);
                            } else if (from === "direct") {
                                callback(AIZ.uploader.data.selectedFiles);
                            }
                            $("#aizUploaderModal").modal("hide");
                        }
                    );
                }
            );
            // }, 50);
        },
        initForInput: function () {
            $(document).on(
                "click",
                '[data-toggle="aizuploader"]',
                function (e) {
                    if (e.detail === 1) {
                        var elem = $(this);
                        var multiple = elem.data("multiple");
                        var type = elem.data("type");
                        var oldSelectedFiles = elem
                            .find(".selected-files")
                            .val();

                        multiple = !multiple ? "" : multiple;
                        type = !type ? "" : type;
                        oldSelectedFiles = !oldSelectedFiles
                            ? ""
                            : oldSelectedFiles;

                        AIZ.uploader.trigger(
                            this,
                            "input",
                            type,
                            oldSelectedFiles,
                            multiple
                        );
                    }
                }
            );
        },
        previewGenerate: function () {
            $('[data-toggle="aizuploader"]').each(function () {
                var $this = $(this);
                var files = $this.find(".selected-files").val();
                if (files != "") {
                    $.post(
                        AIZ.data.appUrl + "/aiz-uploader/get_file_by_ids",
                        { _token: AIZ.data.csrf, ids: files },
                        function (data) {
                            $this.next(".file-preview").html(null);

                            if (data.length > 0) {
                                $this
                                    .find(".file-amount")
                                    .html(AIZ.uploader.updateFileHtml(data));
                                for (var i = 0; i < data.length; i++) {
                                    var thumb = "";
                                    if (data[i].type === "image") {
                                        thumb =
                                            '<img src="' +
                                            data[i].file_name +
                                            '" class="img-fit">';
                                    } else if(data[i].type === 'video') {
                                        thumb = '<video width="320" height="240" controls><source src="'+ data[i].file_name +'" type="video/mp4">Your browser does not support the video tag.</video>'
                                    } else {
                                        thumb = '<i class="la la-file-text"></i>';
                                    }
                                    var html =
                                        '<div class="d-flex justify-content-between align-items-center mt-2 file-preview-item" data-id="' +
                                        data[i].id +
                                        '" title="' +
                                        data[i].file_original_name +
                                        "." +
                                        data[i].extension +
                                        '">' +
                                        '<div class="align-items-center align-self-stretch d-flex justify-content-center thumb">' +
                                        thumb +
                                        "</div>" +
                                        '<div class="col body">' +
                                        '<h6 class="d-flex">' +
                                        '<span class="text-truncate title">' +
                                        data[i].file_original_name +
                                        "</span>" +
                                        '<span class="ext flex-shrink-0">.' +
                                        data[i].extension +
                                        "</span>" +
                                        "</h6>" +
                                        "<p>" +
                                        AIZ.extra.bytesToSize(
                                            data[i].file_size
                                        ) +
                                        "</p>" +
                                        "</div>" +
                                        '<div class="remove">' +
                                        '<button class="btn btn-sm btn-link remove-attachment" type="button">' +
                                        '<i class="la la-close"></i>' +
                                        "</button>" +
                                        "</div>" +
                                        "</div>";

                                    $this.next(".file-preview").append(html);
                                }
                            } else {
                                $this
                                    .find(".file-amount")
                                    .html(AIZ.local.choose_file);
                            }
                        }
                    );
                }
            });
        },
    };
    AIZ.plugins = {
        metismenu: function () {
            $('[data-toggle="aiz-side-menu"]').metisMenu();
        },
        bootstrapSelect: function (refresh = "") {
            $(".aiz-selectpicker").each(function (el) {
                var $this = $(this);
                if (!$this.parent().hasClass("bootstrap-select")) {
                    var selected = $this.data("selected");
                    if (typeof selected !== "undefined") {
                        $this.val(selected);
                    }
                    $this.selectpicker({
                        size: 5,
                        noneSelectedText: AIZ.local.nothing_selected,
                        virtualScroll: false,
                    });
                }
                if (refresh === "refresh") {
                    $this.selectpicker("refresh");
                }
                if (refresh === "destroy") {
                    $this.selectpicker("destroy");
                }
            });
        },
        tagify: function () {
            $(".aiz-tag-input")
                .not(".tagify")
                .each(function () {
                    var $this = $(this);

                    var maxTags = $this.data("max-tags");
                    var whitelist = $this.data("whitelist");
                    var onchange = $this.data("on-change");

                    maxTags = !maxTags ? Infinity : maxTags;
                    whitelist = !whitelist ? [] : whitelist;

                    $this.tagify({
                        maxTags: maxTags,
                        whitelist: whitelist,
                        dropdown: {
                            enabled: 1,
                        },
                    });
                    try {
                        callback = eval(onchange);
                    } catch (e) {
                        var callback = "";
                    }
                    if (typeof callback == "function") {
                        $this.on("removeTag", function () {
                            callback();
                        });
                        $this.on("add", function () {
                            callback();
                        });
                    }
                });
        },
        textEditor: function () {
            $(".aiz-text-editor").each(function (el) {
                var $this = $(this);
                var buttons = $this.data("buttons");
                var minHeight = $this.data("min-height");
                var placeholder = $this.attr("placeholder");
                var format = $this.data("format");
                $.extend($.summernote.options, {
                    buttons: {
                        clearText: function (context) {
                            var ui = $.summernote.ui;
                            var button = ui.button({
                                contents: '<i class="fa fa-trash"></i> Clear',
                                tooltip: 'Clear all content',
                                click: function () {
                                    context.invoke('code', '');
                                }
                            });
                            return button.render();
                        }
                    }
                });

                buttons = !buttons
                    ? [
                        ["font", ["bold", "underline", "italic", "clear"]],
                        ["para", ["ul", "ol", "paragraph"]],
                        ["style", ["style"]],
                        ["color", ["color"]],
                        ["table", ["table"]],
                        ["insert", ["link", "picture", "video"]],
                        ["view", ["fullscreen", "undo", "redo"]],
                        ["custom", ["clearText"]],
                    ]
                    : buttons;
                placeholder = !placeholder ? "" : placeholder;
                minHeight = !minHeight ? 200 : minHeight;
                format = typeof format == "undefined" ? false : format;

                $this.summernote({
                    toolbar: buttons,
                    placeholder: placeholder,
                    disableDragAndDrop: true,
                    height: minHeight,
                    callbacks: {
                        onImageUpload: function (data) {
                            data.pop();
                        },
                        onPaste: function (e) {
                            if (format) {
                                var bufferText = (
                                    (e.originalEvent || e).clipboardData ||
                                    window.clipboardData
                                ).getData("Text");
                                e.preventDefault();
                                document.execCommand(
                                    "insertText",
                                    false,
                                    bufferText
                                );
                            }
                        },
                    },
                });

                var nativeHtmlBuilderFunc = $this.summernote(
                    "module",
                    "videoDialog"
                ).createVideoNode;

                $this.summernote("module", "videoDialog").createVideoNode =
                    function (url) {
                        var wrap = $(
                            '<div class="embed-responsive embed-responsive-16by9"></div>'
                        );
                        var html = nativeHtmlBuilderFunc(url);
                        html = $(html).addClass("embed-responsive-item");
                        return wrap.append(html)[0];
                    };
            });
        },
        dateRange: function () {
            $(".aiz-date-range").each(function () {
                var $this = $(this);
                var today = moment().startOf("day");
                var value = $this.val();
                var startDate = false;
                var minDate = false;
                var maxDate = false;
                var advncdRange = false;
                var ranges = {
                    Today: [moment(), moment()],
                    Yesterday: [
                        moment().subtract(1, "days"),
                        moment().subtract(1, "days"),
                    ],
                    "Last 7 Days": [moment().subtract(6, "days"), moment()],
                    "Last 30 Days": [moment().subtract(29, "days"), moment()],
                    "This Month": [
                        moment().startOf("month"),
                        moment().endOf("month"),
                    ],
                    "Last Month": [
                        moment().subtract(1, "month").startOf("month"),
                        moment().subtract(1, "month").endOf("month"),
                    ],
                };

                var single = $this.data("single");
                var monthYearDrop = $this.data("show-dropdown");
                var format = $this.data("format");
                var separator = $this.data("separator");
                var pastDisable = $this.data("past-disable");
                var futureDisable = $this.data("future-disable");
                var timePicker = $this.data("time-picker");
                var timePickerIncrement = $this.data("time-gap");
                var advncdRange = $this.data("advanced-range");

                single = !single ? false : single;
                monthYearDrop = !monthYearDrop ? false : monthYearDrop;
                format = !format ? "YYYY-MM-DD" : format;
                separator = !separator ? " / " : separator;
                minDate = !pastDisable ? minDate : today;
                maxDate = !futureDisable ? maxDate : today;
                timePicker = !timePicker ? false : timePicker;
                timePickerIncrement = !timePickerIncrement
                    ? 1
                    : timePickerIncrement;
                ranges = !advncdRange ? "" : ranges;

                $this.daterangepicker({
                    singleDatePicker: single,
                    showDropdowns: monthYearDrop,
                    minDate: minDate,
                    maxDate: maxDate,
                    timePickerIncrement: timePickerIncrement,
                    autoUpdateInput: false,
                    ranges: ranges,
                    locale: {
                        format: format,
                        separator: separator,
                        applyLabel: "Select",
                        cancelLabel: "Clear",
                    },
                });
                if (single) {
                    $this.on("apply.daterangepicker", function (ev, picker) {
                        $this.val(picker.startDate.format(format));
                    });
                } else {
                    $this.on("apply.daterangepicker", function (ev, picker) {
                        $this.val(
                            picker.startDate.format(format) +
                                separator +
                                picker.endDate.format(format)
                        );
                    });
                }

                $this.on("cancel.daterangepicker", function (ev, picker) {
                    $this.val("");
                });
            });
        },
        timePicker: function () {
            $(".aiz-time-picker").each(function () {
                var $this = $(this);

                var minuteStep = $this.data("minute-step");
                var defaultTime = $this.data("default");

                minuteStep = !minuteStep ? 10 : minuteStep;
                defaultTime = !defaultTime ? "00:00" : defaultTime;

                $this.timepicker({
                    template: "dropdown",
                    minuteStep: minuteStep,
                    defaultTime: defaultTime,
                    icons: {
                        up: "las la-angle-up",
                        down: "las la-angle-down",
                    },
                    showInputs: false,
                });
            });
        },
        colorPicker: function () {
            $(".aiz-color-picker").on("change", function () {
                var $this = $(this);
                let value = $this.val();
                $this.parent().parent().siblings(".aiz-color-input").val(value);
            });
            $(".aiz-color-input").on("change", function () {
                var $this = $(this);
                let value = $this.val();
                $(this)
                    .siblings(".input-group-append")
                    .children(".input-group-text")
                    .children(".aiz-color-picker")
                    .val(value);
            });
        },
        fooTable: function () {
            $(".aiz-table").each(function () {
                var $this = $(this);

                var empty = $this.data("empty");
                empty = !empty ? AIZ.local.nothing_found : empty;

                $this.footable({
                    breakpoints: {
                        xs: 576,
                        sm: 768,
                        md: 992,
                        lg: 1200,
                        xl: 1400,
                    },
                    cascade: true,
                    on: {
                        "ready.ft.table": function (e, ft) {
                            AIZ.extra.deleteConfirm();
                            AIZ.plugins.bootstrapSelect("refresh");
                        },
                    },
                    empty: empty,
                });
            });
        },
        sectionFooTable: function (section) {
            var $this = $(section+" .aiz-table");

            var empty = $this.data("empty");
            empty = !empty ? AIZ.local.nothing_found : empty;

            $this.footable({
                breakpoints: {
                    xs: 576,
                    sm: 768,
                    md: 992,
                    lg: 1200,
                    xl: 1400,
                },
                cascade: true,
                on: {
                    "ready.ft.table": function (e, ft) {
                        AIZ.extra.deleteConfirm();
                        AIZ.plugins.bootstrapSelect("refresh");
                    },
                },
                empty: empty,
            });
        },
        notify: function (type = "dark", message = "") {
            $.notify(
                {
                    // options
                    message: message,
                },
                {
                    // settings
                    showProgressbar: true,
                    delay: 2500,
                    mouse_over: "pause",
                    placement: {
                        from: "bottom",
                        align: "left",
                    },
                    animate: {
                        enter: "animated fadeInUp",
                        exit: "animated fadeOutDown",
                    },
                    type: type,
                    template:
                        '<div data-notify="container" class="aiz-notify alert alert-{0}" role="alert">' +
                        '<button type="button" aria-hidden="true" data-notify="dismiss" class="close"><i class="las la-times"></i></button>' +
                        '<span data-notify="message">{2}</span>' +
                        '<div class="progress" data-notify="progressbar">' +
                        '<div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%;"></div>' +
                        "</div>" +
                        "</div>",
                }
            );
        },
        aizUppy: function () {
            if ($("#aiz-upload-files").length > 0) {
                var uppy = Uppy.Core({
                    autoProceed: true,
                });
                uppy.use(Uppy.Dashboard, {
                    target: "#aiz-upload-files",
                    inline: true,
                    showLinkToFileUploadResult: false,
                    showProgressDetails: true,
                    hideCancelButton: true,
                    hidePauseResumeButton: true,
                    hideUploadButton: true,
                    proudlyDisplayPoweredByUppy: false,
                    locale: {
                        strings: {
                            addMoreFiles: AIZ.local.add_more_files,
                            addingMoreFiles: AIZ.local.adding_more_files,
                            dropPaste:
                                AIZ.local.drop_files_here_paste_or +
                                " %{browse}",
                            browse: AIZ.local.browse,
                            uploadComplete: AIZ.local.upload_complete,
                            uploadPaused: AIZ.local.upload_paused,
                            resumeUpload: AIZ.local.resume_upload,
                            pauseUpload: AIZ.local.pause_upload,
                            retryUpload: AIZ.local.retry_upload,
                            cancelUpload: AIZ.local.cancel_upload,
                            xFilesSelected: {
                                0: "%{smart_count} " + AIZ.local.file_selected,
                                1: "%{smart_count} " + AIZ.local.files_selected,
                            },
                            uploadingXFiles: {
                                0:
                                    AIZ.local.uploading +
                                    " %{smart_count} " +
                                    AIZ.local.file,
                                1:
                                    AIZ.local.uploading +
                                    " %{smart_count} " +
                                    AIZ.local.files,
                            },
                            processingXFiles: {
                                0:
                                    AIZ.local.processing +
                                    " %{smart_count} " +
                                    AIZ.local.file,
                                1:
                                    AIZ.local.processing +
                                    " %{smart_count} " +
                                    AIZ.local.files,
                            },
                            uploading: AIZ.local.uploading,
                            complete: AIZ.local.complete,
                        },
                    },
                });
                uppy.use(Uppy.XHRUpload, {
                    endpoint: AIZ.data.appUrl + "/aiz-uploader/upload",
                    fieldName: "aiz_file",
                    formData: true,
                    headers: {
                        "X-CSRF-TOKEN": AIZ.data.csrf,
                    },
                });
                uppy.on("upload-success", function () {
                    AIZ.uploader.getAllUploads(
                        AIZ.data.appUrl + "/aiz-uploader/get-uploaded-files"
                    );
                });
            }
        },
        tooltip: function () {
            $("body")
                .tooltip({ selector: '[data-toggle="tooltip"]' })
                .click(function () {
                    $('[data-toggle="tooltip"]').tooltip("hide");
                });
        },
        countDown: function () {
            if ($(".aiz-count-down").length > 0) {
                $(".aiz-count-down").each(function () {
                    var $this = $(this);
                    var date = $this.data("date");
                    // console.log(date)

                    $this
                        .countdown(date)
                        .on("update.countdown", function (event) {
                            var $this = $(this).html(
                                event.strftime(
                                    "" +
                                        '<div class="countdown-item"><span class="countdown-digit">%-D</span></div><span class="countdown-separator">:</span>' +
                                        '<div class="countdown-item"><span class="countdown-digit">%H</span></div><span class="countdown-separator">:</span>' +
                                        '<div class="countdown-item"><span class="countdown-digit">%M</span></div><span class="countdown-separator">:</span>' +
                                        '<div class="countdown-item"><span class="countdown-digit">%S</span></div>'
                                )
                            );
                        });
                });
            }
        },
        countDownBox: function () {
            if ($(".aiz-count-down-box").length > 0) {
                $(".aiz-count-down-box").each(function () {
                    var $this = $(this);
                    var date = $this.data("date");
                    // console.log(date)

                    $this
                        .countdown(date)
                        .on("update.countdown", function (event) {
                            var $this = $(this).html(
                                event.strftime(
                                    "" +
                                        '<div class="countdown-item"><span class="countdown-digit">%-D</span><span class="countdown-name">DAY</span></div><span class="countdown-separator">:</span>' +
                                        '<div class="countdown-item"><span class="countdown-digit">%H</span><span class="countdown-name">HRS</span></div><span class="countdown-separator">:</span>' +
                                        '<div class="countdown-item"><span class="countdown-digit">%M</span><span class="countdown-name">MIN</span></div><span class="countdown-separator">:</span>' +
                                        '<div class="countdown-item"><span class="countdown-digit">%S</span><span class="countdown-name">SEC</span></div>'
                                )
                            );
                        });
                });
            }
        },
        countDownCircle: function () {

            // default values
            let cx = 30, cy = 30, r = 30, stroke_length=190;

            let homepage = $("#selected_homepage").val();

            if (homepage && homepage == "thecore") {
                cx = cy = r = 23;
                stroke_length=138;
            }

            let html =
                '<div id="time">' +
                    '<div class="circle"><svg><circle cx="'+cx+'" cy="'+cy+'" r="'+r+'"></circle><circle cx="'+cx+'" cy="'+cy+'" r="'+r+'" id="dd"></circle></svg><div id="days" class="mb-2">00 <br><span>Day</span></div></div>' +
                    '<div class="circle"><svg><circle cx="'+cx+'" cy="'+cy+'" r="'+r+'"></circle><circle cx="'+cx+'" cy="'+cy+'" r="'+r+'" id="hh"></circle></svg><div id="hours" class="mb-2">00 <br><span>Hrs</span></div></div>' +
                    '<div class="circle"><svg><circle cx="'+cx+'" cy="'+cy+'" r="'+r+'"></circle><circle cx="'+cx+'" cy="'+cy+'" r="'+r+'" id="mm"></circle></svg><div id="minutes" class="mb-2">00 <br><span>Min</span></div></div>' +
                    '<div class="circle"><svg><circle cx="'+cx+'" cy="'+cy+'" r="'+r+'"></circle><circle cx="'+cx+'" cy="'+cy+'" r="'+r+'" id="ss"></circle></svg><div id="seconds" class="mb-2">00 <br><span>Sec</span></div></div>' +
                '</div>';

            if ($(".aiz-count-down-circle").length > 0) {
                $(".aiz-count-down-circle").each(function () {
                    var $this = $(this);
                    $this.html(html);

                    let days = $this.find("#days");
                    let hours = $this.find("#hours");
                    let minutes = $this.find("#minutes");
                    let seconds = $this.find("#seconds");

                    let dd = $this.find("#dd");
                    let hh = $this.find("#hh");
                    let mm = $this.find("#mm");
                    let ss = $this.find("#ss");

                    // Date Format mm/dd/yyyy
                    var endDate = $this.attr("end-date");
                    let now = new Date(endDate).getTime();
                    let x = setInterval(function () {
                        let CountDown = new Date().getTime();
                        let distance = now - CountDown;
                        if (distance > 0) {
                            // Time calculation for days, hours, minutes & seconds
                            let d = Math.floor(
                                distance / (1000 * 60 * 60 * 24)
                            );
                            let h = Math.floor(
                                (distance % (1000 * 60 * 60 * 24)) /
                                    (1000 * 60 * 60)
                            );
                            let m = Math.floor(
                                (distance % (1000 * 60 * 60)) / (1000 * 60)
                            );
                            let s = Math.floor((distance % (1000 * 60)) / 1000);

                            // Output the results in elements with id
                            days.html(d + "<br><span>Day</span>");
                            hours.html(h + "<br><span>Hrs</span>");
                            minutes.html(m + "<br><span>Min</span>");
                            seconds.html(s + "<br><span>Sec</span>");

                            // Animate stroke
                            dd.css("strokeDashoffset", stroke_length - (stroke_length * d) / 365); // 365 days in a year
                            hh.css("strokeDashoffset", stroke_length - (stroke_length * h) / 24); // 24 hours in a day
                            mm.css("strokeDashoffset", stroke_length - (stroke_length * m) / 60); // 60 minutes in an hour
                            ss.css("strokeDashoffset", stroke_length - (stroke_length * s) / 60); // 60 seconds in a minute
                        } else {
                            // If Countdown is over
                            clearInterval(x);
                        }
                    });
                });
            }
        },
       slickCarousel: function () {
            // Initialize category section slider on mobile
            $(document).ready(function () {
                function initCategorySlider() {
                    if (window.innerWidth <= 576) {
                        // Destroy existing slider if any
                        if ($('.mobile-category-slider').hasClass('slick-initialized')) {
                            $('.mobile-category-slider').slick('unslick');
                        }
                        // Initialize new slider
                        $('.mobile-category-slider').slick({
                            slidesToShow: 1.5,
                            slidesToScroll: 1,
                            arrows: false,
                            dots: false,
                            infinite: false,
                            variableWidth: false,
                            centerMode: false,
                            adaptiveHeight: false,
                            autoplay: true,
                            utoplaySpeed: 3000 // 3 seconds
                        });
                        // Equalize heights after initialization
                        setTimeout(equalizeSlideHeights, 100);
                    } else {
                        // Destroy slider on larger screens
                        if ($('.mobile-category-slider').hasClass('slick-initialized')) {
                            $('.mobile-category-slider').slick('unslick');
                        }
                    }
                }
                function equalizeSlideHeights() {
                    let maxHeight = 0;
                    $('.category-slide').each(function () {
                        $(this).css('height', 'auto');
                        const slideHeight = $(this).outerHeight();
                        if (slideHeight > maxHeight) {
                            maxHeight = slideHeight;
                        }
                    });
                    $('.category-slide').css('height', maxHeight + 'px');
                    $('.mobile-category-slider').slick('setPosition');
                }
                // Initialize on load
                initCategorySlider();

                // Reinitialize on resize
                $(window).on('resize', function () {
                    initCategorySlider();
                });
            });









            $(".aiz-carousel")
                .not(".slick-initialized")
                   .each(function () {
                    var $this = $(this);

                    var slidesPerViewXs = $this.data("xs-items");
                    var slidesPerViewSm = $this.data("sm-items");
                    var slidesPerViewMd = $this.data("md-items");
                    var slidesPerViewLg = $this.data("lg-items");
                    var slidesPerViewXl = $this.data("xl-items");
                    var slidesPerViewXXl = $this.data("xxl-items");
                    var slidesPerViewFullHd = $this.data("full-hd-items");
                    var slidesPerView = $this.data("items");

                    var slidesCenterMode = $this.data("center");
                    var slidesArrows = $this.data("arrows");
                    var slidesDots = $this.data("dots");
                    var slidesRows = $this.data("rows");
                    var slidesAutoplay = $this.data("autoplay");
                    var slidesAutoplaySpeed = $this.data("autoplay-speed");
                    var slidesFade = $this.data("fade");
                    var asNavFor = $this.data("nav-for");
                    var infinite = $this.data("infinite");
                    var focusOnSelect = $this.data("focus-select");
                    var adaptiveHeight = $this.data("auto-height");
                    var variableWidth = $this.data("variableWidth");

                    var vertical = $this.data("vertical");
                    var verticalXs = $this.data("vertical-xs");
                    var verticalSm = $this.data("vertical-sm");
                    var verticalMd = $this.data("vertical-md");
                    var verticalLg = $this.data("vertical-lg");
                    var verticalXl = $this.data("vertical-xl");
                    var verticalXXl = $this.data("vertical-xxl");
                    var verticalFullHd = $this.data("vertical-full-hd");

                    slidesPerView = !slidesPerView ? 1 : slidesPerView;
                    slidesPerViewFullHd = !slidesPerViewFullHd 
                        ? slidesPerView 
                        : slidesPerViewFullHd;
                    slidesPerViewXXl = !slidesPerViewXXl 
                        ? slidesPerViewFullHd 
                        : slidesPerViewXXl;
                    slidesPerViewXl = !slidesPerViewXl
                        ? slidesPerView
                        : slidesPerViewXl;
                    slidesPerViewLg = !slidesPerViewLg
                        ? slidesPerViewXl
                        : slidesPerViewLg;
                    slidesPerViewMd = !slidesPerViewMd
                        ? slidesPerViewLg
                        : slidesPerViewMd;
                    slidesPerViewSm = !slidesPerViewSm
                        ? slidesPerViewMd
                        : slidesPerViewSm;
                    slidesPerViewXs = !slidesPerViewXs
                        ? slidesPerViewSm
                        : slidesPerViewXs;

                    vertical = !vertical ? false : vertical;

                    verticalFullHd = 
                    typeof verticalFullHd == "undefined" 
                        ? vertical 
                        : verticalFullHd;
                        
                    verticalXXl = 
                    typeof verticalXXl == "undefined" 
                        ? verticalFullHd 
                        : verticalXXl;

                    verticalXl =
                        typeof verticalXl == "undefined"
                            ? vertical
                            : verticalXl;
                    verticalLg =
                        typeof verticalLg == "undefined"
                            ? verticalXl
                            : verticalLg;
                    verticalMd =
                        typeof verticalMd == "undefined"
                            ? verticalLg
                            : verticalMd;
                    verticalSm =
                        typeof verticalSm == "undefined"
                            ? verticalMd
                            : verticalSm;
                    verticalXs =
                        typeof verticalXs == "undefined"
                            ? verticalSm
                            : verticalXs;

                    slidesCenterMode = !slidesCenterMode
                        ? false
                        : slidesCenterMode;
                    slidesArrows = !slidesArrows ? false : slidesArrows;
                    slidesDots = !slidesDots ? false : slidesDots;
                    slidesRows = !slidesRows ? 1 : slidesRows;
                    slidesAutoplay = !slidesAutoplay ? false : slidesAutoplay;
                    slidesAutoplaySpeed = !slidesAutoplaySpeed
                        ? "5000"
                        : slidesAutoplaySpeed;
                    slidesFade = !slidesFade ? false : slidesFade;
                    asNavFor = !asNavFor ? null : asNavFor;
                    infinite = !infinite ? false : infinite;
                    focusOnSelect = !focusOnSelect ? false : focusOnSelect;
                    adaptiveHeight = !adaptiveHeight ? false : adaptiveHeight;
                    variableWidth = !variableWidth ? false : variableWidth;

                    var slidesRtl =
                        $("html").attr("dir") === "rtl" && !vertical
                            ? true
                            : false;

                    var slidesRtlFullHd = 
                        $("html").attr("dir") === "rtl" && !verticalFullHd 
                            ? true 
                            : false;

                    var slidesRtlXXl = 
                        $("html").attr("dir") === "rtl" && !verticalXXl 
                            ? true 
                            : false;

                    var slidesRtlXL =
                        $("html").attr("dir") === "rtl" && !verticalXl
                            ? true
                            : false;
                    var slidesRtlLg =
                        $("html").attr("dir") === "rtl" && !verticalLg
                            ? true
                            : false;
                    var slidesRtlMd =
                        $("html").attr("dir") === "rtl" && !verticalMd
                            ? true
                            : false;
                    var slidesRtlSm =
                        $("html").attr("dir") === "rtl" && !verticalSm
                            ? true
                            : false;
                    var slidesRtlXs =
                        $("html").attr("dir") === "rtl" && !verticalXs
                            ? true
                            : false;

                    $this.slick({
                        slidesToShow: slidesPerView,
                        autoplay: slidesAutoplay,
                        autoplaySpeed: slidesAutoplaySpeed,
                        dots: slidesDots,
                        arrows: slidesArrows,
                        infinite: infinite,
                        vertical: vertical,
                        rtl: slidesRtl,
                        rows: slidesRows,
                        centerPadding: "0px",
                        centerMode: slidesCenterMode,
                        fade: slidesFade,
                        asNavFor: asNavFor,
                        focusOnSelect: focusOnSelect,
                        adaptiveHeight: adaptiveHeight,
                        slidesToScroll: 1,
                        variableWidth: variableWidth,
                        prevArrow:
                            '<button type="button" class="slick-prev"><i class="las la-angle-left"></i></button>',
                        nextArrow:
                            '<button type="button" class="slick-next"><i class="las la-angle-right"></i></button>',
                        responsive: [
                            {
                                breakpoint: 2601, 
                                settings: {
                                    slidesToShow: slidesPerViewFullHd, 
                                    vertical: verticalFullHd,
                                    rtl: slidesRtlFullHd
                                },
                            },
                            {
                                breakpoint: 1951, 
                                settings: {
                                    slidesToShow: slidesPerViewXXl,
                                    vertical: verticalXXl,
                                    rtl: slidesRtlXXl
                                },
                            },
                            {
                                breakpoint: 1500,
                                settings: {
                                    slidesToShow: slidesPerViewXl,
                                    vertical: verticalXl,
                                    rtl: slidesRtlXL,
                                },
                            },
                            {
                                breakpoint: 1200,
                                settings: {
                                    slidesToShow: slidesPerViewLg,
                                    vertical: verticalLg,
                                    rtl: slidesRtlLg,
                                },
                            },
                            {
                                breakpoint: 992,
                                settings: {
                                    slidesToShow: slidesPerViewMd,
                                    vertical: verticalMd,
                                    rtl: slidesRtlMd,
                                },
                            },
                            {
                                breakpoint: 768,
                                settings: {
                                    slidesToShow: slidesPerViewSm,
                                    vertical: verticalSm,
                                    rtl: slidesRtlSm,
                                },
                            },
                            {
                                breakpoint: 576,
                                settings: {
                                    slidesToShow: slidesPerViewXs,
                                    vertical: verticalXs,
                                    rtl: slidesRtlXs,
                                },
                            },
                        ],
                    });
                });
        },
        chart: function (selector, config) {
            if (!$(selector).length) return;

            $(selector).each(function () {
                var $this = $(this);

                var aizChart = new Chart($this, config);
            });
        },
        noUiSlider: function () {
            if ($(".aiz-range-slider")[0]) {
                $(".aiz-range-slider").each(function () {
                    var c = document.getElementById("input-slider-range"),
                        d = document.getElementById("input-slider-range-value-low"),
                        e = document.getElementById("input-slider-range-value-high"),
                        f = [d, e];

                    noUiSlider.create(c, {
                        start: [
                            parseInt(d.getAttribute("data-range-value-low")),
                            parseInt(e.getAttribute("data-range-value-high")),
                        ],
                        connect: true,
                        range: {
                            min: parseInt(c.getAttribute("data-range-value-min")),
                            max: parseInt(c.getAttribute("data-range-value-max")),
                        },
                    });

                
                    const handles = c.querySelectorAll(".noUi-handle");

                    handles.forEach((handle, index) => {
                        const touchArea = handle.querySelector(".noUi-touch-area");

                        
                        const valueText = document.createElement("span");
                        valueText.classList.add("slider-value-text");
                        valueText.textContent = f[index].textContent || 0;
                        touchArea.appendChild(valueText);

                        valueText.style.position = "absolute";
                        valueText.style.top = "-30px"; 
                        valueText.style.left = "50%";
                        valueText.style.transform = "translateX(-50%)";
                        valueText.style.fontSize = "14px";
                        valueText.style.color = "#fff"; 
                        valueText.style.background = "rgba(0, 0, 0, 0.4)"; 
                        valueText.style.padding = "4px 8px"; 
                        valueText.style.borderRadius = "4px"; 
                        valueText.style.whiteSpace = "nowrap";
                        valueText.style.pointerEvents = "none"; 
                        valueText.style.transition = "opacity 0.3s ease";


                    });

                
                    c.noUiSlider.on("update", function (a, b) {
                        f[b].textContent = a[b]; // existing element
                        const handle = c.querySelectorAll(".noUi-handle")[b];
                        const label = handle.querySelector(".slider-value-text");
                        let value = Math.round(a[b]);
                        if (label) label.textContent = value.toLocaleString();
                        d.textContent = "0";
                        e.textContent = parseInt(c.getAttribute("data-range-value-max")).toLocaleString();
                        // label.style.display = "none";
                    });

                
                    c.noUiSlider.on("change", function (a, b) {
                        rangefilter(a);
                    });
                });
            }
        },

        zoom: function () {
            if ($(".img-zoom")[0]) {
                $(".img-zoom").zoom({
                    magnify: 1.5,
                });
                if (
                    "ontouchstart" in window ||
                    navigator.maxTouchPoints > 0 ||
                    navigator.msMaxTouchPoints > 0
                ) {
                    $(".img-zoom").trigger("zoom.destroy");
                }
            }
        },
        jsSocials: function () {
            if ($(".aiz-share")[0]) {
                $(".aiz-share").jsSocials({
                    showLabel: false,
                    showCount: false,
                    shares: [
                        {
                            share: "email",
                            logo: "lar la-envelope",
                        },
                        {
                            share: "twitter",
                            logo: "aiz-custom-x-logo",
                        },
                        {
                            share: "facebook",
                            logo: "lab la-facebook-f",
                        },
                        {
                            share: "linkedin",
                            logo: "lab la-linkedin-in",
                        },
                        {
                            share: "whatsapp",
                            logo: "lab la-whatsapp",
                        },
                    ],
                });
            }
        },
        particles: function () {
            particlesJS(
                "particles-js",

                {
                    particles: {
                        number: {
                            value: 80,
                            density: {
                                enable: true,
                                value_area: 800,
                            },
                        },
                        color: {
                            value: "#dfdfe6",
                        },
                        shape: {
                            type: "circle",
                            stroke: {
                                width: 0,
                                color: "#000000",
                            },
                            polygon: {
                                nb_sides: 5,
                            },
                            image: {
                                src: "img/github.svg",
                                width: 100,
                                height: 100,
                            },
                        },
                        opacity: {
                            value: 0.5,
                            random: false,
                            anim: {
                                enable: false,
                                speed: 1,
                                opacity_min: 0.1,
                                sync: false,
                            },
                        },
                        size: {
                            value: 5,
                            random: true,
                            anim: {
                                enable: false,
                                speed: 40,
                                size_min: 0.1,
                                sync: false,
                            },
                        },
                        line_linked: {
                            enable: true,
                            distance: 150,
                            color: "#dfdfe6",
                            opacity: 0.4,
                            width: 1,
                        },
                        move: {
                            enable: true,
                            speed: 6,
                            direction: "none",
                            random: false,
                            straight: false,
                            out_mode: "out",
                            attract: {
                                enable: false,
                                rotateX: 600,
                                rotateY: 1200,
                            },
                        },
                    },
                    interactivity: {
                        detect_on: "canvas",
                        events: {
                            onhover: {
                                enable: true,
                                mode: "repulse",
                            },
                            onclick: {
                                enable: true,
                                mode: "push",
                            },
                            resize: true,
                        },
                        modes: {
                            grab: {
                                distance: 400,
                                line_linked: {
                                    opacity: 1,
                                },
                            },
                            bubble: {
                                distance: 400,
                                size: 40,
                                duration: 2,
                                opacity: 8,
                                speed: 3,
                            },
                            repulse: {
                                distance: 200,
                            },
                            push: {
                                particles_nb: 4,
                            },
                            remove: {
                                particles_nb: 2,
                            },
                        },
                    },
                    retina_detect: true,
                    config_demo: {
                        hide_card: false,
                        background_color: "#b61924",
                        background_image: "",
                        background_position: "50% 50%",
                        background_repeat: "no-repeat",
                        background_size: "cover",
                    },
                }
            );
        },
    };
    AIZ.extra = {
        refreshToken: function () {
            $.get(AIZ.data.appUrl + "/refresh-csrf").done(function (data) {
                AIZ.data.csrf = data;
            });
            // console.log(AIZ.data.csrf);
        },
        mobileNavToggle: function () {
            if (window.matchMedia("(max-width: 1200px)").matches) {
                $("body").addClass("side-menu-closed");
            }
            $('[data-toggle="aiz-mobile-nav"]').on("click", function () {
                if ($("body").hasClass("side-menu-open")) {
                    $("body")
                        .addClass("side-menu-closed")
                        .removeClass("side-menu-open");
                } else if ($("body").hasClass("side-menu-closed")) {
                    $("body")
                        .removeClass("side-menu-closed")
                        .addClass("side-menu-open");
                } else {
                    $("body")
                        .removeClass("side-menu-open")
                        .addClass("side-menu-closed");
                }
            });
            $(".aiz-sidebar-overlay").on("click", function () {
                $("body")
                    .removeClass("side-menu-open")
                    .addClass("side-menu-closed");
            });
        },
        initActiveMenu: function () {
            $('[data-toggle="aiz-side-menu"] a').each(function () {
                var pageUrl = window.location.href.split(/[?#]/)[0];
                if (this.href == pageUrl || $(this).hasClass("active")) {
                    $(this).addClass("active");
                    $(this).closest(".aiz-side-nav-item").addClass("mm-active");
                    $(this)
                        .closest(".level-2")
                        .siblings("a")
                        .addClass("level-2-active");
                    $(this)
                        .closest(".level-3")
                        .siblings("a")
                        .addClass("level-3-active");
                }
            });
        },
        deleteConfirm: function () {
            $(".confirm-delete").click(function (e) {
                e.preventDefault();
                var url = $(this).data("href");
                $("#delete-modal").modal("show");
                $("#delete-link").attr("href", url);
            });

            $(".confirm-cancel").click(function (e) {
                e.preventDefault();
                var url = $(this).data("href");
                $("#cancel-modal").modal("show");
                $("#cancel-link").attr("href", url);
            });

            $(".confirm-complete").click(function (e) {
                e.preventDefault();
                var url = $(this).data("href");
                $("#complete-modal").modal("show");
                $("#comfirm-link").attr("href", url);
            });

            $(".confirm-alert").click(function (e) {
                e.preventDefault();
                var url = $(this).data("href");
                var target = $(this).data("target");
                $(target).modal("show");
                $(target).find(".comfirm-link").attr("href", url);
                $("#comfirm-link").attr("href", url);
            });
        },
        bytesToSize: function (bytes) {
            var sizes = ["Bytes", "KB", "MB", "GB", "TB"];
            if (bytes == 0) return "0 Byte";
            var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
            return Math.round(bytes / Math.pow(1024, i), 2) + " " + sizes[i];
        },
        multiModal: function () {
            $(document).on("show.bs.modal", ".modal", function (event) {
                var zIndex = 1040 + 10 * $(".modal:visible").length;
                $(this).css("z-index", zIndex);
                setTimeout(function () {
                    $(".modal-backdrop")
                        .not(".modal-stack")
                        .css("z-index", zIndex - 1)
                        .addClass("modal-stack");
                }, 0);
            });
            $(document).on("hidden.bs.modal", function () {
                if ($(".modal.show").length > 0) {
                    $("body").addClass("modal-open");
                }
            });
        },
        bsCustomFile: function () {
            $(".custom-file input").change(function (e) {
                var files = [];
                for (var i = 0; i < $(this)[0].files.length; i++) {
                    files.push($(this)[0].files[i].name);
                }
                if (files.length === 1) {
                    $(this).next(".custom-file-name").html(files[0]);
                } else if (files.length > 1) {
                    $(this)
                        .next(".custom-file-name")
                        .html(files.length + " " + AIZ.local.files_selected);
                } else {
                    $(this)
                        .next(".custom-file-name")
                        .html(AIZ.local.choose_file);
                }
            });
        },
        stopPropagation: function () {
            $(document).on("click", ".stop-propagation", function (e) {
                e.stopPropagation();
            });
        },
        outsideClickHide: function () {
            $(document).on("click", function (e) {
                $(".document-click-d-none").addClass("d-none");
            });
        },
        inputRating: function () {
            $(".rating-input").each(function () {
                $(this)
                    .find("label")
                    .on({
                        mouseover: function (event) {
                            $(this).find("i").addClass("hover");
                            $(this).prevAll().find("i").addClass("hover");
                        },
                        mouseleave: function (event) {
                            $(this).find("i").removeClass("hover");
                            $(this).prevAll().find("i").removeClass("hover");
                        },
                        click: function (event) {
                            $(this).siblings().find("i").removeClass("active");
                            $(this).find("i").addClass("active");
                            $(this).prevAll().find("i").addClass("active");
                        },
                    });
                if ($(this).find("input").is(":checked")) {
                    $(this)
                        .find("label")
                        .siblings()
                        .find("i")
                        .removeClass("active");
                    $(this)
                        .find("input:checked")
                        .closest("label")
                        .find("i")
                        .addClass("active");
                    $(this)
                        .find("input:checked")
                        .closest("label")
                        .prevAll()
                        .find("i")
                        .addClass("active");
                }
            });
        },
        scrollToBottom: function () {
            $(".scroll-to-btm").each(function (i, el) {
                el.scrollTop = el.scrollHeight;
            });
        },
        classToggle: function () {
            $(document).on(
                "click",
                '[data-toggle="class-toggle"]',
                function () {
                    var $this = $(this);
                    var target = $this.data("target");
                    var sameTriggers = $this.data("same");
                    var backdrop = $(this).data("backdrop");

                    if ($(target).hasClass("active")) {
                        $(target).removeClass("active");
                        $(sameTriggers).removeClass("active");
                        $this.removeClass("active");
                        $("body").removeClass("overflow-hidden");
                    } else {
                        $(target).addClass("active");
                        $this.addClass("active");
                        if (backdrop == "static") {
                            $("body").addClass("overflow-hidden");
                        }
                    }
                }
            );
        },
        collapseSidebar: function () {
            $(document).on(
                "click",
                '[data-toggle="collapse-sidebar"]',
                function (i, el) {
                    var $this = $(this);
                    var target = $(this).data("target");
                    var sameTriggers = $(this).data("siblings");

                    // var showOverlay = $this.data('overlay');
                    // var overlayMarkup = '<div class="overlay overlay-fixed dark c-pointer" data-toggle="collapse-sidebar" data-target="'+target+'"></div>';

                    // showOverlay = !showOverlay ? true : showOverlay;

                    // if (showOverlay && $(target).siblings('.overlay').length !== 1) {
                    //     $(target).after(overlayMarkup);
                    // }

                    e.preventDefault();
                    if ($(target).hasClass("opened")) {
                        $(target).removeClass("opened");
                        $(sameTriggers).removeClass("opened");
                        $($this).removeClass("opened");
                    } else {
                        $(target).addClass("opened");
                        $($this).addClass("opened");
                    }
                }
            );
        },
        autoScroll: function () {
            if ($(".aiz-auto-scroll").length > 0) {
                $(".aiz-auto-scroll").each(function () {
                    var options = $(this).data("options");

                    options = !options
                        ? '{"delay" : 2000 ,"amount" : 70 }'
                        : options;

                    options = JSON.parse(options);

                    this.delay = parseInt(options["delay"]) || 2000;
                    this.amount = parseInt(options["amount"]) || 70;
                    this.autoScroll = $(this);
                    this.iScrollHeight = this.autoScroll.prop("scrollHeight");
                    this.iScrollTop = this.autoScroll.prop("scrollTop");
                    this.iHeight = this.autoScroll.height();

                    var self = this;
                    this.timerId = setInterval(function () {
                        if (
                            self.iScrollTop + self.iHeight <
                            self.iScrollHeight
                        ) {
                            self.iScrollTop = self.autoScroll.prop("scrollTop");
                            self.iScrollTop += self.amount;
                            self.autoScroll.animate(
                                { scrollTop: self.iScrollTop },
                                "slow",
                                "linear"
                            );
                        } else {
                            self.iScrollTop -= self.iScrollTop;
                            self.autoScroll.animate(
                                { scrollTop: "0px" },
                                "fast",
                                "swing"
                            );
                        }
                    }, self.delay);
                });
            }
        },
        addMore: function () {
            $('[data-toggle="add-more"]').each(function () {
                var $this = $(this);
                var content = $this.data("content");
                var target = $this.data("target");

                $this.on("click", function (e) {
                    e.preventDefault();
                    $(target).append(content);
                    AIZ.plugins.bootstrapSelect();
                });
            });
        },
        removeParent: function () {
            $(document).on(
                "click",
                '[data-toggle="remove-parent"]',
                function () {
                    var $this = $(this);
                    var parent = $this.data("parent");
                    $this.closest(parent).remove();
                }
            );
        },
        selectHideShow: function () {
            $('[data-show="selectShow"]').each(function () {
                var target = $(this).data("target");
                $(this).on("change", function () {
                    var value = $(this).val();
                    // console.log(value);
                    $(target)
                        .children()
                        .not("." + value)
                        .addClass("d-none");
                    $(target)
                        .find("." + value)
                        .removeClass("d-none");
                });
            });
        },
        plusMinus: function () {
            $(".aiz-plus-minus input").each(function () {
                var $this = $(this);
                var min = parseInt($(this).attr("min"));
                var max = parseInt($(this).attr("max"));
                var value = parseInt($(this).val());
                if (value <= min) {
                    $this
                        .siblings('[data-type="minus"]')
                        .attr("disabled", true);
                } else if (
                    $this.siblings('[data-type="minus"]').attr("disabled")
                ) {
                    $this
                        .siblings('[data-type="minus"]')
                        .removeAttr("disabled");
                }
                if (value >= max) {
                    $this.siblings('[data-type="plus"]').attr("disabled", true);
                } else if (
                    $this.siblings('[data-type="plus"]').attr("disabled")
                ) {
                    $this.siblings('[data-type="plus"]').removeAttr("disabled");
                }
            });
            $(".aiz-plus-minus button")
                .off("click")
                .on("click", function (e) {
                    e.preventDefault();

                    var fieldName = $(this).attr("data-field");
                    var type = $(this).attr("data-type");
                    var input = $("input[name='" + fieldName + "']");
                    var currentVal = parseInt(input.val());

                    if (!isNaN(currentVal)) {
                        if (type == "minus") {
                            if (currentVal > input.attr("min")) {
                                input.val(currentVal - 1).change();
                            }
                            if (parseInt(input.val()) == input.attr("min")) {
                                $(this).attr("disabled", true);
                            }
                        } else if (type == "plus") {
                            if (currentVal < input.attr("max")) {
                                input.val(currentVal + 1).change();
                            }
                            if (parseInt(input.val()) == input.attr("max")) {
                                $(this).attr("disabled", true);
                            }
                        }
                    } else {
                        input.val(0);
                    }
                });
            $(".aiz-plus-minus input")
                .off("change")
                .on("change", function () {
                    var minValue = parseInt($(this).attr("min"));
                    var maxValue = parseInt($(this).attr("max"));
                    var valueCurrent = parseInt($(this).val());

                    name = $(this).attr("name");
                    if (valueCurrent >= minValue) {
                        $(this)
                            .siblings("[data-type='minus']")
                            .removeAttr("disabled");
                    } else {
                        alert(
                            translate(
                                "Sorry, the minimum limit has been reached"
                            )
                        );
                        $(this).val(minValue);
                    }
                    if (valueCurrent <= maxValue) {
                        $(this)
                            .siblings("[data-type='plus']")
                            .removeAttr("disabled");
                    } else {
                        alert(
                            translate(
                                "Sorry, the maximum limit has been reached"
                            )
                        );
                        $(this).val(maxValue);
                    }

                    if (typeof getVariantPrice === "function") {
                        getVariantPrice();
                    }
                });
        },
        hovCategoryMenu: function () {
            $("#category-menu-icon, #category-sidebar")
                .on("mouseover", function (event) {
                    $("#hover-category-menu")
                        .addClass("active")
                        .removeClass("d-none");
                })
                .on("mouseout", function (event) {
                    $("#hover-category-menu")
                        .addClass("d-none")
                        .removeClass("active");
                });
        },
        clickCategoryMenu: function () {
            var menu = $("#click-category-menu");
            menu.hide();
            menu.removeClass("d-none");
            $("#category-menu-bar").on("click", function (event) {
                if (event.target.closest('.categoriesAll')) {
                    event.stopPropagation();
                }else{
                    menu.slideToggle(500);
                    if ($("#category-menu-bar-icon").hasClass("show")) {
                        $("#category-menu-bar-icon").removeClass("show");
                    } else {
                        $("#category-menu-bar-icon").addClass("show");
                    }
                }

            });
        },
        hovUserTopMenu: function () {
            $("#nav-user-info")
                .on("mouseover", function (event) {
                    $(".hover-user-top-menu").addClass("active");
                })
                .on("mouseout", function (event) {
                    $(".hover-user-top-menu").removeClass("active");
                });
        },
        trimAppUrl: function () {
            if (AIZ.data.appUrl.slice(-1) == "/") {
                AIZ.data.appUrl = AIZ.data.appUrl.slice(
                    0,
                    AIZ.data.appUrl.length - 1
                );
                // console.log(AIZ.data.appUrl);
            }
        },
        setCookie: function (cname, cvalue, exdays) {
            var d = new Date();
            d.setTime(d.getTime() + exdays * 24 * 60 * 60 * 1000);
            var expires = "expires=" + d.toUTCString();
            document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
        },
        getCookie: function (cname) {
            var name = cname + "=";
            var decodedCookie = decodeURIComponent(document.cookie);
            var ca = decodedCookie.split(";");
            for (var i = 0; i < ca.length; i++) {
                var c = ca[i];
                while (c.charAt(0) === " ") {
                    c = c.substring(1);
                }
                if (c.indexOf(name) === 0) {
                    return c.substring(name.length, c.length);
                }
            }
            return "";
        },
        
        acceptCookie: function () {
            if (!AIZ.extra.getCookie("acceptCookies")) {
                $(".aiz-cookie-alert:not(.club-point-alert)").addClass("show");
            }
            if (!AIZ.extra.getCookie("acceptClubPointAlert")) {
                $(".club-point-alert").addClass("show");
            }

            $(".aiz-cookie-accept").on("click", function () {
                AIZ.extra.setCookie("acceptCookies", true, 60);
                $(".aiz-cookie-alert:not(.club-point-alert)").fadeOut(600, function() {
                    $(this).removeClass("show");
                });
            });

            $(".aiz-cookie-accept-club-point").on("click", function () {
                AIZ.extra.setCookie("acceptClubPointAlert", true, 60);
                $(".aiz-cookie-alert").fadeOut(600, function() {
                    $(this).removeClass("show");
                });
          });
        },
        setSession: function () {
            $(".set-session").each(function () {
                var $this = $(this);
                var key = $this.data("key");
                var value = $this.data("value");

                const now = new Date();
                const item = {
                    value: value,
                    expiry: now.getTime() + 3600000,
                };

                $this.on("click", function () {
                    localStorage.setItem(key, JSON.stringify(item));
                });
            });
        },
        showSessionPopup: function () {
            $(".removable-session").each(function () {
                var $this = $(this);
                var key = $this.data("key");
                var value = $this.data("value");
                var item = {};
                if (localStorage.getItem(key)) {
                    item = localStorage.getItem(key);
                    item = JSON.parse(item);
                }
                const now = new Date();
                if (
                    typeof item.expiry == "undefined" ||
                    now.getTime() > item.expiry
                ) {
                    $this.removeClass("d-none");
                }
            });
        },
    };

    setInterval(function () {
        AIZ.extra.refreshToken();
    }, 3600000);

    // init aiz plugins, extra options
    AIZ.extra.initActiveMenu();
    AIZ.extra.mobileNavToggle();
    AIZ.extra.deleteConfirm();
    AIZ.extra.multiModal();
    AIZ.extra.inputRating();
    AIZ.extra.bsCustomFile();
    AIZ.extra.stopPropagation();
    AIZ.extra.outsideClickHide();
    AIZ.extra.scrollToBottom();
    AIZ.extra.classToggle();
    AIZ.extra.collapseSidebar();
    AIZ.extra.autoScroll();
    AIZ.extra.addMore();
    AIZ.extra.removeParent();
    AIZ.extra.selectHideShow();
    AIZ.extra.plusMinus();
    AIZ.extra.hovCategoryMenu();
    AIZ.extra.clickCategoryMenu();
    AIZ.extra.hovUserTopMenu();
    AIZ.extra.trimAppUrl();
    AIZ.extra.acceptCookie();
    AIZ.extra.setSession();
    AIZ.extra.showSessionPopup();

    AIZ.plugins.metismenu();
    AIZ.plugins.bootstrapSelect();
    //AIZ.plugins.tagify();
    AIZ.plugins.textEditor();
    AIZ.plugins.tooltip();
    AIZ.plugins.countDown();
    AIZ.plugins.countDownBox();
    AIZ.plugins.countDownCircle();
    AIZ.plugins.dateRange();
    AIZ.plugins.timePicker();
    AIZ.plugins.colorPicker();
    AIZ.plugins.fooTable();
    AIZ.plugins.slickCarousel();
    AIZ.plugins.noUiSlider();
    AIZ.plugins.zoom();
    AIZ.plugins.jsSocials();

    // initialization of aiz uploader
    AIZ.uploader.initForInput();
    AIZ.uploader.removeAttachment();
    AIZ.uploader.previewGenerate();

    // $(document).ajaxComplete(function(){
    //     AIZ.plugins.bootstrapSelect('refresh');
    // });
})(jQuery);


 // Footer description expand
    const toggleButton = document.getElementById("toggle-btn");
    const textParagraph = document.querySelector(".footer-text-control");

    if (toggleButton && textParagraph) {
    toggleButton.addEventListener("click", () => {
        const isExpanded =
        textParagraph.style.maxHeight &&
        textParagraph.style.maxHeight !== "80px";

        // Toggle between expanded and collapsed state
        textParagraph.style.maxHeight = isExpanded
        ? "80px"
        : `${textParagraph.scrollHeight}px`;

        toggleButton.textContent = isExpanded ? "Read More" : "Show Less";
    });
    }



document.querySelectorAll('.video-container').forEach(container => {
    const video = container.querySelector('.upload_video');
    const playBtn = container.querySelector('.playButton');
    const playPauseBtn = container.querySelector('.playPauseBtn');
    const muteBtn = container.querySelector('.muteBtn');
    const progress = container.querySelector('.progress');
    const currentTimeText = container.querySelector('.currentTime');
    const durationText = container.querySelector('.duration');
    const openPopupBtn = container.querySelector('.openPopupBtn'); 
    const popupVideo = document.getElementById('popupVideo');      
    const videoModal = document.getElementById('videoModal');      
    const closeModalBtn = document.getElementById('closeModalBtn');

    // Format time (mm:ss)
    function formatTime(time) {
        const minutes = Math.floor(time / 60);
        const seconds = Math.floor(time % 60);
        return `${minutes}:${seconds.toString().padStart(2, '0')}`;
    }

    // Initial metadata load
    video.addEventListener('loadedmetadata', () => {
        durationText.innerText = formatTime(video.duration);
    });

    // Play button
    playBtn.addEventListener('click', () => {
        video.play();
        playBtn.classList.add('hidden');
        playPauseBtn.innerText = '⏸';
    });

    // Toggle play/pause on video click
    video.addEventListener('click', () => {
        if (video.paused) {
            video.play();
            playBtn.classList.add('hidden');
            playPauseBtn.innerText = '⏸';
        } else {
            video.pause();
            playBtn.classList.remove('hidden');
            playPauseBtn.innerText = '▶';
        }
    });

    // Play/Pause Button
    playPauseBtn.addEventListener('click', () => {
        if (video.paused) {
            video.play();
            playBtn.classList.add('hidden');
            playPauseBtn.innerText = '⏸';
        } else {
            video.pause();
            playBtn.classList.remove('hidden');
            playPauseBtn.innerText = '▶';
        }
    });

    // Mute / Unmute
    muteBtn.addEventListener('click', () => {
        video.muted = !video.muted;
        muteBtn.innerText = video.muted ? '🔈' : '🔊';
    });

    // Video ended
    video.addEventListener('ended', () => {
        playBtn.classList.remove('hidden');
        playPauseBtn.innerText = '▶';
    });

    // Update progress and time
    video.addEventListener('timeupdate', () => {
        if (!isNaN(video.duration)) {
            progress.value = (video.currentTime / video.duration) * 100;
            currentTimeText.innerText = formatTime(video.currentTime);
        }
    });

    // Seek video
    progress.addEventListener('input', () => {
        const seekTime = (progress.value / 100) * video.duration;
        video.currentTime = seekTime;
    });

    // Open modal popup
if (openPopupBtn) {
    openPopupBtn.addEventListener('click', () => {
        popupVideo.innerHTML = '';
        Array.from(video.getElementsByTagName('source')).forEach(source => {
            const cloned = document.createElement('source');
            cloned.src = source.src;
            cloned.type = source.type;
            popupVideo.appendChild(cloned);
        });

     
        popupVideo.muted = false;

        popupVideo.load();
        popupVideo.currentTime = video.currentTime;
        popupVideo.play();
        videoModal.classList.add('active');
    });
}

// Close modal popup
if (closeModalBtn) {
    closeModalBtn.addEventListener('click', () => {
        popupVideo.pause();
        popupVideo.currentTime = 0;
        videoModal.classList.remove('active');
    });
}



});
        


// header 5 js

document.addEventListener('DOMContentLoaded', function () {
    const gearToggle = document.querySelector('.gear-toggle');
    const topBarContent = document.querySelector('.top-bar-content');

    if (gearToggle && topBarContent) {
        gearToggle.addEventListener('click', function () {
            this.classList.toggle('active');
            topBarContent.classList.toggle('show');
        });
    }
});

document.addEventListener('DOMContentLoaded', function () {
    const searchIconDesktop = document.querySelector('.search-icon-desktop');
    const searchIconMobile = document.querySelector('.search-icon-mobile');
    const searchContainer = document.querySelector('.desktop-search-container');
    const searchClose = document.querySelector('.search-close-desktop');
    const headerMenus = document.querySelector('.header-menus-container');
    const searchInput = document.getElementById('search');

    function openSearch() {
        if (searchContainer) {
            searchContainer.classList.remove('d-none');
            setTimeout(() => {
                searchContainer.classList.add('expanded');
                $('.search-icon-mobile-hide').addClass('d-none');
                if (headerMenus) headerMenus.classList.add('hidden');
            }, 10);
            if (searchInput) searchInput.focus();
        }
    }

    function closeSearch() {
        if (searchContainer) {
            searchContainer.classList.remove('expanded');
            setTimeout(() => {
                $('.search-icon-mobile-hide').removeClass('d-none');
                searchContainer.classList.add('d-none');
                if (headerMenus) headerMenus.classList.remove('hidden');
            }, 300);
        }
    }

    [searchIconDesktop, searchIconMobile].forEach(icon => {
        if (icon) {
            icon.addEventListener('click', function (e) {
                e.preventDefault();
                openSearch();
            });
        }
    });

    if (searchClose && searchInput) {
        searchClose.addEventListener('click', function (e) {
            e.preventDefault();
            if (searchInput.value.trim() !== '') {
                searchInput.value = '';
                searchInput.focus();
            } else {
                closeSearch();
            }
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !searchContainer.classList.contains('d-none')) {
            closeSearch();
        }
    });

    document.addEventListener('click', function (e) {
    if (
        (!searchContainer || !searchContainer.contains(e.target)) &&
        (!searchIconDesktop || !searchIconDesktop.contains(e.target)) &&
        (!searchIconMobile || !searchIconMobile.contains(e.target))
    ) {
        if (searchContainer && !searchContainer.classList.contains('d-none')) {
            closeSearch();
        }
    }
});


    if (searchInput && searchClose) {
    searchInput.addEventListener('input', function () {
        const icon = searchClose.querySelector('i');
        if (!icon) return; 

        if (this.value.trim()) {
            icon.classList.remove('la-search');
            icon.classList.add('la-times');
        } else {
            icon.classList.remove('la-times');
            icon.classList.add('la-search');
        }
    });
}
});



function toggleChildDropdown(el, event) {
    if (event) event.stopPropagation();

    let $submenu = $(el).next('.child-dropdown');
    if ($submenu.hasClass('show')) {
        $submenu.removeClass('show');
    } else {
        $('.child-dropdown').removeClass('show');
        $submenu.addClass('show');
    }
}



$('#category-menu-bar').on('click', function () {
    $(this).find('.menu-icon').toggleClass('rotated');
});


//product details gallery
$('.product-gallery-carousel').on('beforeChange', function(event, slick, currentSlide, nextSlide){
    $(slick.$slides[currentSlide]).find('iframe').each(function(){
        let src = $(this).attr('src');
        $(this).attr('src', src);
    });
});

//destroy zoom when mouse on expand btn 
// Only run hover zoom disable on non-touch devices
if (!("ontouchstart" in window || navigator.maxTouchPoints > 0 || navigator.msMaxTouchPoints > 0)) {
    $(document).on("mouseenter", ".wd-show-product-gallery-wrap a", function(e){
        $(".img-zoom").trigger("zoom.destroy");
    }).on("mouseleave", ".wd-show-product-gallery-wrap a", function(e){
        $(".img-zoom").zoom({ magnify: 1.5 });
    });
}


//for custom x logo
document.addEventListener("DOMContentLoaded", function () {
    const xLogo = `
      <svg viewBox="0 0 1200 1227" xmlns="http://www.w3.org/2000/svg">
        <path d="M714.2 529.5 1160 0H1050.9L666.4 
          450.9 357.2 0H0l468.9 681.4L0 1226.9h109.1l409-493.3 
          330.2 493.3H1200L714.2 529.5zm-144.5 
          170.9-47.4-68.1L160 79.8h154.6l304.9 
          438.8 47.4 68.1 395.2 568.5H907.5L569.7 
          700.4z" />
      </svg>
    `;
    
    document.querySelectorAll('.aiz-custom-x-logo').forEach(el => {
      el.outerHTML = xLogo.replace('<svg', '<svg class="aiz-custom-x-logo"');
    });
});

$(".jssocials-share-twitter .jssocials-share-link")
  .css("background-color", "#332f2fff") 
  .hover(
    function() {
      $(this).css("background-color", "#000"); 
    },
    function() {
      $(this).css("background-color", "#332f2fff");
    }
);

/* top-banner-scroll-text */
function initTopBarScroll() {
    const inners = document.querySelectorAll(".top-banner-scroll-inner");
    const speed = 80; 

    inners.forEach(inner => {
        const parentWidth = inner.parentElement.offsetWidth;
        const textWidth = inner.scrollWidth;

        inner.style.animation = "none";
        inner.style.transform = "translateX(0)";
        inner.style.animationPlayState = "running";

        if (textWidth > parentWidth) {
            if (!inner.dataset.cloned) {
                const clone = inner.innerHTML;
                inner.innerHTML += clone;
                inner.dataset.cloned = true;
            }

            const totalWidth = inner.scrollWidth;
            const duration = totalWidth / speed; 

            inner.style.animation = `top-banner-scroll-left ${duration}s linear infinite`;
            inner.style.animationPlayState = "running"; 
        } else {
            inner.style.display = "flex";
            inner.style.justifyContent = "center";
        }
    });
}

document.addEventListener("DOMContentLoaded", () => {
    const inners = document.querySelectorAll(".top-banner-scroll-inner");

    inners.forEach(inner => {
        inner.addEventListener("mouseenter", () => {
            inner.style.animationPlayState = "paused";
        });

        inner.addEventListener("mouseleave", () => {
            inner.style.animationPlayState = "running";
        });
    });
});

document.addEventListener("DOMContentLoaded", initTopBarScroll);
window.addEventListener("resize", initTopBarScroll);



// Gallery Image vIEWER 

document.addEventListener("DOMContentLoaded", function() {
    const modal = document.getElementById("lightboxModal");
    const modalImg = document.getElementById("lightboxImage");
    const closeBtn = document.querySelector(".lightbox-close");
    const zoomInBtn = document.getElementById("zoomInBtn");
    const zoomOutBtn = document.getElementById("zoomOutBtn");
    const fullscreenBtn = document.getElementById("fullscreenBtn");
    const copyLinkBtn = document.getElementById("copyLinkBtn");
    const downloadBtn = document.getElementById("downloadBtn");
    const prevBtn = document.getElementById("prevBtn");
    const nextBtn = document.getElementById("nextBtn");
    const imageCounter = document.getElementById("imageCounter");

    const galleryImgs = document.querySelectorAll(".lightbox-item img.lightbox-source");

    let currentIndex = 0;
    let scale = 1;

    function openModal(index, direction = 0) {
        if (!modal || !modalImg) return;

        currentIndex = index;
        modal.style.display = "block";

        // Slide animation
        modalImg.style.transition = "transform 0.3s ease, opacity 0.3s ease";
        modalImg.style.transform = `translate(calc(-50% + ${direction * 100}%), -50%) scale(${scale})`;
        modalImg.style.opacity = 0;

        setTimeout(() => {
            modalImg.src = galleryImgs[index].dataset.src || galleryImgs[index].src;
            scale = 1; // reset zoom
            modalImg.style.transform = `translate(-50%, -50%) scale(${scale})`;
            modalImg.style.opacity = 1;
            $('.lightbox-content').css('cursor', 'zoom-in');
            updateCounter();
        }, 200);
    }

    function updateCounter() {
        if (imageCounter) {
            imageCounter.textContent = `${currentIndex + 1} / ${galleryImgs.length}`;
        }
    }

    // Gallery buttons
    document.querySelectorAll(".woodmart-show-product-gallery").forEach((btn, i) => {
        btn.addEventListener("click", function(e) {
            e.preventDefault();
            openModal(i);
        });
    });

    // Double click to zoom
    if (modalImg) {
        modalImg.addEventListener("dblclick", () => {
            scale = scale === 1 ? 2 : 1;
            modalImg.style.transform = `translate(-50%, -50%) scale(${scale})`;
            $('.lightbox-content').css('cursor', scale === 1 ? 'zoom-in' : 'zoom-out');
        });
    }

    // Close modal
    if (closeBtn && modal) {
        closeBtn.onclick = () => modal.style.display = "none";
    }

    // Previous image
    if (prevBtn) {
        prevBtn.onclick = () => {
            currentIndex = (currentIndex - 1 + galleryImgs.length) % galleryImgs.length;
            openModal(currentIndex, 0);
        };
    }

    // Next image
    if (nextBtn) {
        nextBtn.onclick = () => {
            currentIndex = (currentIndex + 1) % galleryImgs.length;
            openModal(currentIndex, 0);
        };
    }

    // Zoom in
    if (zoomInBtn && modalImg) {
        zoomInBtn.onclick = () => {
            scale += 0.2;
            modalImg.style.transform = `translate(-50%, -50%) scale(${scale})`;
            $('.lightbox-content').css('cursor', 'zoom-out');
        };
    }

    // Zoom out
    if (zoomOutBtn && modalImg) {
        zoomOutBtn.onclick = () => {
            scale = Math.max(0.2, scale - 0.2);
            modalImg.style.transform = `translate(-50%, -50%) scale(${scale})`;
            $('.lightbox-content').css('cursor', 'zoom-in');
        };
    }

    // Fullscreen toggle
    if (fullscreenBtn && modal) {
        fullscreenBtn.onclick = () => {
            if (!document.fullscreenElement) {
                modal.requestFullscreen();
            } else {
                document.exitFullscreen();
            }
        };
    }

    // Copy link
    if (copyLinkBtn && modalImg) {
        copyLinkBtn.onclick = () => {
            navigator.clipboard.writeText(modalImg.src)
                .then(() => AIZ.plugins.notify('success', 'Link copied to clipboard'));
        };
    }

    // Download
    if (downloadBtn && modalImg) {
        downloadBtn.onclick = () => {
            const link = document.createElement('a');
            link.href = modalImg.src;
            link.download = 'image';
            link.click();
        };
    }

    // Close modal on outside click
    if (modal) {
        modal.addEventListener("click", function(e) {
            if (e.target === modal) modal.style.display = "none";
        });
    }

    // Arrow keys navigation
    document.addEventListener("keydown", function(e) {
        if (!modal || modal.style.display !== "block") return;
        if (e.key === "ArrowRight" && nextBtn) nextBtn.click();
        if (e.key === "ArrowLeft" && prevBtn) prevBtn.click();
        if (e.key === "Escape") modal.style.display = "none";
    });
});

// smart bar 
document.addEventListener("DOMContentLoaded", function () {

    const smartBar = document.getElementById('smart-bar');
    const trigger = document.getElementById('smart-bar-trigger');

    if (!smartBar || !trigger) return;

    smartBar.classList.remove('visible');
    smartBar.classList.add('hidden');

    const showSmartBar = () => {
        smartBar.classList.remove('hidden');
        smartBar.classList.add('visible');
    };

    const hideSmartBar = () => {
        smartBar.classList.remove('visible');
        smartBar.classList.add('hidden');
    };

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach(entry => {

                if (entry.isIntersecting && entry.boundingClientRect.top >= 0) {
                    showSmartBar();
                }
                else if (entry.boundingClientRect.top > 0) {
                    hideSmartBar();
                }
                else {
                    showSmartBar();
                }

            });
        },
        {
            root: null,
            threshold: 0
        }
    );

    observer.observe(trigger);

    const triggerPosition = trigger.getBoundingClientRect();
    if (triggerPosition.top <= window.innerHeight) {
        showSmartBar();
    } else {
        hideSmartBar();
    }

});


function closeSmartBar() {
    const bar = document.getElementById('smart-bar');
    if (!bar) return;

    bar.classList.add('closing');

    bar.addEventListener('transitionend', function handler() {
        bar.removeEventListener('transitionend', handler);
        bar.remove();
    });
}

function scrollToTopSection() {
    const topSection = document.querySelector('.product-details > .container');

    if(topSection) {
        topSection.scrollIntoView({
            behavior: 'smooth',
            block: 'start'     
        });
    }
}



//for header-6 gear icon show-hide
function hideOptions() {
    document.querySelector('.silder-toggle').classList.add('d-none');
}

function showOptions() {
    document.querySelector('.silder-toggle').classList.remove('d-none');
}



// Product Clone

function duplicateProduct(productId = null, type = null) {
    let selectedProductId;

    if (productId === null) {
        var selectedProduct = $('input[name="selected_product_id"]:checked');
        selectedProductId = selectedProduct.val();

        if (!selectedProductId) {
            AIZ.plugins.notify('warning', 'Please select a product');
            return;
        }

        $('#products_select_modal').modal('hide');
    } else {
        selectedProductId = productId;
    }

    $('#loading-modal').modal('show', { backdrop: 'static' });

    $.ajax({
        url: duplicateProductUrl.replace(':id', selectedProductId) + (type ? '?type=' + type : ''),
        method: 'GET',
        success: function(response) {
            $('#loading-modal').modal('hide');
           if (response.success) {
                AIZ.plugins.notify('success', response.message);
            } else {
                AIZ.plugins.notify('danger', response.message);
                return; 
            }


            if (response.redirect) {
                setTimeout(function() {
                    window.location.href = response.redirect;
                }, 1000);
            } else {
                setTimeout(function() {
                    location.reload();
                }, 1000);
            }
        },
        error: function(xhr) {
            $('#loading-modal').modal('hide');
            AIZ.plugins.notify('danger', 'Something went wrong');
        }
    });
}



// Open Bulk Action Modal

function showBulkActionModal(){

    $('#bulk-action-modal').modal('show');
}

//Hide BulkActionModal
function hideBulkActionModal(){
    $('#bulk-action-modal').modal('hide');
}

function initFooTable() {
    generateDetails();
    initToggles();
    handleResize();
    updateToggleVisibility(); // Initial check
}

/* Build details rows for hidden columns */
function generateDetails() {
    // Remove old details rows
    document.querySelectorAll(".details-row").forEach(el => el.remove());

    document.querySelectorAll(".data-row").forEach(row => {
        const details = document.createElement("tr");
        details.className = "details-row";

        // Mark this details row as belonging to its data row
        details.dataset.parentId = row.dataset.rowId || row.getAttribute("id") || [...document.querySelectorAll(".data-row")].indexOf(row);

        const td = document.createElement("td");
        td.colSpan = row.children.length;

        let html = "";

        [...row.children].forEach(cell => {
            if (cell.querySelector(".toggle-plus-minus-btn")) return;

            if (window.getComputedStyle(cell).display === "none") {
                const label = cell.dataset.label || "";
                html += `
                    <div class="detail-item py-10px">
                        <span class="detail-label d-block text-uppercase fs-12 fw-700 text-secondary mb-2">
                            ${label}:
                        </span>
                        <span class="detail-value d-block">${cell.innerHTML}</span>
                    </div>
                `;
            }
        });

        td.innerHTML = html;
        details.appendChild(td);

        row.insertAdjacentElement("afterend", details);

        // check if it was open before rebuild
        const toggleBtn = row.querySelector(".toggle-plus-minus-btn");
        if (toggleBtn && toggleBtn.textContent === "-") {
            details.style.display = "table-row";
            toggleBtn.textContent = "-"; // keep minus
        } else {
            details.style.display = "none";
            if (toggleBtn) toggleBtn.textContent = "+";
        }
    });
}

/* Toggle button handler */
function initToggles() {
    document.querySelectorAll(".toggle-plus-minus-btn").forEach(btn => {
        btn.onclick = () => {
            const row = btn.closest("tr");
            const details = row.nextElementSibling;
            const isOpen = details.style.display === "table-row";

            details.style.display = isOpen ? "none" : "table-row";
            btn.textContent = isOpen ? "+" : "-";
        };
    });
}

/* Show toggle only if there are hidden columns */
function updateToggleVisibility() {
    document.querySelectorAll(".data-row").forEach(row => {
        const toggleBtn = row.querySelector(".toggle-plus-minus-btn");
        if (!toggleBtn) return;

        const hasHidden = [...row.children].some(cell =>
            cell !== toggleBtn.parentElement && window.getComputedStyle(cell).display === "none"
        );

        toggleBtn.style.display = hasHidden ? "inline-block" : "none";

        // Reset to + if no hidden columns (safety)
        if (!hasHidden) {
            const details = row.nextElementSibling;
            if (details && details.classList.contains("details-row")) {
                details.style.display = "none";
            }
            toggleBtn.textContent = "+";
        }
    });
}

function handleResize() {
    const openStates = new Map();
    document.querySelectorAll(".data-row").forEach(row => {
        const btn = row.querySelector(".toggle-plus-minus-btn");
        const key = row.dataset.rowId || [...document.querySelectorAll(".data-row")].indexOf(row);
        if (btn) {
            openStates.set(key, btn.textContent === "-");
        }
    });

    generateDetails();
    document.querySelectorAll(".data-row").forEach(row => {
        const btn = row.querySelector(".toggle-plus-minus-btn");
        if (!btn) return;

        const key = row.dataset.rowId || [...document.querySelectorAll(".data-row")].indexOf(row);
        if (openStates.get(key)) {
            const details = row.nextElementSibling;
            if (details && details.classList.contains("details-row")) {
                details.style.display = "table-row";
            }
            btn.textContent = "-";
        }
    });

    // Step 4: Update toggle visibility (in case breakpoints changed)
    updateToggleVisibility();
}

// Throttle resize for performance
let resizeTimeout;
window.addEventListener("resize", () => {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(handleResize, 100);
});
//behabiour for Table Nav Tabs Scroll 
document.addEventListener('DOMContentLoaded', () => {
    const tableTabsContainer = document.querySelector('.table-tabs-container');
    // If element not found — break
    if (!tableTabsContainer) return;
    const tableTabs = tableTabsContainer.querySelectorAll('.nav-link');
    tableTabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const offset =
                tab.offsetLeft - tableTabsContainer.clientWidth / 2 + tab.clientWidth / 2;

            tableTabsContainer.scrollTo({
                left: offset,
                behavior: "smooth"
            });
        });
    });
});



function startTimer() {
    if (currentIdx >= cardIds.length) return;

    let activeId = cardIds[currentIdx];
    let $card = $('#' + activeId);
    let $bar = $('#fill-' + activeId);
    let width = 0;

    // show close button
    $card.find('.btn-close-stack').removeClass('d-none');

    clearInterval(timer);

    // dynamic duration (seconds)
    let durationSeconds = window.POPUP_DURATION || 5;

    // 100 steps progress bar
    let intervalTime = (durationSeconds * 1000) / 100;

    timer = setInterval(() => {
        width += 1;
        $bar.css('width', width + '%');

        if (width >= 100) {
            clearInterval(timer);
            removeTopCard(activeId, true);
        }
    }, intervalTime);
}

function removeTopCard(id, auto = false) {

    if (id !== cardIds[currentIdx]) return;

    let $card = $('#' + id);

    // store session removal state
    let key = $card.closest('.removable-session').data('key');
    if (key) {
        const item = {
            value: "removed",
            expiry: Date.now()+(3600000),
        };
        localStorage.setItem(key, JSON.stringify(item));
    }

    $card.addClass('exit-now');

    setTimeout(() => {
        $card.hide();
        currentIdx++;
        realignStack();

        if (currentIdx >= cardIds.length) {
            $('#stack-popup-main-wrapper').fadeOut(200);
            $('body').removeClass('modal-open');
            $('.absolute-full').fadeOut(200);
            return;
        }

        startTimer();
    }, 500);
}

function realignStack() {
    $('.card-wrapper:visible').each(function (i) {
        $(this).removeClass(function (index, className) {
            return (className.match(/(^|\s)card-pos-\S+/g) || []).join(' ');
        });
        $(this).addClass('card-pos-' + i);
    });
}

function smoothlyRemoveElement($element) {
    if (!$element || $element.length === 0) return;
    $element.addClass('removing');

    setTimeout(() => {
        try {
            var sessionKey = $element.data('key') || $element.closest('.removable-session').data('key');
            if (sessionKey) {
                const now = new Date();
                const item = {
                    value: 'removed',
                    expiry: now.getTime() + 3600000
                };
                localStorage.setItem(sessionKey, JSON.stringify(item));
            }
        } catch (err) {
            console.error('safely storing alert-dismissal key failed', err);
        }

        $element.remove();
        $(document).trigger('alertRemoved', [$element]);
    }, 600);
}

// new image Uploader 

$(document).ready(function() {

    $(document).on('click', '.direct-uploader', function(e) {
        if (!$(e.target).is('input')) {
            $(this).find('input[type="file"]')[0].click();
        }
    });

    $(document).on('change', '.direct-uploader input', function(e) {
        let input = this;
        let files = Array.from(input.files);
        let wrapper = $(this).closest('.form-group');
        let previewBox = wrapper.find('.direct-preview');
        let errorBox = wrapper.find('.upload-error');
        let isMultiple = $(this).prop('multiple');

        errorBox.text('');
        const allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
        let invalidFiles = files.filter(f => {
            const ext = f.name.split('.').pop().toLowerCase();
            return !allowedExts.includes(ext);
        });

        if (invalidFiles.length > 0) {
            errorBox.text('Only JPG, JPEG, PNG, and WEBP images are allowed.');
            input.value = '';
            return;
        }

        let existingCount = previewBox.find('.direct-file-preview-item').length;
        let totalCount = existingCount + files.length;

        if (isMultiple && totalCount > 6) {
            errorBox.text('Maximum 6 files are allowed.');
            input.value = '';
            return;
        }

        if (!isMultiple) {
            previewBox.html('');
        } else {
            let dt = new DataTransfer();

            if (input._storedFiles) {
                input._storedFiles.forEach(f => dt.items.add(f));
            }

            files.forEach(f => dt.items.add(f));

            input.files = dt.files;
            input._storedFiles = Array.from(dt.files);
        }

        files.forEach((file) => {

            if (!file.type.startsWith('image/') && !file.type.startsWith('video/')) return;

            let reader = new FileReader();

            reader.onload = function(e) {

                if (file.type.startsWith('image/')) {
                    let img = new Image();
                    img.src = e.target.result;
                    img.onload = function() {
                        EXIF.getData(img, function() {
                            let orientation = EXIF.getTag(this, "Orientation") || 1;
                            let canvas = document.createElement('canvas');
                            let ctx = canvas.getContext('2d');

                            let width = img.width;
                            let height = img.height;

                            let maxSize = 800;

                            if (width > maxSize || height > maxSize) {
                                let scale = Math.min(maxSize / width, maxSize / height);
                                width = Math.round(width * scale);
                                height = Math.round(height * scale);
                            }

                            switch (orientation) {

                                case 2: 
                                    canvas.width = width;
                                    canvas.height = height;
                                    ctx.translate(width, 0);
                                    ctx.scale(-1, 1);
                                    break;

                                case 3: 
                                    canvas.width = width;
                                    canvas.height = height;
                                    ctx.translate(width, height);
                                    ctx.rotate(Math.PI);
                                    break;

                                case 4: 
                                    canvas.width = width;
                                    canvas.height = height;
                                    ctx.translate(0, height);
                                    ctx.scale(1, -1);
                                    break;

                                case 5:
                                    canvas.width = height;
                                    canvas.height = width;
                                    ctx.rotate(0.5 * Math.PI);
                                    ctx.scale(1, -1);
                                    break;

                                case 6: 
                                    canvas.width = height;
                                    canvas.height = width;
                                    ctx.rotate(0.5 * Math.PI);
                                    ctx.translate(0, -height);
                                    break;

                                case 7:
                                    canvas.width = height;
                                    canvas.height = width;
                                    ctx.rotate(0.5 * Math.PI);
                                    ctx.translate(width, -height);
                                    ctx.scale(-1, 1);
                                    break;

                                case 8: 
                                    canvas.width = height;
                                    canvas.height = width;
                                    ctx.rotate(-0.5 * Math.PI);
                                    ctx.translate(-width, 0);
                                    break;

                                default: 
                                    canvas.width = width;
                                    canvas.height = height;
                            }

                            ctx.drawImage(img, 0, 0, width, height);

                            let resizedDataUrl = canvas.toDataURL(file.type, 0.8);

                            let thumbHTML = `<img src="${resizedDataUrl}" class="img-fit">`;

                            appendPreview(wrapper, file, thumbHTML);

                        });

                    };

                } else if (file.type.startsWith('video/')) {
                    let thumbHTML = `
                        <video width="100%" height="100%" controls>
                            <source src="${e.target.result}" type="${file.type}">
                        </video>`;

                    appendPreview(wrapper, file, thumbHTML);
                }
            };

            reader.readAsDataURL(file);
        });
    });

    function appendPreview(wrapper, file, thumbHTML) {
        let previewBox = wrapper.find('.direct-preview');

        let html = `
            <div class="direct-file-preview-item" title="${file.name}">
                <div class="thumb d-flex justify-content-center align-items-center">
                    ${thumbHTML}
                </div>
                <div class="col body">
                    <h6 class="d-flex">
                        <span class="text-truncate title">${file.name}</span>
                    </h6>
                    <p>${(file.size/1024).toFixed(1)} KB</p>
                </div>
                <div class="remove">
                    <button class="btn btn-sm btn-link remove-btn" type="button">
                        <i class="la la-close"></i>
                    </button>
                </div>
            </div>
        `;

        previewBox.append(html);

        let totalItems = previewBox.find('.direct-file-preview-item').length;
        if (totalItems >= 6) {
            wrapper.find('.direct-uploader').hide();
        }
    }

    $(document).on('click', '.direct-preview .remove-btn', function() {
        let parent = $(this).closest('.direct-file-preview-item');
        let wrapper = $(this).closest('.form-group');
        let input = wrapper.find('input[type="file"]')[0];
        let fileName = parent.attr('title');

        if (input._storedFiles) {
            input._storedFiles = input._storedFiles.filter(f => f.name !== fileName);

            let dt = new DataTransfer();
            input._storedFiles.forEach(f => dt.items.add(f));
            input.files = dt.files;
        }

        parent.remove();

        if (input._storedFiles && input._storedFiles.length > 0) {
            const allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
            let hasInvalid = input._storedFiles.some(f => {
                const ext = f.name.split('.').pop().toLowerCase();
                return !allowedExts.includes(ext);
            });
            wrapper.find('.upload-error').text(hasInvalid ? 'Only JPG, JPEG, PNG, and WEBP images are allowed.' : '');
        } else {
            wrapper.find('.upload-error').text('');
        }

        let totalItems = wrapper.find('.direct-file-preview-item').length;
        if (totalItems < 6) {
            wrapper.find('.direct-uploader').show();
        }

        if (!$(input).prop('multiple')) {
            input.value = '';
        }
    });

});

document.addEventListener("DOMContentLoaded", function () {
    $(document).on('click', '.custom-submenu-toggle', function (e) {
        e.stopPropagation();
        e.preventDefault();
        var $toggle = $(this);
        var $subMenu = $toggle.closest('.custom-dropdown-submenu').find('.custom-submenu-nested');
        var $parentDropdown = $toggle.closest('.dropdown-menu');
        var isOpen = $subMenu.hasClass('show');
        $parentDropdown.find('.custom-submenu-nested').not($subMenu).removeClass('show flip-right');
        $parentDropdown.find('.custom-submenu-toggle').not($toggle).removeClass('open').attr('aria-expanded', 'false');
        if (isOpen) {
            $subMenu.removeClass('show flip-right');
            $toggle.removeClass('open').attr('aria-expanded', 'false');
        } else {
            $subMenu.addClass('show');
            $toggle.addClass('open').attr('aria-expanded', 'true');
            if (window.innerWidth > 767) {
                var rect = $subMenu[0].getBoundingClientRect();
                if (rect.left < 0) {
                    $subMenu.addClass('flip-right');
                }
            }
        }
    });

    $(document).on('hidden.bs.dropdown', '.dropdown', function () {
        $(this).find('.custom-submenu-nested').removeClass('show flip-right');
        $(this).find('.custom-submenu-toggle').removeClass('open').attr('aria-expanded', 'false');
    });
    
    $(document).on('click', function (event) {
        var $trigger = $("[data-toggle='collapse'][href='#collapseQuickMenu'], [data-toggle='collapse'][data-target='#collapseQuickMenu']");
        var $collapseMenu = $("#collapseQuickMenu");
        if (!$collapseMenu.is(event.target) && $collapseMenu.has(event.target).length === 0 && !$trigger.is(event.target) && $trigger.has(event.target).length === 0) {
            if ($collapseMenu.hasClass('show')) {
                $collapseMenu.collapse('hide');
            }
        }
    });
});