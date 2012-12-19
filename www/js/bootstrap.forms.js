/**
 * Utility functions for using cooler forms with bootstrap
 */
var	Bootstrap = typeof Bootstrap == 'undefined' ? {} : Bootstrap,
   	forms = Bootstrap.forms = {};

/**
 * Decorate forms with replaceRadios and replaceCheckboxes
 */
forms.decorate = function (forms) {
	forms.find('.control-group > .controls').each(function () {
		var container = $(this),
			radios = container.find('input[type=radio]'),
			checkboxes = container.find('input[type=checkbox]');
		if (radios.length) {
			Bootstrap.forms.replaceRadios(container, radios);
		}
		if (checkboxes.length > 1) {
			Bootstrap.forms.replaceCheckboxes(container, checkboxes);
		}
	});
};

/**
 * Replaces radio buttons and their labels with button groups
 */
forms.replaceRadios = function (container, radios) {
	var group = $('<div class="btn-group" data-toggle="buttons-radio"></div>');
	radios.each(function () {
		var radio = $(this),
			button = $('<button type="button" class="btn"></button>'),
			label = radio.parent().hide();
		button.text(label.text())
		.click(function () {
			radio.attr('checked', true).change();
		});
		if (radio.is(':checked')) {
			button.addClass('active');
		}
		group.append(button);
	});
	container.append(group);
};

/**
 * Replaces checkboxes and their labels with buttons
 */
forms.replaceCheckboxes = function (container, checkboxes) {
	checkboxes.each(function () {
		var checkbox = $(this),
			button = $('<button type="button" class="btn" data-toggle="button"></button>'),
			label = checkbox.parent().hide();
		button.text(label.text())
		.click(function () {
			checkbox.attr('checked', !$(this).hasClass('active')).change();
		});
		if (checkbox.is(':checked')) {
			button.addClass('active');
		}
		container.append(button);
	}).parents('.control-group').addClass('control-group-checkboxes');
};

forms.addTabsToInputs = function (inputs) {
	var tabs = $('<ul class="nav nav-tabs"></ul>'),
		contents = $('<div class="tab-content"></div>'),
		container = $('<div class="tabbable"></div>'),
		parents = inputs.parents('.control-group');
	inputs.each(function (index) {
		var self = $(this),
			id = self.attr('name'),
			tab = $('<li><a href="#' + id + '" data-toggle="tab">' + self.parents('.control-group').find('label[for="' + self.attr('id') + '"]').html() + '</a></li>').appendTo(tabs),
			pane = $('<div class="tab-pane" id="' + id + '"></div>').appendTo(contents);
		pane.append(self);
		if (!index) {
			tab.addClass('active');
			pane.addClass('active');
		}
	});
	var controlGroup = $('<div class="control-group">'
		+ '<label class="control-label tabs-left"></label>'
		+ '<div class="controls"></div>'
		+ '</div>');
	container
		.append(contents);
	controlGroup
		.find('label')
		.append(tabs);
	controlGroup
		.find('.controls')
		.append(container)
	controlGroup.insertBefore(parents.filter(':eq(0)'));
	parents.remove();
};
