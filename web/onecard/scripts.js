var ACCOUNT_CUSTOM = -2;
var ACCOUNT_EMPTY = 0;

function selectAll() {
	var state = $('#allcheck').attr('checked');
	if(state){
		var allCheckbox = $('#chechoutTableUsers input[type=checkbox][name = "otherUsers[]"]').length;
		if(allCheckbox > $('#cardsAvail').html()){
			$('#allcheck').attr('checked', false);
			checkCredits(true);
			return false;
		}
	}
	$("input[type='checkbox']").attr('checked', state == 'checked');
	calculateAmount();
	return true;
}

function changeState(sender) {
	var state = $(sender).attr('checked');
	if(!state) {
		$('#allcheck').attr('checked', false);
	}
	else {
		if(!checkCredits()){
			$(sender).attr('checked', false);
			return false;
		}
		var checked = $('#chechoutTableUsers input:checked[type=checkbox][name = "otherUsers[]"]').length;
		var allCheckbox = $('#chechoutTableUsers input[type=checkbox][name = "otherUsers[]"]').length;
		$('#allcheck').attr('checked', checked === allCheckbox);
	}
	calculateAmount();
	return true;
}

function calculateAmount() {
	var count = $('#chechoutTableUsers input:checked[type=checkbox][name = "otherUsers[]"]').length;
	$('#cardsUsed').html(count);
	var left = $('#cardsAvail').html() - count;
	if(left < 0)
		left = 0;
	$('#cardsLeft').html(left);
//	if (count == $('#chechoutTableUsers input[type=checkbox]').length)
//		count -= 1;
//	$('#totalAmount').text("$" + count *  5 + ".00");
}

function sendUsersForm(button){
	var form = document.forms['editor_form'];
	if($('#cardsAvail').html() == '0'){
		showPopupWindow(document.getElementById('funcPopup'), true);
		return false;
	}
	if(checkedCount(form, 'otherUsers[]') == 0){
		showMessagePopup('error', 'No users selected', 'Please select one or more names.');
		return false;
	}
	if(!checkCredits())
		return false;
	button.disabled = true;
	form.submit();
	return true;
}

function checkCredits(force){
	var form = document.forms['editor_form'];
	if(force || (checkedCount(form, 'otherUsers[]') > $('#cardsAvail').html())){
		showMessagePopup('error', 'You do not have enough credits', 'Please <a href="/user/pay.php">donate or upgrade</a> to AwardWallet Plus to get more AwardWallet OneCards.');
		return false;
	}
	return true;
}

function showCardWarning(id) {
	showPopupWindow($(id).get(0), true);
}

var listTimer = null;

function rowName(row){
	if($(row).find('input.account').val() == ACCOUNT_CUSTOM)
		text = trim($(row).find('input.accountText').val());
	else
		text = trim($(row).find('p.title').html());
	if(text == '') // empty goes last
		text = 'яяяяяяяяяяяяяяяяя';
	if(text == 'Loyalty Program') // header row goes first
		text = ' ';
	text += $(row).find('input.number').val();
	return text.toLowerCase();
}

function sortRows(rows, sort){
	rows.sortElements(
		function(a, b){
			if(sort == 'alphabet'){
				var aText = rowName(a);
				var bText = rowName(b);
			}
			else{
				aText = Math.round($(a).attr('data-priority'));
				bText = Math.round($(b).attr('data-priority'));
			}
			if(aText == bText)
				return 0;
			else
				if(aText > bText)
					return 1;
				else
					return -1;
        },
		function(){
			return this;
		}
	);
}

function hideSelect(){
	var select = $('#accountSelect');
	if(select.css('display') == 'block'){
		select.css('display', 'none');
		select.closest("tr").find("td p.accountSelected").css("display","none");
		select.closest("tr").find("td p.title").css("display","block");
		document.getElementById('cardDesigner').appendChild(document.getElementById('accountSelect'));
		$('#accountSelect option[value = -1]').remove().empty();
	}
}

