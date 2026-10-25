// AJAX form validation with smooth scroll
$(document).ready(function() {

    $('#aizSubmitForm').on('submit', function(e){
        e.preventDefault(); 
        
        var form = $(this);
        var formData = new FormData(this);
        var clickedButton = $(document.activeElement);
        var actionType = clickedButton.data('action') || clickedButton.val() || '';
        var submitBtn =actionType ? form.find('button[data-action="' + actionType + '"]') : form.find('button[type="submit"]');
        var originalBtnText = submitBtn.html();
        submitBtn.prop('disabled', true).html('<i class="las la-spinner la-spin"></i> ' + AIZ.local.saving + '...');
        if (actionType) {
            formData.append('button', actionType);
        }
        
        // Remove previous error messages and classes
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();
        $('.alert').remove();

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    AIZ.plugins.notify('success', response.message);
                    if ((actionType == 'publish' || actionType == 'unpublish') && typeof savedClearTempdata === 'function') 
                    {
                        savedClearTempdata();
                    }
                    if (response.redirect) {
                        setTimeout(function() {
                            window.location.href = response.redirect;
                        }, 1000);
                    }
                } else {
                    AIZ.plugins.notify('danger', AIZ.local.something_went_wrong);
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON.errors;
                    submitBtn.prop('disabled', false).html(originalBtnText);
                    displayValidationErrors(errors);
                } else {
                    AIZ.plugins.notify('danger', AIZ.local.error_occured_while_processing);
                }
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(originalBtnText);
            }
        });
    });

    // validation error
    function displayValidationErrors(errors) {
        var firstErrorField = null;
        var firstErrorTab = null;
        var processedFields = {};
        var categoryCard = $("#category-card");
        if(categoryCard.length ){
            categoryCard.removeClass("border border-danger");
        }

        $.each(errors, function(field, messages) {
           
            if (field === "category_id" && categoryCard.length) {
                var holder = $("#category-tree-table-error");
                holder.html(
                    '<div class="invalid-feedback d-block text-danger mt-2">' + messages[0] + '</div>'
                );
                categoryCard.addClass("border border-danger");
                firstErrorField = categoryCard;
                var tabPane = categoryCard.closest(".tab-pane");
                if (tabPane.length) {
                    var tabId = tabPane.attr("id");
                    var tabLink = $('.nav-link[data-target="#' + tabId + '"]');
                    if (tabLink.length) {
                        firstErrorTab = tabLink;
                    }
                }
                return;
            }
            if (processedFields[field]) {
                return; 
            }
            processedFields[field] = true;
            var inputs = $('[name="' + field.replace(/\.\d+/g, '[]') + '"]');
            inputs.addClass('is-invalid');
            var firstInput = inputs.first();
            var formGroup = firstInput.closest('.form-group');
            
            if (formGroup.length) {
                formGroup.append('<div class="invalid-feedback d-block text-left">' + messages[0] + '</div>');
            } else {
                firstInput.after('<div class="invalid-feedback d-block text-left">' + messages[0] + '</div>');
            }
            
            if (!firstErrorField && firstInput.length) {
                firstErrorField = firstInput;
                var tabPane = firstInput.closest('.tab-pane');
                if (tabPane.length) {
                    var tabId = tabPane.attr('id');
                    var tabLink = $('.nav-link[data-target="#' + tabId + '"]');
                    if (tabLink.length) {
                        firstErrorTab = tabLink;
                    }
                }
            }
        });

        // Activate the tab containing the first error
        if (firstErrorTab && firstErrorTab.length) {
            firstErrorTab.tab('show');
        }

        // Scroll to the first error field
        if (firstErrorField && firstErrorField.length) {
            setTimeout(function() {
                $('html, body').animate({
                    scrollTop: firstErrorField.offset().top - 120
                }, 10);
                firstErrorField.focus();
            }, 50);
        }
    }
});


