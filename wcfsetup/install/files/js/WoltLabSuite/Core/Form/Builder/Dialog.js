/**
 * Provides API to easily create a dialog form created by form builder.
 * 
 * @author	Matthias Schmidt
 * @copyright	2001-2019 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @module	WoltLabSuite/Core/Form/Builder/Manager
 * @since	5.2
 */
define(['Ajax', 'Core', './Manager', 'Ui/Dialog'], function(Ajax, Core, FormBuilderManager, UiDialog) {
	"use strict";
	
	/**
	 * @constructor
	 */
	function FormBuilderDialog(dialogId, className, actionName, options) {
		this.init(dialogId, className, actionName, options);
	};
	FormBuilderDialog.prototype = {
		/**
		 * Initializes the dialog.
		 * 
		 * @param	{string}	dialogId
		 * @param	{string}	className
		 * @param	{string}	actionName
		 * @param	{{actionParameters: object, destoryOnClose: boolean, dialog: object}}	options
		 */
		init: function(dialogId, className, actionName, options) {
			this._dialogId = dialogId;
			this._className = className;
			this._actionName = actionName;
			this._options = Core.extend({
				actionParameters: {},
				destroyOnClose: false
			}, options);
			this._options.dialog = Core.extend(this._options.dialog || {}, {
				onClose: this._dialogOnClose.bind(this),
				onSetup: this._dialogOnSetup.bind(this)
			});
			
			this._formId = '';
			this._dialogContent = '';
		},
		
		/**
		 * Returns the data for Ajax to setup the Ajax/Request object.
		 * 
		 * @return	{object}	setup data for Ajax/Request object
		 */
		_ajaxSetup: function() {
			return {
				data: {
					actionName: this._actionName,
					className: this._className,
					parameters: this._options.actionParameters
				}
			};
		},
		
		/**
		 * Handles successful Ajax requests.
		 *
		 * @param	{object}	data	response data
		 */
		_ajaxSuccess: function(data) {
			if (data.returnValues === undefined) {
				throw new Error("Missing return data.");
			}
			else if (data.returnValues.dialog === undefined) {
				throw new Error("Missing dialog template in return data.");
			}
			else if (data.returnValues.formId === undefined) {
				throw new Error("Missing form id in return data.");
			}
			
			this.destroy(true);
			
			this._formId = data.returnValues.formId;
			this._dialogContent = data.returnValues.dialog;
			
			UiDialog.open(this, this._dialogContent);
		},
		
		/**
		 * Is called when clicking on the dialog form's close button.
		 */
		_closeDialog: function() {
			UiDialog.close(this);
		},
		
		/**
		 * Is called when the dialog is set up.
		 * 
		 * @param	{HTMLElement}	content		dialog's content element
		 */
		_dialogOnSetup: function(content) {
			var cancelButton = elById(this._formId + '_cancelButton', content);
			if (cancelButton !== null) {
				cancelButton.addEventListener('click', this._closeDialog.bind(this));
			}
		},
		
		/**
		 * Is called by the dialog API when the dialog is closed.
		 */
		_dialogOnClose: function() {
			if (this._options.destroyOnClose) {
				this.destroy();
			}
		},
		
		/**
		 * Returns the data used to setup the dialog.
		 * 
		 * @return	{object}	setup data
		 */
		_dialogSetup: function() {
			return {
				id: this._dialogId,
				options : this._options.dialog,
				source: this._dialogContent
			};
		},
		
		/**
		 * Is called by the dialog API when the dialog form is submitted.
		 */
		_dialogSubmit: function() {
			this.getData().then(this._submitForm.bind(this));
		},
		
		/**
		 * Submits the form with the given form data.
		 * 
		 * @param	{object}	formData
		 */
		_submitForm: function(formData) {
			if (typeof this._options.onSubmit === 'function') {
				this._options.onSubmit(formData);
			}
			else if (typeof this._options.submitActionName === 'string') {
				Ajax.api(this, {
					actionName: this._options.submitActionName,
					parameters: {
						data: formData
					}
				});
			}
		},
		
		/**
		 * Destroys the dialog.
		 * 
		 * @param	{boolean}	ignoreDialog	if `true`, the actual dialog is not destroyed, only the form is
		 */
		destroy: function(ignoreDialog) {
			if (this._formId !== '') {
				if (FormBuilderManager.hasForm(this._formId)) {
					FormBuilderManager.unregisterForm(this._formId);
				}
				
				if (ignoreDialog !== true) {
					UiDialog.destroy(this);
				}
			}
		},
		
		/**
		 * Returns a promise that all of the dialog form's data.
		 * 
		 * @return	{Promise}
		 */
		getData: function() {
			if (this._formId === '') {
				throw new Error("Form has not been requested yet.");
			}
			
			return FormBuilderManager.getData(this._formId);
		},
		
		/**
		 * Opens the dialog form.
		 */
		open: function() {
			if (UiDialog.getDialog(this._dialogId)) {
				UiDialog.openStatic(this._dialogId);
			}
			else {
				Ajax.api(this);
			}
		}
	};
	
	return FormBuilderDialog;
});