function bindSelectChange() {
	$("p.title").click(function () {
		hideSelect();
		var accountId = Math.round($(this).parent("td").find('input').val());
//		$(this).parent("td").parent("tr").parent("tbody").find("tr td p.accountSelected").not('div.accountText').css("display", "none");
//		$(this).parent("td").parent("tr").parent("tbody").find("tr td p.title").css("display", "block");
		$(this).css("display", "none");

		var table = $(this).closest('table');
		if(table.hasClass('frontSide')){
			var user = table.closest('div.card').attr('data-user');
			var userCards = $('#cardDesigner div.card[data-user = '+user+']').length;
			if(userCards == 1){
				var dataRows = 0;
				table.find('tr.accountRow').each(function(index, element){
					var priority = Math.round($(element).attr('data-priority'));
					if(priority >= 0 && priority < 999999)
						dataRows++;
				});
				// insert header row, if there are space available
				if(dataRows < 15){
					if((accountId == -1) || (table.find('input.account[value = -1]').length == 0)){
						var emptyOption = $('#accountSelect option[value = 0]');
						var headerOption = emptyOption.clone();
						headerOption.val('-1');
						headerOption.attr('data-priority', 0);
						headerOption.attr('data-provider', 'Loyalty Program');
						headerOption.attr('data-number', 'Account #');
						headerOption.attr('data-phone', 'Phone');
						headerOption.attr('data-level', 'Status');
						headerOption.html('Header Row');
						headerOption.insertAfter(emptyOption);
					}
				}
			}
		}
		$(this).next("p.accountSelected").css("display", "block");
		//document.getElementById('accountSelect').selectedIndex = 0;
		$('#accountSelect option[value = "'+accountId+'"]').attr('selected', 'selected');
		if(document.getElementById('accountSelect').parentNode != $(this).next("p").get(0))
			$(this).next("p").get(0).appendChild(document.getElementById('accountSelect'));

		$(this).next("p").find("select").css("display", "block");
	});

	$('.cardWarning').click(function() {
		showCardWarning('#funcPopup');
		return false;
	})

	$(".accountsTable tr td input").focusin(function() {
		$(this).addClass("focused");
		hideSelect();
	});
	$(".accountsTable tr td input").focusout(function() {
		$(this).removeClass("focused");
		if($(this).hasClass('accountText')){
			var row = $(this).closest('tr');
			row.find('p.title').html($(this).val());
			if(trim($(this).val()) == ''){
				$(this).hide();
				row.find('p.title').show();
				row.find('input.account').val(ACCOUNT_EMPTY);
				row.attr('data-priority', '999999');
			}
			sortRowsAndKeepFocus(row);
		}
	});
	var selectChanging = false;
	$("#accountSelect").change(function () {
		if(selectChanging)
			return;
		var accountID = $(this).val();
		var selectedOption = $(this).find('option:selected');
		var row = $(this).closest('tr');
		input = this;
		selectChanging = true;
		fillRowFromOption(row, selectedOption, false);
		hideSelect();
		if(accountID == ACCOUNT_CUSTOM){
			row.find("td:eq(0) p.title").css('display', 'none');
			row.find("td:eq(0) input.accountText").css('display', 'block').focus();
		}
		else{
			row.find("td:eq(0) p.title").css('display','block');
			row.find("td:eq(0) p.accountSelected").css('display','none');
			sortRowsAndKeepFocus(row);
		}
		selectChanging = false;
	});

	$('input.phone').focus(function(){
		$(this).parent().addClass('focused');
	}).blur(function(){
		$(this).parent().removeClass('focused');
	});

	$('div.dropButton').click(function(){
		if(listTimer){
			clearTimeout(listTimer);
			listTimer = null;
		}
		var input = $(this).closest('table').find('input');
		var accountId = $(this).closest('tr.accountRow').find('input.account').val();
		var list = $('#phoneList');
		if(list.attr('accountId') != accountId){
			list.css('display', 'none');
			list.width(input.width() + $(this).width());
			list.html('Loading..');
			$.ajax({
				url: "/onecard/phones.php?AccountID=" + accountId + '&Country='  + getCountry(),
				success: function(data){
					list.html(data);
					var width = 0;
					var spans = list.find('span');
					for(i = 0; i < spans.length; i++){
						var span = $(spans[i]);
						if(span.width() > width)
							width = span.width();
					}
					list.width(Math.max(list.width(), width + 20));
					list.attr('accountId', accountId);
					list.find('div.row').click(function(){
						input.val($(this).attr('phone'));
						list.css('display', 'none');
						input.focus();
					});
				},
				error: ajaxError
			});
		}
		if(list.css('display') == 'none'){
			list.css('display', 'block');
			list.position({
				my: "left top",
				at: "left bottom",
				of: input,
				collision: "fit"
			});
			//list.css('top', input.offset().top + input.height());
			//list.css('left', input.offset().left + );
			input.focus();
			input.blur(function(){
				if(listTimer)
					clearTimeout(listTimer);
				listTimer = setTimeout(function(){
					list.css('display', 'none');
					listTimer = null;
				}, 200);
			});
		}
		else
			list.css('display', 'none');
	});
}

