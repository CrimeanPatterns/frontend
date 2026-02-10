var balances = function(){

	var self = {};

	self.init = function(){
		$('#select-all').click(self.selectAllClicked);
		self.getCheckboxes().click(self.checkboxClicked);
		$('#updateProgress button').click(self.stopUpdate);
		$('#updateComplete button').click(self.stopUpdate);
	};

	/**
	 * select all checkbox clicked, toggle selection
	 */
	self.selectAllClicked = function(){
		self.selectAll($('#select-all').is(':checked'));
	};

	self.stopUpdate = function(){
		updater.stop();
		$('.update-form').slideUp();
	};

	/**
	 * select/deselect "all" checkbox
	 */
	self.checkboxClicked  = function(){
		$('#select-all').prop('checked', self.getSelectedAccounts(false).length == self.getCheckboxes().length);
	};

	/**
	 * start account update
	 * if no accounts selected - it means we should check all accounts
	 */
	self.updateSelected = function(){
		$('#updateComplete').hide();
		$('#updateProgress').show();
		updater.start(self.getSelectedAccounts(true), self.updateProgress);
		$('.update-form li').hide();
		$('.update-form').slideDown();
	};

	/**
	 * caller on accounts update progress
	 * @param updater
	 */
	self.updateProgress = function(updater){
		var form = $('.update-form');
		if(updater.state.complete){
			form.find('h3').text("Update complete");
			$('#updateProgress').hide();
			$('#updateComplete').show();
		}
		else{
			form.find('h3').text("Updating " + (updater.state.successCount + updater.state.errorCount) + ' of ' + updater.accounts.length + ' accounts');
		}
		form.find('div.progress span').css('width', updater.state.progress + '%');
		if(updater.state.successCount > 0)
			form.find('li.done').slideDown().find('span.change').text(updater.state.successCount);
		if(updater.state.errorCount > 0)
			form.find('li.errors').slideDown().find('span.change').text(updater.state.errorCount);
		if(updater.state.increase.accounts > 0){
			form.find('li.increment').slideDown().find('span.change').text(updater.state.increase.change);
			form.find('li.increment span.text').text(updater.state.increase.text);
		}
		if(updater.state.decrease.accounts != 0){
			form.find('li.decrement').slideDown().find('span.change').text(updater.state.decrease.change);
			form.find('li.decrement span.text').text(updater.state.decrease.text);
		}
		if(updater.state.totalChange != 0)
			form.find('li.totals').slideDown().find('span.change').text(updater.state.totalChange);
	};

	/**
	 * internal. get each account checkbox
	 */
	self.getCheckboxes = function(){
		return $('#search-result').find('td.check-cell input[type="checkbox"]');
	};

	/**
	 * get ids of selected accounts
	 * @param noneMeansAll - return all accounts if none selected
	 * @returns {Array} selected accounts, ids
	 */
	self.getSelectedAccounts = function(noneMeansAll){
		var result = [];
		var all = [];
		self.getCheckboxes().each(function(){
			var box = $(this);
			var id = box.closest('tr').data('account');
			if(box.is(':checked'))
				result.push(id);
			all.push(id);
		});
		if(result.length == 0)
			result = all;
		return result;
	};

	/**
	 * select or deselect all checkboxes
	 * @param select
	 */
	self.selectAll = function(select){
		self.getCheckboxes().each(function(){
			$(this).prop('checked', select);
		});
	};

	$(document).ready(self.init);

	return self;

}();