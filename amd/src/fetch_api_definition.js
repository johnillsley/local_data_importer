define(['jquery', 'core/ajax', 'core/config'], function ($, ajax, config) {
    var URL = config.wwwroot + '/local/data_importer/ajax.php';
    return {
        init: function () {
            var URL = config.wwwroot + '/local/data_importer/ajax.php';
            var server_control = $('#id_apiserver');
            // When 'fetch' is pressed.
            $('#id_fetchapidef').on("click", function () {
                // Get the api url from text control.

                var apikey = $('#id_openapikey').val();
                var apiurl = $('#id_openapidefinitionurl').val();
                if (apiurl === '' || apikey === '') {
                    alert("Cannot be blank");
                    return false;
                }
                $.ajax({
                    type: 'GET',
                    dataType: 'json',
                    data: {'openapikey': apikey, 'openapidefinitionurl': apiurl},
                    url: URL
                }).done(function (servers) {
                    // Add it to the disabled control.
                    $.each(servers, function (key, value) {
                        var option = $("<option />");
                        option.empty();
                        option.html(value);
                        option.val(value);
                        server_control.append(option);
                    });

                    // Enable the control.
                    server_control.prop('disabled', false);
                });
            });
        }
    };
});