function sortRowsAndKeepFocus(row){
	var rows;
	var uAgentID = row.parents(".accountsTable").attr('rel');
	if(getSort() == 'priority')
		rows = $("table.accountsTable[rel = "+uAgentID+"] tbody tr.accountRow");
	else{
		var user = row.closest('div.card').attr('data-user');
		rows = getUserRows(user);
	}

	var oldIndex = rows.index(row);
	sortRows(rows, "alphabet");
	var newIndex = rows.index(row);

	if((newIndex != oldIndex) && (Math.round(row.find('input.account').val()) != 0))
		blinkRow(row);
}

function blinkRow(row){
	row.scrollintoview({
		complete: function(){
			var flashes = 0;
			var lightOn = false;
			var switchLight = function(){
				if(!lightOn)
					row.css('background-color', 'white');
				else
					row.css('background-color', '');
				lightOn = !lightOn;
				flashes++;
				if(flashes < 6)
					setTimeout(switchLight, 300);
			};
			setTimeout(switchLight, 300);
		}
	});
}

function showDeleteLinks(){
	var info = getUsersAndCards();
	if(info.cards.length > 1)
		for(var user in info.users)
			$('#cardDesigner a.deleteLink[data-user = '+user+']').last().css('display', '');
	$('#cardDesigner a.deleteLink').click(function(){
		var row = $(this).closest('tr');
		showMessagePopup('question', 'Confirmation', 'Delete this card?', false, 'Delete');
		document.getElementById('messageOKButton').onclick = function(){
			cancelPopup();
			row.fadeOut(800, function(){
				row.empty();
				showDeleteLinks();
				$('#fader').css('height', '1px');
				reindexCards();
			});
			row.next().fadeOut(800, function(){
				row.next().empty();
			});
		};
		document.getElementById('messageCancelButton').style.display = "";
		$('#messageCancelButton').removeClass('btn-blue').addClass('btn-silver');
		return false;
	});
}

function reindexCards(){
	var info = getUsersAndCards();
	for(var user in info.users){
		var cardCount = $('#cardDesigner a.deleteLink[data-user = '+user+']').length;
		var indexes = $('#cardDesigner span.cardIndex[data-user = '+user+']');
		if(cardCount > 1)
			indexes.each(function(index, element){
				element.innerHTML = '('+(index+1)+' of '+cardCount+')';
			});
		else
			indexes.each(function(index, element){
				element.innerHTML = '';
			});
	}
}

function countryChanged(){
	showPopupWindow(document.getElementById('waitPopup'), true, null, true);
	setTimeout(function(){
		$.ajax({
			dataType: 'json',
			url: "/onecard/getAccountInfo.php?Country="  + getCountry(),
			success: function(data){
				for(var accountId in data){
					var info = data[accountId];
					var row = $('#cardDesigner input.account[value = '+accountId+']').closest('tr');
					row.find("td:eq(3) input").val(info.Phone);
				}
				cancelPopup();
			},
			error: ajaxError
		});
	}, 10);
}

