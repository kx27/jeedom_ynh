function initMessage() {
    var rightPanel = '<ul data-role="listview" data-theme="b" data-dividertheme="b" class="ui-icon-alt">';
    rightPanel += '<li data-role="list-divider">{{Action}}</li>';
    rightPanel += '<li><a id="bt_clearMessage" href="#"><i class="fa fa-trash-o"></i> {{Vider}}</a></li>';
    rightPanel += '</ul>';
    rightPanel += '<ul data-role="listview" data-theme="b" data-dividertheme="b" class="ui-icon-alt">';
    rightPanel += '<li data-role="list-divider">{{Logfile}}</li>';
    rightPanel += '<li><a class="messageFilter" data-plugin="">{{Tout}}</a></li>';
    jeedom.plugin.all({
        error: function (error) {
            $('#div_alert').showAlert({message: error.message, level: 'danger'});
        },
        success: function (plugins) {
            for (var i in plugins) {
                rightPanel += '<li><a class="messageFilter" data-plugin="' + plugins[i].name + '">' + plugins[i].name + '</a></li>';
            }
            rightPanel += '</ul>';
            panel(rightPanel);
        }
    });

    getAllMessage();

    $("#bt_clearMessage").on('click', function (event) {
        jeedom.message.clear({
            plugin: $('#sel_plugin').value(),
            error: function (error) {
                $('#div_alert').showAlert({message: error.message, level: 'danger'});
            },
            success: getAllMessage
        });
    });

    $(".messageFilter").on('click', function (event) {
        getAllMessage($(this).attr('data-plugin'));
    });

    $("#table_message").delegate(".removeMessage", 'click', function (event) {
        var tr = $(this).closest('tr');
        jeedom.message.remove({
            id: tr.attr('data-message_id'),
            error: function (error) {
                $('#div_alert').showAlert({message: error.message, level: 'danger'});
            },
            success: function () {
                tr.remove();
            }
        });
    });
}

function getAllMessage(_plugin) {
    jeedom.message.all({
        plugin: _plugin || '',
        error: function (error) {
            $('#div_alert').showAlert({message: error.message, level: 'danger'});
        },
        success: function (messages) {
            var tbody = '';
            for (var i in  messages) {
                tbody += '<tr data-message_id="' + messages[i].id + '">';
                tbody += '<td><center><i class="fa fa-trash-o cursor removeMessage"></i></center></td>';
                tbody += '<td class="datetime">' + messages[i].date + '</td>';
                tbody += '<td class="plugin">' + messages[i].plugin + '</td>';
                tbody += '<td class="message">' + messages[i].message + '</td>';
                tbody += '</tr>';
            }
            $('#table_message tbody').empty().append(tbody);
            $("#table_message").table("rebuild");
        }
    });
}