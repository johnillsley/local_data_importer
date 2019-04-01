define(['jquery', 'core/ajax', 'core/config', 'local_data_importer/fetch_api_definition', 'core/templates'], function ($, ajax, config, fetch_api_definition, templates) {
    var getSubPluginParams = function (component) {
        if (component == 0) {
            return;
        }
        $.ajax({
            type: 'POST',
            dataType: 'json',
            data: {'componentname': component},
            url: config.wwwroot + '/local/data_importer/ajax.php'
        }).done(function (params) {
        })
    };
    return {
        init: function () {
            $('#id_componentlist').change(function () {

                getSubPluginParams(this.value);
                var log_entries = ['hittesh'];
                templates.render('local_data_importer/select_connector',
                    {'connectoritems': log_entries})
                    .then(function (html) {
                        yuiDialogue.set('bodyContent', html);
                    }).fail(function (ex) {
                        yuiDialogue.set('bodyContent', '');
                    });
            });
        }
    };
});