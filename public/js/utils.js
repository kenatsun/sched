/**
 * document ready - initialize elements
 */
var month_names = {
	'January' : 1,
	'February' : 2,
	'March' : 3,
	'April' : 4,
	'May' : 5,
	'June' : 6,
	'July' : 7,
	'August' : 8,
	'September' : 9,
	'October' : 10,
	'November' : 11,
	'December' : 12
};

$(document).ready(function() {
	var $ = jQuery;
	Survey.init();

});

/**
 * Various utilities which govern the survey.
 */
Survey = {
	option_levels: {},

	init: function() {
		Survey.getSelectOptions();
		Survey.enableMonthSelect();
		Survey.enableWeekdaySelect();
		Survey.enableWeekSelect();

		$('#avoid_worker, #prefer_worker').select2({
			placeholder: 'Select one or more names',
			width: 350,
		}).on('change', Survey.workerPrefChange);

		$('p.month_mark_all').on('click', 'a', Survey.markMonth);
	},

	/**
	 * Prefer / Avoid list connection.
	 * Detect when a name is being added to the other list, remove it from the
	 * first one and notify.
	 */
	workerPrefChange: function(e) {
		var currentId = $(this).attr('id'),
			otherId = 'prefer_worker',
			otherName = 'Prefer',
			added = e.added,
			removed = e.removed,
			found = false,
			otherList, i, vals;

		if (currentId == 'prefer_worker') {
			otherId = 'avoid_worker';
			otherName = 'Avoids';
		}

		otherList = $('#' + otherId);

		if (added) {
			vals = otherList.select2('val');
			for (i in vals) {
				// does this name exist in the other list?
				if (added.id == vals[i]) {
					found = true;
					vals.splice(i, 1);
					otherList.select2('val', vals);
					alert(added.id + ' has been removed from the ' +
						otherName + ' list.');
					break;
				}
			}
		}
	},

	/**
	 * Get the <select> options, or prefer / ok / avoids.
	 */
	getSelectOptions: function() {
		var first_select = $('.choice select')[0];
		$(first_select).children().each(function() {
			Survey.option_levels[$(this).text()] = $(this).val();
		});
	},

	getAffectedWeekdays: function(chosen_elem) {
		// find the class containing the weekday number
		var weekday_num_x = $(chosen_elem).parent('td').attr('class');
		// extract the numeric portion
		var num = weekday_num_x.replace(/.*weekday_num_(\d).*/, "$1");

		// find the select days of the week inside the current month
		return $(chosen_elem).parents('.month_wrapper').find('td.dow_' + num);
	},

	enableWeekdaySelect: function() {
		$('tr.weekdays').show();

		$('tr.weekdays td a').hover(function() {
			Survey.getAffectedWeekdays(this).addClass('affecting');
		},
		function() {
			Survey.getAffectedWeekdays(this).removeClass('affecting');
		}).click(function() {
			// get the option level number to set this to:
			var level_num = Survey.option_levels[$(this).attr('class')];

			var weekdays = Survey.getAffectedWeekdays(this);
			weekdays.find('select').val(level_num);
		});
	},

	enableWeekSelect: function() {
		$('td.week_selector a').hover(function() {
			$(this).parents('tr').find('td').addClass('affecting');
		},
		function() {
			$(this).parents('tr').find('td').removeClass('affecting');
		}).click(function() {
			var level_num = Survey.option_levels[$(this).attr('class')];
			$(this).parents('tr').find(':input').val(level_num);
		});
	},

	enableMonthSelect: function() {
		// add links to change all shifts in the entire month
		$('.month_wrapper').each(function(i,e) {
			$(this).find('h3').after(
				'<p class="month_mark_all" style="text-align:center; ">mark entire month: \
					<a class="prefer">prefer<\/a> \
					<a class="ok">ok<\/a> \
					<a class="avoid">avoid<\/a><\/p>'
			);
		});
	},

	/**
	 * Mark the month - make a preference selection for all shifts within a month.
	 * @param[in] element DOM element, the link which was selected to take this
	 *     action.
	 * @param[in] choice int, the chosen preference level.
	 */
	markMonth: function(e) {
		var cur_el = $(this),
			month_name = cur_el.closest(".month_wrapper").attr("id"),
			choice = 0;

		if (cur_el.hasClass('prefer')) {
			choice = 2;
		}
		else if (cur_el.hasClass('ok')) {
			choice = 1;
		}
		$("#" + month_name + " :input").val(choice);
	}
};