// Helper function to show error messages
function showError(input, message) {
    const formGroup = input.closest('.form-group');
    $(formGroup).find('.invalid-feedback').remove(); // remove existing errors
    $(input).removeClass('is-valid').addClass('is-invalid');
    $(formGroup).append(`<div class="invalid-feedback d-block text-left">${message}</div>`);
}

// Helper function to mark input as valid
function showValid(input) {
    $(input).removeClass('is-invalid').addClass('is-valid');
    const formGroup = input.closest('.form-group');
    $(formGroup).find('.invalid-feedback').remove();
}

// Dynamic validation on input fields
$(document).on('input', '#aizSubmitForm input, #aizSubmitForm textarea, #aizSubmitForm select', function() {
    var input = $(this);
    var value = input.val().trim();
    var type = input.attr('type');

    // Required validation
    if (input.prop('required') && !value) {
        showError(input[0], 'This field is required.');
        return;
    } else if (!input.prop('required') && !value) {
        input.removeClass('is-valid is-invalid');
        return;
    }

    // Email validation
    if (type === 'email') {
        var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(value)) {
            showError(input[0], 'Please enter a valid email address.');
        } else {
            showValid(input[0]);
        }
        return;
    }

    // Numbers Validation
    if (type === 'number') {
        var numberPattern = /^\d*(\.\d*)?$/;
        let min = input.attr('min');
        let max = input.attr('max');
        let step = input.attr('step');
        let num = parseFloat(value);

        // Removeed invalid chars like -, +, e, E
        if (/[eE+\-]/.test(value)) {
            input.val(value.replace(/[eE+\-]/g, ''));
            showError(input[0], 'Invalid input. Only positive numbers are allowed.');
            return;
        }
        // check Regex input
        if (!numberPattern.test(value)) {
            let cleaned = value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');
            input.val(cleaned);
            showError(input[0], 'Only numbers and a single dot are allowed.');
            return;
        }

        if (value === '' || isNaN(num)) {
            showError(input[0], 'Enter a valid number.');
            return;
        }

        if (min && num < parseFloat(min)) {
            showError(input[0], `Minimum allowed value is ${min}.`);
            return;
        }

        if (max && num > parseFloat(max)) {
            showError(input[0], `Maximum allowed value is ${max}.`);
            return;
        }

         if (step) {
            let decimalLimit = (step.split('.')[1] || '').length;
            let valueDecimalPart = value.split('.')[1];
            if (valueDecimalPart && valueDecimalPart.length > decimalLimit) {
                showError(input[0], `Maximum ${decimalLimit} decimal places allowed.`);
                return;
            }
        }

        showValid(input[0]);
        return;
    }

    // Default: mark as valid if nothing else
    showValid(input[0]);
});


//Global Solutions
// Keydown listener to block invalid number characters
document.addEventListener("keydown", function(e) {
    const input = e.target;

    if (input.tagName === "INPUT" && input.type === "number") {
        if (["-", "Minus", "+", "e", "E"].includes(e.key)) {
            e.preventDefault();
            showError(input, 'Invalid input. Only positive numbers are allowed.');
        }
        
    }

    
});

document.addEventListener("input", function(e) {
    const input = e.target;
    if (input.hasAttribute("letter-only")) {
        const before = input.value;
        input.value = input.value.replace(/[^A-Za-z]/g, "");
        if (before !== input.value) {
            showError(input, "Only letters A–Z are allowed.");
        }
    }

    if (input.hasAttribute("integer-only")) {
        const before = input.value;
        input.value = input.value.replace(/[^0-9]/g, "");
        if (before !== input.value) {
            showError(input, "Only whole numbers are allowed (no decimals).");
        }
    }
    const maxLength = input.getAttribute("maxlength");
    if (!maxLength) return; 
    if (input.value.length == maxLength) {
        input.value = input.value.slice(0, maxLength); 
        showError(input, `Maximum ${maxLength} characters allowed.`);
    }
    
});