function getSort(){
	return $('#cardDesigner input:checked[name = Sort]').val();
}

function getCountry(){
	return $('#cardDesigner select[name = Country] option:selected').val();
}

function getUserRows(user){
	return $("div[data-user = "+user+"] table.accountsTable tbody tr.accountRow");
}

function getUsersAndCards(){
	var users = new Array();
	var cards = $('div.card');
	cards.each(function(index, element){
		var user = $(element).attr('data-user');
		users[user] = true;
	});
	return {
		users: users,
		cards: cards
	};
}

function sortAccounts(){
	showPopupWindow(document.getElementById('waitPopup'), true, null, true);
	setTimeout(function(){
		var sort = getSort();
		var info = getUsersAndCards();
		for(var user in info.users){
			var rows = getUserRows(user);
			sortRows(rows, sort);
		}
		if(sort == 'priority'){
			info.cards.each(function(index, element){
				var rows = $(element).find("table.accountsTable tbody tr.accountRow");
				sortRows(rows, "alphabet");
			});
		}
		cancelPopup();
	}, 10);
}

function clearBgrLine(){
	var rows = $("table.accountsTable tbody tr");
	for(i = 0; i < rows.length; i++){		
		$(rows[i]).children("td:eq(0)").css('background','transparent');
	}
}

function saveOneCardEditor(button, aaConfirmed){
	var form = document.forms['cardDesigner'];
	var checked = true;
	$('input[name = "otherUsers[]"]').each(function(index, input){
		var userAgentId = input.value;
		saveOneCardRow(userAgentId, 'front', 'input.account', form, 'AccFront');
		saveOneCardRow(userAgentId, 'front', 'p.title', form, 'PFront');
		saveOneCardRow(userAgentId, 'front', 'input.number', form, 'AFront');
		saveOneCardRow(userAgentId, 'front', 'input.level', form, 'SFront');
		saveOneCardRow(userAgentId, 'front', 'input.phone', form, 'PhFront');
		saveOneCardRow(userAgentId, 'back', 'input.account', form, 'AccBack');
		saveOneCardRow(userAgentId, 'back', 'p.title', form, 'PBack');
		saveOneCardRow(userAgentId, 'back', 'input.number', form, 'ABack');
		saveOneCardRow(userAgentId, 'back', 'input.level', form, 'SBack');
		saveOneCardRow(userAgentId, 'back', 'input.phone', form, 'PhBack');
		var userName = trim(form['FullName_' + userAgentId].value);
		if(userName == ''){
			showMessagePopup('error', 'Error', 'Full name required');
			checked = false;
			return false;
		}
	});
	if(checked){
		button.disabled = true;
		form.submit();
	}
}

function saveOneCardRow(userAgentId, side, query, form, field){
	var values = new Array();
	$('div.card_'+userAgentId+' table.'+side+'Side td '+query).each(function(index, element){
		if(element.tagName == 'INPUT')
			values.push(trim(element.value));
		else
			if(element.tagName == 'SELECT')
				values.push(trim(element.value));
			else
				values.push(trim(element.innerHTML));
	});
	form[field+'_'+userAgentId].value = values.join("\n");
}

function bindPayAmountChange(){
	$('#fldAmount').change(payAmountChanged);
	$('#fldAmount').keyup(payAmountChanged);
}

function payAmountChanged(){
	var amount = Math.floor(document.getElementById("fldAmount").value);
	var cards = 0;
	if(!isNaN(amount)){
		if (amount >= 10){
			if(amount < 25)
				cards = 1;
			else
				cards = Math.floor(amount / 25) * 3;
		}
	}
	document.getElementById("cardsAvail").innerHTML = cards;
}

