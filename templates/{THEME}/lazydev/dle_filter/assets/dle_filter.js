let getNavigation = function(doc) {
    let currentNode;
    let navigation = 'none';

    if (typeof doc === 'object') {
        let nodeIterator = document.createNodeIterator(
            doc,
            NodeFilter.SHOW_COMMENT
        );

        while (currentNode = nodeIterator.nextNode()) {
            if (currentNode.nodeType === 8 && currentNode.nodeValue === 'ENGINE_NAVIGATION' && currentNode.nextSibling.nodeValue !== 'ENGINE_NAVIGATION') {
                navigation = currentNode.nextSibling;
                break;
            }
        }
    }

    return navigation;
};

let navigation = getNavigation(document.body);
let filterNavigation = 'none';

$(function() {
    // Поиск по ENTER
    $('[data-dlefilter*=dle-filter] input, [data-dlefilter*=dle-filter] textarea').keypress(function(e) {
        if (e.which == 13) {
            e.preventDefault();
            filterAjax($(this).closest('form'), true);
        }
    });

    // Получаем ID контента
    let getId = function(elemTag) {
        dleFilter.id = (elemTag !== 'FORM' ? $(elemTag).closest('form[data-dlefilter]').data('dlefilter-id') : $(elemTag).data('dlefilter-id')) || 'dle-content';

        if (!dleFilter.original[dleFilter.id] && dleFilter.id != 'dle-content') {
            dleFilter.original[dleFilter.id] = $('#' + dleFilter.id).html();
        }
    }

    // Задаем данные на странице фильтра.
    let setFilterParam = function() {
        let filterParam = decodeURIComponent(location.href).split('/' + dleFilter.filterUrl + '/');
        if (!filterParam[1]) {
            return;
        }
        if (filterParam[1].slice(-1) === '/') {
            filterParam[1] = filterParam[1].slice(0, -1);
        }

        // Разбивка данных
        filterParam = filterParam[1].split('/');
        let arrayParam = [];
        for (let i = 0; i < filterParam.length; i++) {
            arrayParam[i] = filterParam[i].split('=');
            if (arrayParam[i][1]) {
                arrayParam[i][0] = arrayParam[i][0].replace(/\+/g, ' ');
                arrayParam[i][1] = arrayParam[i][1].replace(/\+/g, ' ');
            }
        }

        // Задаем для input text и textarea, так же для ionRange.Slider
        $('[data-dlefilter*=dle-filter] input[type="text"], [data-dlefilter*=dle-filter] textarea').each(function () {
            let nameElem = $(this).attr('name');
            if (nameElem !== undefined && nameElem.length > 0) {
                for (let i = 0; i < arrayParam.length; i++) {
                    if (nameElem === arrayParam[i][0]) {
                        if (nameElem.indexOf('r.') + 1) {
                            let slider = $(this).data('ionRangeSlider');

                            let sliderData = arrayParam[i][1].split(';');
                            if (slider.options.type == 'single') {
                                slider.update({
                                    from: sliderData[0]
                                });
                            } else if (slider.options.type == 'double') {
                                slider.update({
                                    from: sliderData[0],
                                    to: sliderData[1]
                                });
                            }
                        } else {
                            $(this).val(arrayParam[i][1]);
                        }
                    }
                }
            }
        });

        // Select
        $('[data-dlefilter*=dle-filter] select').each(function (index) {
            let nameElem = $(this).attr('name');
            if (nameElem !== undefined && nameElem.length > 0) {
                for (let i = 0; i < arrayParam.length; i++) {
                    if (nameElem === arrayParam[i][0]) {
                        let selectData = [];
                        if (nameElem.indexOf('c.') + 1) {
                            selectData[0] = arrayParam[i][1];
                        } else {
                            selectData = arrayParam[i][1].split(',');
                        }

                        $(this).find('option').each(function (s, n) {
                            if ($.inArray(n.value, selectData) >= 0) {
                                $(this).attr('selected', true);
                            }
                        });
                    }
                }

                if (dleFilter.jsSelect == 1) {
                    let getTail = $('.tail-select');
                    if (getTail.length > 0) {
                        tail.select('[data-dlefilter*=dle-filter] select[data-select-id="' + index + '"]').reload();
                    }
                }
            }
        });

        if (dleFilter.jsSelect == 2) {
            let getChosen = $('.chosen-results');
            if (getChosen.length > 0) {
                $('[data-dlefilter*=dle-filter] select').trigger('chosen:updated');
            }
        }

        if (dleFilter.jsSelect == 3) {
            $('[data-dlefilter*=dle-filter] select').niceSelect('update');
        }

        // Radio / Checkbox
        $('[data-dlefilter*=dle-filter] input[type="radio"], [data-dlefilter*=dle-filter] input[type="checkbox"]').each(function () {
            let nameElem = $(this).attr('name');
            if (nameElem !== undefined && nameElem.length > 0) {
                for (let i = 0; i < arrayParam.length; i++) {
                    if (nameElem === arrayParam[i][0]) {
                        let selectData = arrayParam[i][1].split(',');

                        $(this).each(function (s, n) {
                            if ($.inArray(n.value, selectData) >= 0) {
                                $(this).attr('checked', 'checked');
                            }
                        });
                    }
                }
            }
        });
    };

    // Очистка фильтра
    let filterClear = function() {
        dleFilter.reset = true;

        let formFilter = $(this).closest('form');
        let formId = $(formFilter).data('dlefilter');
        getId(this);

        if (dleFilter.ajaxUrl == 0) {
            document.title = dleFilter.title;
            history.pushState(null, dleFilter.title, dleFilter.path);
        }

        if (filterNavigation !== 'none' && dleFilter.id == 'dle-content' && dleFilter.navApart == 1) {
            $(getNavigation(document.body)).replaceWith(navigation);
        }

        if (dleFilter.path.indexOf('/' + dleFilter.filterUrl + '/') + 1) {
            $('#dle-content').html(dleFilter.content);
        } else {
            if (dleFilter.id == 'dle-content') {
                $('#dle-content').html(dleFilter.content);
            } else {
                $('#' + dleFilter.id).html(dleFilter.original[dleFilter.id]);
            }
        }

        if (dleFilter.lazy == 1 && jQuery().lazyLoadXT) {
            $('[data-src]').lazyLoadXT();
        }

        if ($('#dle-speedbar').length > 0) {
            $('#dle-speedbar').html(dleFilter.speedbar);
        }

        $(formFilter).find('input[type="text"]').each(function() {
            let nameElem = $(this).prop('name');
            if (nameElem.length > 0) {
                if (nameElem.indexOf('r.') + 1) {
                    let slider = $(this).data('ionRangeSlider');
                    if (slider.options.type == 'single') {
                        slider.update({
                            from: slider.options.min
                        });
                    } else if (slider.options.type == 'double') {
                        slider.update({
                            from: slider.options.min,
                            to: slider.options.max
                        });
                    }
                } else {
                    $(this).val('');
                }
                if ($(this).data('dlefilter-show') !== undefined) {
                    showAndHideFilter(this);
                }
            }
        });

        $(formFilter).find('select').each(function(index) {
            let nameElem = $(this).prop('name');
            if (nameElem.length > 0) {
                $(this).children('option').each(function() {
                    $(this).prop('selected', false);
                });

                if (dleFilter.jsSelect == 1) {
                    let getTail = $('.tail-select');
                    if (getTail.length > 0) {
                        tail.select('[data-dlefilter*="' + formId + '"] select[data-select-id="' + index + '"]').reload();
                    }
                }

                if ($(this).data('dlefilter-show') !== undefined) {
                    showAndHideFilter(this);
                }
            }
        });

        if (dleFilter.jsSelect == 2) {
            let getChosen = $('.chosen-results');
            if (getChosen.length > 0) {
                $('[data-dlefilter*="' + formId + '"] select').trigger('chosen:updated');
            }
        }

        if (dleFilter.jsSelect == 3) {
            $('[data-dlefilter*="' + formId + '"] select').niceSelect('update');
        }

        $(formFilter).find('input[type="radio"], input[type="checkbox"]').each(function() {
            let nameElem = $(this).prop('name');
            if (nameElem.length > 0) {
                $(this).prop('checked', false);
                if ($(this).data('dlefilter-show') !== undefined) {
                    showAndHideFilter(this);
                }
            }
        });

        dleFilter.reset = false;
    };

    // Данные фильтра
    let filterWork = function(data) {
        data = $.parseJSON(data);

        if (data.content == 'redirect') {
            dleFilter.ajax = 1;
        }

        if (dleFilter.ajax == 1) {
            window.location.href = data.url;
        } else {
            if (data.clean == dleFilter.defaultUrl) {
                filterClear();
            }  else {
                if ($('#dle-speedbar').length > 0) {
                    $('#dle-speedbar').html($('#dle-speedbar', data.speedbar).html());
                }

                let idContent = '';
                if (dleFilter.path.indexOf('/' + dleFilter.filterUrl + '/') + 1) {
                    $('#dle-content').html(data.content);
                    idContent = '#dle-content';
                } else {
                    $('#' + dleFilter.id).html(data.content);
                    idContent = '#' + dleFilter.id;
                }

                if (data.player && ($(idContent + " .dleplyrplayer").length || $(idContent + " .dleplyrplayer").length || $(idContent + " .dlevideoplayer").length)) {
                    if (data.player == 'plyr') {
                        let playersV = Array.from(document.querySelectorAll('.dleplyrplayer:not(.dlepl)')).map(player => new DLEPlayer(player));
                        playersV.forEach(function(instance, index) {
                            instance.on('play',function() {
                                playersV.forEach(function(instance1, index1) {
                                    if (instance != instance1) {
                                        instance1.pause();
                                    }
                                });
                            });
                        });
                    } else {
                        $('.dleaudioplayer:not(.alreadyLoaded)').addClass('alreadyLoaded').cleanaudioplayer();
                        $('.dlevideoplayer:not(.alreadyLoaded)').addClass('alreadyLoaded').cleanvideoplayer();
                    }
                }

                if (dleFilterId === 1) {
                    $('pre').removeAttr('class');
                    $('pre').each(function() {
                        if (!$(this).find('code').length) {
                            $(this).wrapInner('<code>');
                        }
                    });

                    hljs.highlightAll();
                } else if (dleFilterId === 0) {
                    $('pre code:not([class])').each(function(i, e) {hljs.highlightBlock(e, null)});
                }

                if (navigation !== 'none' && data.nav && dleFilter.id == 'dle-content' && dleFilter.navApart == 1) {
                    filterNavigation = data.nav;
                    navigation = getNavigation(document.body);
                    $(navigation).replaceWith(data.nav);
                } else if (!data.nav && dleFilter.navApart == 1) {
                    navigation = getNavigation(document.body);
                    navigation.remove();
                }

                if (dleFilter.lazy == 1 && jQuery().lazyLoadXT) {
                    $('[data-src]').lazyLoadXT();
                }

                if (dleFilter.ajaxUrl == 0) {
                    history.pushState(null, data.title, data.clean);
                    document.title = data.title;
                }
            }
        }
    };

    // Собираем данные
    let filterAjax = function(elem, enter) {
        if (dleFilter.reset) {
            return;
        }

        $('[data-dlefilter*=dle-filter] input[name*="r."]').each(function () {
            let nameElem = $(this).attr('name');
            if (nameElem !== undefined && nameElem.length > 0) {
                let slider = $(this).data('ionRangeSlider');
                let sliderDefault = 0;

                if (slider.options.type == 'single') {
                    sliderDefault = slider.options.min;
                } else if (slider.options.type == 'double') {
                    sliderDefault = slider.options.min + ';' + slider.options.max;
                }

                if (sliderDefault == $(this).val()) {
                    $(this).val('');
                }
            }
        });

        enter = enter || false;
        let data, elemTag, elemType = '';

        if (enter === true) {
            data = $(elem).serialize();
        } else {
            elemTag = elem.target.tagName.toUpperCase();

            if (elem.target.type !== undefined) {
                elemType = elem.target.type.toUpperCase();
            }

            if (elemTag === 'TEXTAREA' || elemTag === 'INPUT' && (elemType === 'TEXT' || elemType === 'RESET')) {
                return;
            }

            data = elemTag !== 'FORM' ? $(this).closest('form').serialize() : $(this).serialize();
        }

        getId(elemTag);

        $.ajax({
            beforeSend: function() {
                if (dleFilter.hideLoading == 0) {
                    ShowLoading('');
                }
            },
            url: dle_root + 'engine/lazydev/dle_filter/ajax.php',
            type: 'POST',
            data: {
                data: data,
                url: dleFilter.path,
                dle_hash: dle_login_hash,
                dleFilterJSData
            },
            success: function(output) {
                if (output.error) {
                    DLEalert(output.text, dle_info);
                } else {
                    filterWork(output);
                }
            },
            error: function (output) {
                filterWork(output.responseText);
            }
        }).always(function() {
            if (dleFilter.hideLoading == 0) {
                HideLoading('');
            }
        });
    };

    // Сохраняем данные спидбара
    if ($('#dle-speedbar').length > 0) {
        dleFilter.speedbar = $('#dle-speedbar').html();
    }

    // Сохраняем данные контента
    if ($('#dle-content').length > 0) {
        dleFilter.content = $('#dle-content').html();
    }

    // Задаем callback для click / change в форме фильтра
    $('body').on('click', '[data-dlefilter=submit]', filterAjax);

    if (dleFilter.button == 0) {
        $('body').on('change', '[data-dlefilter*=dle-filter]', filterAjax);
    }

    // Callback для очистки фильтра
    $('body').on('click', '[data-dlefilter=reset]', filterClear);

    // Работа с датой в слайдере
    let dateToTS = function(date) {
        return date.valueOf();
    };

    let validateDate = function(date) {
        date = date.toString().split(',');
        if (Array.isArray(date) && date.length === 3) {
            date[0] = parseInt(date[0]);
            date[1] = parseInt(date[1]);
            date[2] = parseInt(date[2]);
            if (date[0] > 0 && (date[1] > 0 && date[1] <= 12) && (date[2] > 0 && date[2] <= 31)) {
                return date;
            }
        }

        return false;
    };

    // Метки в слайдере
    function convertToPercent(num, min, max) {
        return (num - min) / (max - min) * 100;
    }

    function addMark($slider, config, mark) {
        let html = '';
        let left = 0;
        let leftPercent = '';

        for (let i = 0; i < mark.length; i++) {
            let tempMark = mark[i].toString().split('|');

            left = convertToPercent(parseInt(tempMark[0]), config.min, config.max);
            leftPercent = left + '%';
            html += '<span class="sliderShowcaseMark" style="left: ' + leftPercent + '">' + tempMark[1] + '</span>';
        }

        $slider.append(html);
    }

    // Работа со слайдером
    $('[data-dlefilter*=dle-filter]').find('input[type="text"][name*="r."]').each(function() {
        let sliderVars = $(this).data('slider-config');
        let sliderType = $(this).data('slider-type');
        let sliderLang = $(this).data('slider-lang');
        let sliderMark = $(this).data('slider-mark');

        let date = '';

        if (sliderVars !== undefined && sliderVars.length > 0) {
            let sliderConfig = {};

            if (sliderVars.slice(-1) === ';') {
                sliderVars = sliderVars.slice(0, -1);
            }

            sliderVars = sliderVars.split(';');
            for (let i = 0; i < sliderVars.length; i++) {
                let attempt = sliderVars[i].split(':');
                attempt[0] = attempt[0].trim();
                switch (attempt[0]) {
                    case 'АвтоПолзунок':
                    case 'AutoSlider':
                        sliderConfig.slider = true;
                        break;
                    case 'Одиночный слайдер':
                    case 'Single':
                        sliderConfig.type = 'single';
                        break;
                    case 'Двойной слайдер':
                    case 'Double':
                        sliderConfig.type = 'double';
                        break;
                    case 'Минимальное значение':
                    case 'Min':
                        if (attempt[1] !== undefined) {
                            if ($.isNumeric(attempt[1])) {
                                sliderConfig.min = attempt[1];
                            } else if (sliderType === 'date') {
                                date = validateDate(attempt[1]);
                                if (date !== false) {
                                    sliderConfig.min = dateToTS(new Date(date[0], date[1] - 1, date[2]));
                                }
                            }
                        }
                        break;
                    case 'Максимальное значение':
                    case 'Max':
                        if (attempt[1] !== undefined) {
                            if ($.isNumeric(attempt[1])) {
                                sliderConfig.max = attempt[1];
                            } else if (sliderType === 'date') {
                                date = validateDate(attempt[1]);
                                if (date !== false) {
                                    sliderConfig.max = dateToTS(new Date(date[0], date[1] - 1, date[2]));
                                }
                            }
                        }
                        break;
                    case 'Начало слайдера':
                    case 'Start':
                        if (attempt[1] !== undefined) {
                            if ($.isNumeric(attempt[1])) {
                                sliderConfig.from = attempt[1];
                            } else if (sliderType === 'date') {
                                date = validateDate(attempt[1]);
                                if (date !== false) {
                                    sliderConfig.from = dateToTS(new Date(date[0], date[1] - 1, date[2]));
                                }
                            }
                        }
                        break;
                    case 'Конец слайдера':
                    case 'End':
                        if (attempt[1] !== undefined) {
                            if ($.isNumeric(attempt[1])) {
                                sliderConfig.to = attempt[1];
                            } else if (sliderType === 'date') {
                                date = validateDate(attempt[1]);
                                if (date !== false) {
                                    sliderConfig.to = dateToTS(new Date(date[0], date[1] - 1, date[2]));
                                }
                            }
                        }
                        break;
                    case 'Шаг':
                    case 'Step':
                        if (attempt[1] !== undefined && $.isNumeric(attempt[1])) {
                            sliderConfig.step = attempt[1];
                        }
                        break;
                    case 'Шаблон':
                    case 'Tpl':
                        if (attempt[1] !== undefined && attempt[1] !== '') {
                            sliderConfig.skin = attempt[1];
                        }
                        break;
                    case 'Префикс':
                    case 'Prefix':
                        if (attempt[1] !== undefined && attempt[1] !== '') {
                            sliderConfig.prefix = attempt[1];
                        }
                        break;
                    case 'Постфикс':
                    case 'Postfix':
                        if (attempt[1] !== undefined && attempt[1] !== '') {
                            sliderConfig.postfix = attempt[1];
                        }
                        break;
                    case 'Сетка':
                    case 'Grid':
                        sliderConfig.grid = true;
                        break;
                    case 'Красивые числа':
                    case 'Numbers':
                        sliderConfig.prettify_enabled = true;
                        break;
                    case 'Скрыть MinMax':
                    case 'Hide MinMax':
                        sliderConfig.hide_min_max = true;
                        break;
                    case 'Скрыть FromTo':
                    case 'Hide FromTo':
                        sliderConfig.hide_from_to = true;
                        break;
                }
            }

            if (sliderConfig.from === undefined) {
                sliderConfig.from = sliderConfig.min;
            }

            if (sliderConfig.to === undefined) {
                sliderConfig.to = sliderConfig.max;
            }

            if (sliderMark) {
                let mark = sliderMark.toString().split(',');

                sliderConfig.onStart = function (data) {
                    addMark(data.slider, sliderConfig, mark);
                }
            }

            if (sliderType === 'date') {
                sliderConfig.prettify = function(ts) {
                    let d = new Date(ts);

                    return d.toLocaleDateString(sliderLang, {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    });
                };
            }

            if (!sliderConfig.prettify_enabled && sliderType !== 'date') {
                sliderConfig.prettify_enabled = false;
            }

            if (sliderConfig.slider) {
                sliderConfig.onFinish = function (data) {
                    filterAjax($('[name="' + data.input[0].name + '"]').closest('form'), true);
                };
            }

            $(this).ionRangeSlider(sliderConfig);
        }
    });

    // Работа с Tail.Select
    if (dleFilter.jsSelect == 1) {
        $('[data-dlefilter*=dle-filter]').find('select').each(function(index) {
            $(this).attr('data-select-id', index);
            let selectVars = $(this).data('select-config');
            let selectConfig = {};
            if (selectVars !== undefined && selectVars.length > 0) {
                if (selectVars.slice(-1) == ';') {
                    selectVars = selectVars.slice(0, -1);
                }
                selectVars = selectVars.split(';');
                for (let i = 0; i < selectVars.length; i++) {
                    let attempt = selectVars[i].split(':');
                    switch (attempt[0]) {
                        case 'Поиск':
                        case 'Search':
                            selectConfig.search = true;
                            break;
                        case 'Скрыть выбранные':
                        case 'Hide':
                            selectConfig.hideSelected = true;
                            break;
                        case 'Максимум выбрать':
                        case 'Max':
                            if (attempt[1] !== undefined && $.isNumeric(attempt[1])) {
                                selectConfig.multiLimit = attempt[1];
                            }
                            break;
                        case 'Показать количество выбранных':
                        case 'Num':
                            if (!selectConfig.multiContainer) {
                                selectConfig.multiShowCount = true;
                            }
                            break;
                        case 'Вывод выбранных':
                        case 'Show':
                            selectConfig.multiContainer = true;
                            if (selectConfig.multiShowCount) {
                                selectConfig.multiShowCount = false;
                            }
                            break;
                        case 'Подсказка':
                        case 'Prompt':
                            if (attempt[1] !== undefined && attempt[1] !== '') {
                                selectConfig.placeholder = attempt[1];
                            }
                            break;
                        case 'Язык':
                        case 'Lang':
                            if (attempt[1] === undefined || attempt[1].trim() == '') {
                                attempt[1] = 'ru';
                            }

                            selectConfig.locale = attempt[1];
                            break;
                        case 'Сортировать':
                        case 'Sort':
                            if (attempt[1] !== undefined && (attempt[1] === 'ASC' || attempt[1] === 'DESC')) {
                                selectConfig.sortItems = attempt[1];
                            }
                            break;
                    }
                }
            }

            if (selectConfig.locale == undefined) {
                selectConfig.locale = 'ru';
            }

            selectConfig.deselect = true;

            tail.select('[data-dlefilter*=dle-filter] select[data-select-id="' + index + '"]', selectConfig).on('change', function(item, state) {
                let name = item.option.parentElement.tagName.toUpperCase() === 'OPTGROUP' ? item.option.parentElement.parentElement.name : item.option.parentElement.name;

                if (state === 'unselect') {
                    $('[name="' + name + '"]').val('');
                }

                if (dleFilter.button == 0) {
                    $('[name="' + name + '"]').trigger('change');
                }
            });
        });
    }

    // Автоматически задаем chosen
    if (dleFilter.jsSelect == 2) {
        $('[data-dlefilter*=dle-filter] select').chosen();
    }

    // Автоматически задаем niceSelect
    if (dleFilter.jsSelect == 3) {
        $('[data-dlefilter*=dle-filter] select').niceSelect();
    }

    // AJAX навигация
    let ajaxNavigation = function (urlPage, pathPage) {
        if (urlPage !== undefined) {
            $.ajax({
                url: urlPage,
                beforeSend: function() {
                    if (dleFilter.hideLoading == 0) {
                        ShowLoading('');
                    }
                },
                success: function(output) {
                    if ($('#dle-speedbar').length > 0) {
                        $('#dle-speedbar').html($('#dle-speedbar', output).html());
                    }

                    if (navigation !== 'none' && dleFilter.navApart == 1) {
                        let parser = new DOMParser();
                        let el = parser.parseFromString(output, 'text/html');
                        let nav = getNavigation(el);
                        let navNow = getNavigation(document.body);

                        filterNavigation = nav;
                        $(navNow).replaceWith(nav);
                    }

                    if (window.location.href.toString().indexOf(pathPage) + 1) {
                        $('#dle-content').html($('#dle-content', output).html());
                    } else {
                        $('#' + dleFilter.id).html($('#dle-content', output).html());
                    }

                    if (dleFilter.lazy == 1 && jQuery().lazyLoadXT) {
                        $('[data-src]').lazyLoadXT();
                    }

                    if (dleFilter.ajaxUrl == 0) {
                        let titlePage = $(output).filter('title').text();
                        window.history.pushState(titlePage, '', urlPage);
                        if (titlePage != '') {
                            document.title = $(output).filter('title').text();
                        }
                    }

                    if (dleFilter.ajaxAnim == 0) {
                        $('html, body').animate({
                            scrollTop: $('#' + dleFilter.id).offset().top
                        }, 400);
                    }
                },
                error: function(output) {
                    if (dle_group === 1) {
                        console.log(output.responseText, dle_info);
                    }
                }
            }).always(function() {
                if (dleFilter.hideLoading == 0) {
                    HideLoading('');
                }
            });
        }
    }

    // Ajax навигация для готовых страниц
    if (dleFilter.ajaxPage == 1 && urlFilter != 0) {
        $('body').on('click', 'a[href*="' + urlFilter + '"]', function(e) {
            e.preventDefault();
            dleFilter.id = 'dle-content';
            let urlPage = $(this).attr('href');
            ajaxNavigation(urlPage, urlFilter);

            return false;
        });
    }

    // AJAX навигация для страниц фильтра
    if (dleFilter.ajaxNav == 1) {
        $('body').on('click', 'a[href*="/' + dleFilter.filterUrl + '/"]', function(e) {
            e.preventDefault();
            getId(this);
            let urlPage = $(this).attr('href');
            ajaxNavigation(urlPage, '/' + dleFilter.filterUrl + '/');

            return false;
        });
    }

    // Расхождение массива
    Array.prototype.diff = function(a) {
        return this.filter(function(i) {return a.indexOf(i) < 0;});
    };

    let showList = {};
    let hideList = {};
    let whatHide = {};

    // Скрытие и показ блоков
    let showAndHideFilter = function(e) {
        let elemTag = $(e).prop('tagName').toUpperCase();
        let elemType = $(e).prop('type').toUpperCase();

        let showNode = $(e).data('dlefilter-show');
        let objShow = {};
        if (showNode.slice(-1) == ';') {
            showNode = showNode.slice(0, -1);
        }

        showNode = showNode.split(';');
        $.each(showNode, function(index, value) {
            let tempObj = value.split(':');
            if (tempObj[0] && tempObj[1]) {
                objShow[tempObj[0]] = tempObj[1].split(',');
            }
        });

        if (elemTag === 'SELECT') {
            $(e).find('option').each(function(p, elem) {
                $.each(objShow, function(index, value) {
                    if ($(elem).val().toString().length > 0 && $(elem).prop('selected') && $.inArray($(elem).val(), value) >= 0) {
                        if (dleFilter.jsSelect == 1) {
                            showList[index] = '.dle-filter-select-' + index;
                        } else {
                            showList[index] = '[data-dlefilter-hide="' + index + '"]';
                        }
                    } else {
                        if (dleFilter.jsSelect == 1) {
                            hideList[index] = '.dle-filter-select-' + index;
                        } else {
                            hideList[index] = '[data-dlefilter-hide="' + index + '"]';
                        }
                    }
                });
            });
        } else if (elemTag === 'INPUT') {
            $.each(objShow, function(index, value) {
                if (elemType === 'TEXT') {
                    if ($.inArray($(e).val(), value) >= 0) {
                        showList[index] = '[data-dlefilter-hide="' + index + '"]';
                    } else {
                        hideList[index] = '[data-dlefilter-hide="' + index + '"]';
                    }
                } else if (elemType === 'RADIO' || elemType === 'CHECKBOX') {
                    if ($(e).prop('checked') && $.inArray($(e).val(), value) >= 0) {
                        showList[index] = '[data-dlefilter-hide="' + index + '"]';
                    } else {
                        hideList[index] = '[data-dlefilter-hide="' + index + '"]';
                    }
                }
            });
        } else if (elemTag === 'TEXTAREA') {
            $.each(objShow, function(index, value) {
                if ($.inArray($(e).val(), value) >= 0) {
                    showList[index] = '[data-dlefilter-hide="' + index + '"]';
                } else {
                    hideList[index] = '[data-dlefilter-hide="' + index + '"]';
                }
            });
        }

        let temp_array1 = Object.values(hideList);
        let temp_array2 = Object.values(showList);

        whatHide = temp_array1.filter(x => !temp_array2.includes(x))
        if (whatHide) {
            $.each(whatHide, function(index, value) {
                $(value).find('input[type="text"]').each(function() {
                    let nameElem = $(this).prop('name');
                    if ($(this).data('dlefilter-show') !== undefined) {
                        showAndHideFilter(this);
                    }
                    if (nameElem.length > 0) {
                        if (nameElem.indexOf('r.') + 1) {
                            let slider = $(this).data('ionRangeSlider');
                            slider.update({
                                from: slider.options.min,
                                to: slider.options.max
                            });
                        } else {
                            $(this).val('');
                        }
                    }
                });

                $(value).find('select').each(function(index) {
                    let nameElem = $(this).prop('name');
                    if (nameElem.length > 0) {
                        if ($(this).data('dlefilter-show') !== undefined) {
                            showAndHideFilter(this);
                        }

                        $(this).children('option').each(function() {
                            $(this).attr('selected', false);
                        });

                        if (dleFilter.jsSelect == 1) {
                            let getTail = $('.tail-select');
                            if (getTail.length > 0) {
                                tail.select('[data-dlefilter*=dle-filter] select[data-select-id="' + index + '"]').reload();
                            }
                        }
                    }
                });

                if (dleFilter.jsSelect == 2) {
                    let getChosen = $('.chosen-results');
                    if (getChosen.length > 0) {
                        $('[data-dlefilter*=dle-filter] select').trigger('chosen:updated');
                    }
                }

                if (dleFilter.jsSelect == 3) {
                    $('[data-dlefilter*=dle-filter] select').niceSelect('update');
                }

                $(value).find('input[type="radio"], input[type="checkbox"]').each(function() {
                    let nameElem = $(this).prop('name');
                    if (nameElem.length > 0) {
                        if ($(this).data('dlefilter-show') !== undefined) {
                            showAndHideFilter(this);
                        }
                        $(this).attr('checked', false);
                    }
                });
            });
        }
    };

    // Если текущая страница фильтр - задаем параметры
    if (dleFilter.path.indexOf('/' + dleFilter.filterUrl + '/') + 1) {
        setFilterParam();
    }

    // Скрываем скрываемые блоки
    $('[data-dlefilter-hide]').hide();

    // Показываем блоки если они нужны
    $('body').find('[data-dlefilter-show]').each(function(p, e) {
        hideList = {};
        whatHide = {};
        showList = {};

        showAndHideFilter(e);

        if (whatHide) {
            $(whatHide.join(', ')).hide();
        }

        if (showList) {
            $(Object.values(showList).join(', ')).show();
        }
    });

    // Показываем блоки если они нужны если изменились данные фильтра
    $('body').on('change', '[data-dlefilter-show]', function(e) {
        hideList = {};
        whatHide = {};
        showList = {};

        $('body').find('[data-dlefilter-show]').each(function(p, z) {
            showAndHideFilter(z);
        });

        if (whatHide) {
            $(whatHide.join(', ')).hide();
        }

        if (showList) {
            $(Object.values(showList).join(', ')).show();
        }
    });

});