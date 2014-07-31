var isClear = false; // чистая ли таблица с данными
var isSave = false; // нужна ли перегрузка после закрытия окна
var id_shop = $("div.body-content.edit-shop").attr('id'); // id магазина

/* Обработка input файла, в форме импорта */
$('#InputFile').change(function(e){
    if(isClear)
    {
        $("table#ImportItemFile tbody").empty();
        isClear = false;
    }
    var file_extension = $(this).val().split(".").pop().toLowerCase();

    if($.inArray(file_extension, ["csv", "txt"]) == -1) {
        $("table#ImportItemFile").before('<p id="ErrorType">Загружаемый файл должен быть типа CSV или TXT</p>');
        return false;
    }

    if (e.target.files != undefined) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var line = e.target.result.split("\n");

            $("table#ImportItemFile").show();
            for(var i = 0; i < line.length; i++)
            {
                line[i] = $.trim(line[i]);
                if(line[i].slice(-1) == ';') line[i] = line[i].substring(0, line[i].length - 1);
                var column = line[i].split(/,|;/);
                if (column.length == 4)
                {
                    var table = '<tr id="' + column[0] + '">';
                    for(var j = 0; j < column.length; j++)
                    {
                        var temp = column[j].replace(/\s/g, '');
                        if (temp == 'null') temp = '—';
                        if(j == 0) table += '<td><img src="/images/items/' + temp + '.png" class="small-icon"/></td>';
                        else table += '<td>' + temp + '</td>';
                    }
                    table += '<td id="sp"></td></tr>';
                    $("table#ImportItemFile tbody").append(table);
                }
            }
        };
        reader.readAsText(e.target.files.item(0));
    }
    return false;
});

/* Форма добавления товаров */
$("form#AddItemForm").submit(function( event ) {
    if(isClear)
    {
        $("table#AddItemTable tbody").empty();
        isClear = false;
    }
    var item_id = $("input#InputID").val();
    var price_sell = $("input#InputBuy").val();
    var price_buy = $("input#InputSell").val();
    var stuck = $("input#InputStuck").val();

    if(item_id && stuck && (price_sell || price_buy))
    {
        if (!price_sell) price_sell = '—';
        if (!price_buy) price_buy = '—';
        $("table#AddItemTable").show();
        var line = '<tr id="' + item_id + '"><td><img src="/images/items/' + item_id + '.png" class="small-icon"/></td><td>' + price_sell + '</td><td>' + price_buy + '</td><td>' + stuck + '</td><td id="sp"></td></tr>';
        $("table#AddItemTable tbody").append(line);
        $('form#AddItemForm').trigger('reset');
    }
    event.preventDefault();
});

/* обработка таблицы, отправка запросов */
$('button#sync').click(function() {
    isClear = true;
    var table = $("table.AddItem:visible > tbody");
    $("td#sp", table).spin('show');

    setTimeout(function(){
        $("tr", table).each(function(index) {
            var that = this;
            var t = setTimeout(function() {
                var id_item = $(that).attr("id");
                var price_sell = $("td", that).eq(1).text();
                var price_buy = $("td", that).eq(2).text();
                var stuck = $("td", that).eq(3).text();
                if (isNaN(price_sell)) price_sell = null;
                if (isNaN(price_buy)) price_buy = null;
                console.log(price_sell + ':' + price_buy);
                var update = $.ajax({
                    type: "GET",
                    url: "/shop/edititem",
                    cache: false,
                    async: false,

                    data: { id_shop: id_shop, id_item: id_item,  price_sell: price_sell, price_buy: price_buy, stuck: stuck }
                }).responseText;

                $("td", that).eq(4).html(update);
            }, 100 * index);
        });
    }, 300);
    isSave = true;
});


/* Нажатие на товар в списке, его редактирование */
var that;
$("button#editButtons").click(function() {
    that = $(this).parents("tr");
    $("#EditModal").modal('show');
    var id_item = $('td', that).eq(1).text();
    var name = $('td', that).eq(2).text();
    var price_sell = $('td', that).eq(3).text();
    var price_buy = $('td', that).eq(5).text();
    var stuck = $('td', that).eq(7).text();

    if (isNaN(price_sell)) price_sell = null;
    if (isNaN(price_buy)) price_buy = null;

    $(".modal#EditModal label#name").html('<img class="small-icon" src="/images/items/' + id_item + '.png" alt="' + name + '">');
    $(".modal#EditModal p#name").text(name + ' (' + id_item + ')');
    $(".modal#EditModal #IdHide").val(id_item);
    $(".modal#EditModal #Sell").val(price_sell);
    $(".modal#EditModal #Buy").val(price_buy);
    $(".modal#EditModal #Stuck").val(stuck);
});

/* POST запрос редактирование */
$(".modal#EditModal button#editButtonModal").click(function(e){
    var form = $("form#EditItemForm");

    var id_item = $("#IdHide", form).val();
    var price_sell = $("#Sell", form).val();
    var price_buy = $("#Buy", form).val();
    var stuck = $("#Stuck", form).val();

    $.ajax({
        type: "GET",
        url: "/shop/edititem",
        cache: false,
        async: false,
        data: { id_item: id_item, id_shop: id_shop, price_sell: price_sell, price_buy: price_buy, stuck: stuck },
        success: function() {
            $("#EditModal").modal('hide');
            if (!price_sell) price_sell = '—';
            if (!price_buy) price_buy = '—';
            $('td', that).eq(3).text(price_sell);
            $('td', that).eq(5).text(price_buy);
            $('td', that).eq(7).text(stuck);
        }
    });
    e.preventDefault();
});

/* Закрытие окон, очистка данных в таблице */
$("#AddModal").on('hidden.bs.modal', function() {
    $("table#AddItemTable tbody").empty();
    $("table#AddItemTable").hide();
    if(isSave) location.reload();
    isClear = false;
});

$('#ImportFileModal').on('hidden.bs.modal', function() {
    $("table#ImportItemFile tbody").empty();
    $("table#ImportItemFile").hide();
    $("#ErrorType").remove();
    if(isSave) location.reload();
    isClear = false;
});

$('button#removeButtons').click(function() {
    that = $(this).parents('tr');
    var id_item = $('td', that).eq(1).text();
    $.get("/shop/removeitem", { id_shop: id_shop, id_item: id_item }).done(function() {
        $(that).hide('slow', function(){
            $(that).remove();
        });
    });
});