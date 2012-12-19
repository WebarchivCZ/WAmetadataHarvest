var	Bootstrap = typeof Bootstrap == 'undefined' ? {} : Bootstrap;
Bootstrap.netteForms = {};

Bootstrap.netteForms.currentError = null;
Bootstrap.netteForms.firstError = null;

/**
 * Replaces default Nette behaviour to support bootstrap theme
 */
Bootstrap.netteForms.addError = function (elem, message) {
	if (Bootstrap.netteForms.currentError == null) {
		Bootstrap.netteForms.firstError = {
			input: elem,
			message: message
		};
	}
	Bootstrap.netteForms.currentError = message;
};

Bootstrap.netteForms.cleanupTooltip = function (elem) {
	if (elem.data('tooltip-on-append')) {
		elem = elem.parent('.input-append');
	}
	elem.tooltip('hide').tooltip('destroy');
};

Bootstrap.netteForms.setErrorTooltip = function (elem) {
	var self = $(elem);
	self.parents('.control-group').addClass('error');
	var append = self.parent('.input-append');
	if (append.length) {
		self.data('tooltip-on-append', true);
		self = append;
	} else {
		self.removeData('tooltip-on-append');
	}
	self
		.tooltip('destroy')
		.tooltip({
			animation: true,
			title: Bootstrap.netteForms.currentError,
			placement: self.attr('type') in {radio: 1, checkbox: 1} ? 'top': 'right'
		});
}

Bootstrap.netteForms.cleanupError = function (elem) {
	var self = $(elem);
	self.parents('.control-group').removeClass('error');
	Bootstrap.netteForms.cleanupTooltip(self);
};

Bootstrap.netteForms.validateForm = function(sender) {
	var form = sender.form || sender;
	if (form['nette-submittedBy'] && form['nette-submittedBy'].getAttribute('formnovalidate') !== null) {
		return true;
	}
	var errors = 0;
	Bootstrap.netteForms.removeErrorAlerts(form);
	for (var i = 0; i < form.elements.length; i++) {
		var elem = form.elements[i];
		if (!(elem.nodeName.toLowerCase() in {input:1, select:1, textarea:1}) || (elem.type in {hidden:1, submit:1, image:1, reset: 1}) || elem.disabled || elem.readonly) {
			continue;
		}
		if (!Nette.validateControl(elem)) {
			Bootstrap.netteForms.setErrorTooltip(elem);
			if (elem.focus) {
				elem.focus();
			}
			errors++;
		} else {
			Bootstrap.netteForms.cleanupError(elem);
		}
	}
	if (errors) {
		Bootstrap.netteForms.showFirstErrorAlert(form);
	}
	return !errors;
};

Bootstrap.netteForms.cleanupErrors = function (form) {
	for (var i = 0; i < form.elements.length; i++) {
		var elem = form.elements[i];
		if (!(elem.nodeName.toLowerCase() in {input:1, select:1, textarea:1}) || (elem.type in {hidden:1, submit:1, image:1, reset: 1}) || elem.disabled || elem.readonly) {
			continue;
		}
		Bootstrap.netteForms.cleanupError(elem);
	}
};

Bootstrap.netteForms.removeErrorAlerts = function (form) {
	Bootstrap.netteForms.currentError = null;
	Bootstrap.netteForms.firstError = null;
	$(form).find('.alert').remove();
}
Bootstrap.netteForms.showFirstErrorAlert = function (form) {
	var error = Bootstrap.netteForms.firstError,
		form = $(form),
		alert = $('<div class="alert alert-block alert-error"><button type="button" class="close" data-dismiss="alert">×</button></div>'),
		id = error.input.id;
	var label = form.find('label[for="' + id + '"]').text();
	if (label) {
		alert.append($('<strong></strong>').text(label + ':'));
		alert.append(' ');
	}
	alert.append(error.message);
	if (error.input.focus) {
		alert.click(function () {
			error.input.focus();
		});
	}
	if (form.hasClass('row-fluid')) {
		alert.addClass('span12');
	}
	var f = $(error.input).parents('fieldset');
	if (f.length) {
		f.prepend(alert);
	} else {
		form.prepend(alert);
	}
}

Bootstrap.netteForms.init = function () {
	Nette.validateForm = this.validateForm;
	Nette.addError = this.addError;
}

Bootstrap.netteForms.init();
