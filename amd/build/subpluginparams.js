define(['jquery', 'core/ajax', 'core/config','local_data_importer/fetch_api_definition'], function ($, ajax, config,fetch_api_definition) {

        var getSubPluginParams = function(component){
            if(component === 0){
                return;
            }
            console.log("doing ajax call");
            $.ajax({
                type: 'POST',
                dataType: 'json',
                data: {'componentname': component },
                url: config.wwwroot + '/local/data_importer/ajax.php'
            }).done(function (params) {
                console.log(params);
            })
        };
    return {
        init: function () {
            $('#id_componentlist').change(function(){

                getSubPluginParams(this.value)
            });
        }
    };
});