function bindPayOneCardBusiness(){
	$('#fldAmount').change(PayOneCardBusiness);
	$('#fldAmount').keyup(PayOneCardBusiness);
}

function PayOneCardBusiness(){
	var amount = Math.floor(document.getElementById("fldAmount").value);
	var cash = 0;
	if(!isNaN(amount)){
		cash = Math.floor(amount / 3) * 25 + (amount % 3) * 10;
	}
	document.getElementById("cash").innerHTML = cash;
}

function fillup(){
	// clear not matching
	var option = $('#accountSelect option:first');
	$('table.accountsTable tbody tr.accountRow').each(function(index, element){
		var row = $(element);
		var checkbox = $('#ua_' + row.attr('data-userAgentId'));
		if(checkbox.length > 0 && !checkbox.attr('checked')){
			fillRowFromOption(row, option, false);
		}
	});

	// find empty rows
	var rows = new Array();
	$("table.accountsTable tbody tr.accountRow").each(function(index, element){
		var row = $(element);
		if(row.attr('data-priority') == '999999')
			rows.push(row);
	});
	rows = rows.reverse();

	// add new
	$('#accountSelect option').each(function(index, element){
		var option = $(element);
		var checkbox = $('#ua_' + option.attr('data-userAgentId'));
		var exists = $('input.account[value = "' + option.attr('data-accountId') + '"]');
		if(checkbox.is(":checked") && (exists.length == 0)){
			var row = rows.pop();
			fillRowFromOption(row, option, true);
		}
		if(rows.length == 0)
			return false;
	});
	
	rows.forEach(function(row){
	    // row.detach();
	    console.log(row);
    });

	// sort
	sortRows($("table.accountsTable tbody tr.accountRow"), "alphabet");
}

function fillRowFromOption(row, option, addName){
	row.find("td:eq(0) p.title").html(option.attr('data-provider'));
	row.attr('data-userAgentId', option.attr('data-userAgentId'));
	row.attr('data-priority', option.attr('data-priority'));
	row.find("td:eq(0) input.account").val(option.attr('data-accountId'));
	row.find("td:eq(1) input").val(option.attr('data-number'));
	if(addName){
		var name = option.attr('data-userName');
		if(name.length > 16)
			name = name.replace(/\s.+$/i, '');
		row.find("td:eq(2) input").val(name);
	}
	else
		row.find("td:eq(2) input").val(option.attr('data-level'));
	row.find("td:eq(3) input").val(option.attr('data-phone'));
}

function fillDataFromBrowser(){
	for(var accountId in browserExt.accounts){
		var account = browserExt.accounts[accountId];
		var row = $('#cardDesign input.account[value = "' + accountId + '"]').closest('tr');
		var option = $('#accountSelect option[value = "' + accountId + '"]');
		if(option.length > 0){
			if(typeof(account.login) != 'undefined')
				option.attr('data-number', account.login);
			if(typeof(account.properties) != 'undefined'){
				if(typeof(account.properties.Number) != 'undefined')
					option.attr('data-number', account.properties.Number);
				if(typeof(account.properties.Status) != 'undefined')
					option.attr('data-level', account.properties.Status);
			}
//			if(typeof(account.properties) != 'undefined' && typeof(account.properties.Balance) != 'undefined')
//				increaseTotal(option.attr('data-userAgentId'), browserExt.numericBalance(account));
			if(row.length > 0)
				fillRowFromOption(row, option, false);
		}
	}
}

function getTotalsFromBrowser(){
	$('#browserData').val($.toJSON(browserExt.getBalances()));
}

/*
function increaseTotal(userAgentId, balance){
	if(userAgentId == '')
		userAgentId = 0;
	$('#cardDesign input[name ^= "Total_'+userAgentId+'_"]').each(function(index, el){
		var miles = Math.round(el.value);
		miles = miles + balance;
		el.value = miles;
		$(el).closest('div').find('span.formattedTotal').html(formatNumberBy3(miles, ".", thousandsSeparator));
	});
}*/