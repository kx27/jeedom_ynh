
/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

printUsers();
$("#bt_addUser").on('click', function(event) {
    $.hideAlert();
    $('#in_newUserLogin').value('');
    $('#in_newUserMdp').value('');
    $('#md_newUser').modal('show');
});

$("#bt_newUserSave").on('click', function(event) {
    $.hideAlert();
    var user = [{login: $('#in_newUserLogin').value(), password: $('#in_newUserMdp').value()}];
    jeedom.user.save({
        users: user,
        error: function(error) {
            $('#div_alert').showAlert({message: error.message, level: 'danger'});
        },
        success: function() {
            printUsers();
            $('#div_alert').showAlert({message: '{{Sauvegarde effectuée}}', level: 'success'});
            modifyWithoutSave = false;
            $('#md_newUser').modal('hide');
        }
    });

});

$("#bt_saveUser").on('click', function(event) {
    jeedom.user.save({
        users: $('#table_user tbody tr').getValues('.userAttr'),
        error: function(error) {
            $('#div_alert').showAlert({message: error.message, level: 'danger'});
        },
        success: function() {
            printUsers();
            $('#div_alert').showAlert({message: '{{Sauvegarde effectuée}}', level: 'success'});
            modifyWithoutSave = false;
        }
    });
});

$("#table_user").delegate(".del_user", 'click', function(event) {
    $.hideAlert();
    var user = {id: $(this).closest('tr').find('.userAttr[data-l1key=id]').value()};
    bootbox.confirm('{{Etes-vous sûr de vouloir supprimer cet utilisateur ?}}', function(result) {
        if (result) {
            jeedom.user.remove({
                id: user.id,
                error: function(error) {
                    $('#div_alert').showAlert({message: error.message, level: 'danger'});
                },
                success: function() {
                    printUsers();
                    $('#div_alert').showAlert({message: '{{L\'utilisateur a bien été supprimé}}', level: 'success'});
                }
            });
        }
    });
});

$("#table_user").delegate(".change_mdp_user", 'click', function(event) {
    $.hideAlert();
    var user = {id: $(this).closest('tr').find('.userAttr[data-l1key=id]').value(), login: $(this).closest('tr').find('.userAttr[data-l1key=login]').value()};
    bootbox.prompt("{{Quel est le nouveau mot de passe ?}}", function(result) {
        if (result !== null) {
            user.password = result;
            jeedom.user.save({
                users: [user],
                error: function(error) {
                    $('#div_alert').showAlert({message: error.message, level: 'danger'});
                },
                success: function() {
                    printUsers();
                    $('#div_alert').showAlert({message: '{{Sauvegarde effectuée}}', level: 'success'});
                    modifyWithoutSave = false;
                }
            });
        }
    });
});

$('body').delegate('.userAttr', 'change', function() {
    modifyWithoutSave = true;
});

$('body').delegate('.configKey', 'change', function() {
    modifyWithoutSave = true;
});

function printUsers() {
    jeedom.user.all({
        error: function(error) {
            $('#div_alert').showAlert({message: error.message, level: 'danger'});
        },
        success: function(data) {
            $('#table_user tbody').empty();
            for (var i in data) {
                var ligne = '<tr><td class="login">';
                ligne += '<span class="userAttr" data-l1key="id" style="display : none;"/>';
                ligne += '<span class="userAttr" data-l1key="login" />';
                ligne += '</td>';
                ligne += '<td>';
                if (ldapEnable != '1') {
                    ligne += '<a class="btn btn-xs btn-danger pull-right del_user"><i class="fa fa-trash-o"></i> {{Supprimer}}</a>';
                    ligne += '<a class="btn btn-xs btn-warning pull-right change_mdp_user"><i class="fa fa-pencil"></i> {{Changer le mot de passe}}</a>';
                }
                ligne += '</td>';
                ligne += '<td>';
                ligne += '<input type="checkbox" class="userAttr" data-l1key="enable" />';
                ligne += '</td>';
                ligne += '<td>';
                ligne += '<input type="checkbox" class="userAttr" data-l1key="rights" data-l2key="admin"/> Admin';
                ligne += '</td>';
                ligne += '</tr>';
                $('#table_user tbody').append(ligne);
                $('#table_user tbody tr:last').setValues(data[i], '.userAttr');
                modifyWithoutSave = false;
            }
        }
    });
}