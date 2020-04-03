function searchProduct(query) {
    var data = {
        action: "search-product",
        query: query
    };
    $.ajax({
        url: posUrlAccess,
        data: data,
        type: "POST",
        dataType: "text",
        startTime: performance.now(),
        success: function(data) {
            $('#searchResults').html(data);
            //Calculate the difference in milliseconds.
            var time = performance.now() - this.startTime;
            //Convert milliseconds to seconds.
            var seconds = time / 1000;
            console.log("Execution time: " + seconds.toFixed(3));
        },
        error: function(xhr, status) {
            alert("Sorry, there was a problem!");
            console.log(xhr);
        },
        complete: function(xhr, status) {
            $('#showresults').slideDown('slow')
        }
    });
}

function autocompleteGetData(searchInput, term) {
    var data = {
        action: "autocomplete",
        field: searchInput.data("field"),
        source: searchInput.data("source"),
        fieldcode: searchInput.data("fieldcode"),
        fieldtitle: searchInput.data("fieldtitle"),
        term: term
    };
    console.log(data);
    return data;
}

function addCartItem(item) {
    var templateScript = $("#cart-item-template").html();
    var template = Handlebars.compile(templateScript);

    var context={
        "id": item.data("id"),
        "desc": item.data("text")
    };

    var compiledHtml = template(context);

    $shoppingCart = $("#cartItems");
    $shoppingCart.append(compiledHtml);

    getCartData();
}

function onCartUpdate() {

}

function getCartData() {
    var rows = $('#cartItems').children();

    rows.each(function() {
        $(this).children().each(function () {
            console.log(this);
            $(this).children().each(function () {
                console.log(this.value);
            })
        });
    });
}

function setCustomerAutocomplete() {
    var customerInput = $("#codclienteAutocomplete");
    customerInput.autocomplete({
        source: function(request, response) {
            $.ajax({
                method: "POST",
                url: posAutocompleteUrl,
                data: autocompleteGetData(customerInput, request.term),
                dataType: "json",
                success: function(results) {
                    var values = [];
                    results.forEach(function(element) {
                        if (element.key !== null) {
                            values.push({ key: element.key, value: element.value });
                        } else {
                            values.push({ key: null, value: element.value });
                        }
                    });
                    response(values);
                },
                error: function(msg) {
                    alert(`${msg.status} ${msg.responseText}`);
                }
            });
        },
        select: function(event, ui) {
            if (ui.item.key !== null) {
                $("#codcliente").val(ui.item.key);
                var value = ui.item.value.split(" | ");
                if (value.length > 1) {
                    ui.item.value = value[1];
                } else {
                    ui.item.value = value[0];
                }
            }
        }
    });
}
$(document).ready(function() {
    setCustomerAutocomplete();
    $("#cashupButton").click(function() {
        $("#cashupModal").modal('show');
    });
    $("#checkoutButton").click(function() {
        showCheckoutModal();
    });
    $("#checkoutPaymentAmount").keyup(function(e) {
        processPaymentAmount();
    });
    $('#checkoutPaymentMethod').change(function(e) {
        processPaymentAmount();
    });
    $('#searchInput').keyup(function() {
        searchProduct($(this).val());
    });
    $('#searchResults').on('click', '.item-add-button', function() {
        addCartItem($(this));
    });
});