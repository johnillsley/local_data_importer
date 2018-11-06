define(['jquery', 'core/ajax', 'core/config'], function ($, ajax, config) {
    var URL = config.wwwroot + '/local/data_importer/ajax.php';
    return {
        init: function () {
            var URL = config.wwwroot + '/local/data_importer/ajax.php';
            var server_control = $('#id_apiserver');
            // When 'fetch' is pressed
            $('#id_fetchapidef').on("click", function () {
                // get the api url from text control

                var apikey = $('#id_openapikey').val();
                var apiurl = $('#id_openapidefinitionurl').val();
                if (apiurl === '' || apikey === '') {
                    alert("Cannot be blank");
                    return false;
                }
                console.log(apikey);
                console.log(apiurl);
                $.ajax({
                    type: 'GET',
                    dataType: 'json',
                    data: {'openapikey': apikey, 'openapidefinitionurl': apiurl},
                    url: URL
                }).done(function (servers) {
                    console.log(servers);
                    // add it to the disabled control
                    $.each(servers, function (key, value) {
                        console.log(key);
                        console.log(value);
                        var option = $("<option />");
                        option.html(value);
                        option.val(value);
                        server_control.append(option);
                    });

                    //enable the control
                    server_control.prop('disabled', false);
                });
            });
        }
    };
});