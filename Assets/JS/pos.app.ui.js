var cartItemsList = [];

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
            $('#searchProductResult').html(data);
            //Calculate the difference in milliseconds.
            var time = performance.now() - this.startTime;
            //Convert milliseconds to seconds.
            var seconds = time / 1000;
            console.log("Execution time: " + seconds.toFixed(3));
        },
        error: function(xhr, status) {
            alert("Sorry, there was a problem!");
            console.log(xhr);
        }
    });
}

function searchCustomer(query) {
    var data = {
        action: "search-customer",
        query: query,
    };

    $.ajax({
       url: posUrlAccess,
       data: data,
       type: "POST",
       dataType: "text",
       success: function (data) {
           $('#searchCustomerResult').html(data);
       },
       error: function (xhr, status) {
           console.log('Error: ');
           console.log(xhr.responseText);
       }
    });
}

function setCustomer(customer) {
    $('#codcliente').val(customer.data('code'));
    $('#searchCustomer').val(customer.data('description'));

    $('#searchCustomerModal').modal('hide');
}

function addCartItem(item) {
    var templateScript = $('#cart-item-template').html();
    var template = Handlebars.compile(templateScript);

    var context={
        "id": item.data("id"),
        "desc": item.data("text")
    };

    var compiledHtml = template(context);

    $shoppingCart = $('#cartItems');
    $shoppingCart.append(compiledHtml);

    $('#searchProductModal').modal('hide');

    var cartItem = new CartItem(context.id, context.desc);
    cartItemsList[cartItem.code] = cartItem;
    console.log(cartItemsList);
}

function onCartUpdate() {

}

function getCartData() {
    var rows = $('#cartItemsAccordion');

    rows.each(function(){
        $(this).find(':input') ;
        console.log($(this).find(':input'));
    });
}

$(document).ready(function() {
    $('#cashupButton').click(function() {
        $('#cashupModal').modal('show');
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

    /*Customer Search Events*/
    $('#searchCustomer').focus(function () {
        $('#searchCustomerModal').modal('show');
    });
    $('#searchCustomerModal').on('shown.bs.modal', function () {
        $('#searchCustomerInput').focus();
    });
    $('#searchCustomerInput').keyup(function () {
        searchCustomer($(this).val());
    });
    $('#searchCustomerResult').on('click', '.item-add-button', function() {
        setCustomer($(this));
    });

    /*Product Search Events*/
    $('#searchProduct').focus(function () {
        $('#searchProductModal').modal('show');
    });
    $('#searchProductModal').on('shown.bs.modal', function () {
        $('#searchProductInput').focus();
    });
    $('#searchProductInput').keyup(function () {
        searchProduct($(this).val());
    });
    $('#searchProductResult').on('click', '.item-add-button', function() {
        addCartItem($(this));
    });
});