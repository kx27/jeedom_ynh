
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

$(".li_eqLogic").on('click', function () {
    $('.eqLogic').hide();
    if ('function' == typeof (prePrintEqLogic)) {
        prePrintEqLogic();
    }
    if (isset($(this).attr('data-eqLogic_type')) && isset($('.' + $(this).attr('data-eqLogic_type')))) {
        $('.' + $(this).attr('data-eqLogic_type')).show();
    } else {
        $('.eqLogic').show();
    }
    $('.li_eqLogic').removeClass('active');
    $(this).addClass('active');
    $.showLoading();
    jeedom.eqLogic.print({
        type: isset($(this).attr('data-eqLogic_type')) ? $(this).attr('data-eqLogic_type') : eqType,
        id: $(this).attr('data-eqLogic_id'),
        error: function (error) {
            $('#div_alert').showAlert({message: error.message, level: 'danger'});
        },
        success: function (data) {
            $('body .eqLogicAttr').value('');
            $('body').setValues(data, '.eqLogicAttr');
            if ('function' == typeof (printEqLogic)) {
                printEqLogic(data);
            }
            if ('function' == typeof (addCmdToTable)) {
                $('.cmd').remove();
                for (var i in data.cmd) {
                    addCmdToTable(data.cmd[i]);
                }
            }
            initTooltips();
            modifyWithoutSave = false;
            $('body').delegate('.cmd .cmdAttr[data-l1key=type]', 'change', function () {
                jeedom.cmd.changeType($(this).closest('.cmd'));
            });

            $('body').delegate('.cmd .cmdAttr[data-l1key=subType]', 'change', function () {
                jeedom.cmd.changeSubType($(this).closest('.cmd'));
            });
            initExpertMode();
        }
    });
    return false;
});

if (getUrlVars('saveSuccessFull') == 1) {
    $('#div_alert').showAlert({message: '{{Sauvegarde effectuée avec succès}}', level: 'success'});
}

if (getUrlVars('removeSuccessFull') == 1) {
    $('#div_alert').showAlert({message: '{{Suppression effectuée avec succès}}', level: 'success'});
}

/**************************EqLogic*********************************************/
$('.eqLogicAction[data-action=copy]').on('click', function () {
    if ($('.li_eqLogic.active').attr('data-eqLogic_id') != undefined) {
        bootbox.prompt("{{Nom la copie de l'équipement ?}}", function (result) {
            if (result !== null) {
                jeedom.eqLogic.copy({
                    id: $('.li_eqLogic.active').attr('data-eqLogic_id'),
                    name: result,
                    error: function (error) {
                        $('#div_alert').showAlert({message: error.message, level: 'danger'});
                    },
                    success: function (data) {
                        modifyWithoutSave = false;
                        if ($('#ul_eqLogic .li_eqLogic[data-eqLogic_id=' + data.id + ']').length != 0) {
                            var name = $('#ul_eqLogic .li_eqLogic[data-eqLogic_id=' + data.id + '] a').text();
                            $('#ul_eqLogic .li_eqLogic[data-eqLogic_id=' + data.id + '] a').empty().append(name.substr(0, name.lastIndexOf("[")) + '[' + data.name + ']');
                            $('#ul_eqLogic .li_eqLogic[data-eqLogic_id=' + data.id + ']').click();
                        } else {
                            var vars = getUrlVars();
                            var url = 'index.php?';
                            for (var i in vars) {
                                if (i != 'id' && i != 'saveSuccessFull' && i != 'removeSuccessFull') {
                                    url += i + '=' + vars[i].replace('#', '') + '&';
                                }
                            }
                            url += 'id=' + data.id + '&saveSuccessFull=1';
                            window.location.href = url;
                        }
                    }
                });
                return false;
            }
        });
    }
});

$('.eqLogicAction[data-action=save]').on('click', function () {
    var eqLogics = [];
    $('.eqLogic').each(function () {
        if ($(this).is(':visible')) {
            var eqLogic = $(this).getValues('.eqLogicAttr');
            eqLogic = eqLogic[0];
            eqLogic.cmd = $(this).find('.cmd').getValues('.cmdAttr');
            if ('function' == typeof (saveEqLogic)) {
                eqLogic = saveEqLogic(eqLogic);
            }
            eqLogics.push(eqLogic);
        }
    });
    jeedom.eqLogic.save({
        type: isset($(this).attr('data-eqLogic_type')) ? $(this).attr('data-eqLogic_type') : eqType,
        id: $(this).attr('data-eqLogic_id'),
        eqLogics: eqLogics,
        error: function (error) {
            $('#div_alert').showAlert({message: error.message, level: 'danger'});
        },
        success: function (data) {
            modifyWithoutSave = false;
            if ($('#ul_eqLogic .li_eqLogic[data-eqLogic_id=' + data.id + ']').length != 0) {
                var name = $('#ul_eqLogic .li_eqLogic[data-eqLogic_id=' + data.id + '] a').text();
                $('#ul_eqLogic .li_eqLogic[data-eqLogic_id=' + data.id + '] a').empty().append(name.substr(0, name.lastIndexOf("[")) + '[' + data.name + ']');
                $('#ul_eqLogic .li_eqLogic[data-eqLogic_id=' + data.id + ']').click();
                $('#div_alert').showAlert({message: '{{Sauvegarde effectuée avec succès}}', level: 'success'});
            } else {
                var vars = getUrlVars();
                var url = 'index.php?';
                for (var i in vars) {
                    if (i != 'id' && i != 'saveSuccessFull' && i != 'removeSuccessFull') {
                        url += i + '=' + vars[i].replace('#', '') + '&';
                    }
                }
                url += 'id=' + data.id + '&saveSuccessFull=1';
                window.location.href = url;
            }
        }
    });
    return false;
});

