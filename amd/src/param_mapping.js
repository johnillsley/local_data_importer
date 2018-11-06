define(['jquery', 'core/ajax', 'core/config','core/templates'],
    function ($, ajax,config,templates){
    var importer_form = function(){
        var mform = $('#mform2');
        return mform;
    };

    /*
    Based on connector ID, get connector Path items from Swagger HUB API
     */
    var getPathItems = function(connectorid){
        var pathitemshtml = null;
        if(connectorid == 0){
            return;
        }
        console.log("doing ajax call");
        $.ajax({
            type: 'POST',
            dataType: 'json',
            data: {'connectorid': connectorid,'action':'fetchpathitems' },
            url: config.wwwroot + '/local/data_importer/ajax.php'
        }).done(function (pathitems) {
            // Start with showing connector drop-down.
            console.log(pathitems);
            templates.render('local_data_importer/select_path_item',
                {'pathitems': pathitems})
                .then(function (html) {
                    // show the options
                    pathitemshtml = html;
                    var form = importer_form();
                    form.append(html);
                    //console.log(html);
                 }).fail(function (ex) {
                console.log(ex);
            });
        });
    };
        return {
            init: function () {
                // Add more button is clicked.

                $('#id_connectorlist').change(function(){
                    getPathItems(this.value);
                });

            }
        };
});