$('.eqLogicAction[data-action=remove]').on('click', function () {
    if ($('.li_eqLogic.active').attr('data-eqLogic_id') != undefined) {
        bootbox.confirm('{{Etes-vous sûr de vouloir supprimer l\'équipement}} ' + eqType + ' <b>' + $('.li_eqLogic.active a:first').text() + '</b> ?', function (result) {
            if (result) {
                jeedom.eqLogic.remove({
                    type: isset($(this).attr('data-eqLogic_type')) ? $(this).attr('data-eqLogic_type') : eqType,
                    id: $('.li_eqLogic.active').attr('data-eqLogic_id'),
                    error: function (error) {
                        $('#div_alert').showAlert({message: error.message, level: 'danger'});
                    },
                    success: function () {
                        var vars = getUrlVars();
                        var url = 'index.php?';
                        for (var i in vars) {
                            if (i != 'id' && i != 'removeSuccessFull' && i != 'saveSuccessFull') {
                                url += i + '=' + vars[i].replace('#', '') + '&';
                            }
                        }
                        modifyWithoutSave = false;
                        url += 'removeSuccessFull=1';
                        window.location.href = url;
                    }
                });
            }
        });
    } else {
        $('#div_alert').showAlert({message: '{{Veuillez d\'abord sélectionner un}} ' + eqType, level: 'danger'});
    }
});


$('.eqLogicAction[data-action=add]').on('click', function () {
    bootbox.prompt("{{Nom de l'équipement ?}}", function (result) {
        if (result !== null) {
            jeedom.eqLogic.save({
                type: eqType,
                eqLogics: [{name: result}],
                error: function (error) {
                    $('#div_alert').showAlert({message: error.message, level: 'danger'});
                },
                success: function (_data) {
                    var vars = getUrlVars();
                    var url = 'index.php?';
                    for (var i in vars) {
                        if (i != 'id' && i != 'saveSuccessFull' && i != 'removeSuccessFull') {
                            url += i + '=' + vars[i].replace('#', '') + '&';
                        }
                    }
                    modifyWithoutSave = false;
                    url += 'id=' + _data.id + '&saveSuccessFull=1';
                    window.location.href = url;
                }
            });
        }
    });
});

/**************************CMD*********************************************/
$('.cmdAction[data-action=add]').on('click', function () {
    addCmdToTable();
    $('.cmd:last .cmdAttr[data-l1key=type]').trigger('change');
});

$('body').delegate('.cmd .cmdAction[data-l1key=chooseIcon]', 'click', function () {
    var cmd = $(this).closest('.cmd');
    chooseIcon(function (_icon) {
        cmd.find('.cmdAttr[data-l1key=display][data-l2key=icon]').empty().append(_icon);
    });
});

$('body').delegate('.cmd .cmdAttr[data-l1key=display][data-l2key=icon]', 'click', function () {
    $(this).empty();
});

$('body').delegate('.cmd .cmdAttr[data-l1key=eventOnly]', 'change', function () {
    if ($(this).value() == 1) {
        $(this).closest('.cmd').find('.cmdAttr[data-l1key=cache][data-l2key=lifetime]').hide();
        $(this).closest('.cmd').find('.cmdAttr[data-l1key=cache][data-l2key=lifetime]').addClass('hide');
    } else {
        $(this).closest('.cmd').find('.cmdAttr[data-l1key=cache][data-l2key=lifetime]').show();
        $(this).closest('.cmd').find('.cmdAttr[data-l1key=cache][data-l2key=lifetime]').removeClass('hide');
    }
});

$('body').delegate('.cmd .cmdAction[data-action=remove]', 'click', function () {
    $(this).closest('tr').remove();
});

$('body').delegate('.cmd .cmdAction[data-action=test]', 'click', function () {
    $.hideAlert();
    if ($('.eqLogicAttr[data-l1key=isEnable]').is(':checked')) {
        var id = $(this).closest('.cmd').attr('data-cmd_id');
        jeedom.cmd.test({id: id});
    } else {
        $('#div_alert').showAlert({message: '{{Veuillez activer l\'équipement avant de tester une de ses commandes}}', level: 'warning'});
    }
});

if (is_numeric(getUrlVars('id'))) {
    if ($('#ul_eqLogic .li_eqLogic[data-eqLogic_id=' + getUrlVars('id') + ']').length != 0) {
        $('#ul_eqLogic .li_eqLogic[data-eqLogic_id=' + getUrlVars('id') + ']').click();
    } else {
        $('#ul_eqLogic .li_eqLogic:first').click();
    }
} else {
    $('#ul_eqLogic .li_eqLogic:first').click();
}

$('body').delegate('.cmdAttr', 'change', function () {
    modifyWithoutSave = true;
});

$('body').delegate('.eqLogicAttr', 'change', function () {
    modifyWithoutSave = true;
});
