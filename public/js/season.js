window.onload = function(){
	
	// alert ("season.js: start");

	// Hide all submit and reset buttons
	document.querySelectorAll("FORM").forEach(function(this_form){
		modifyInputFieldsOfForm(this_form, "", "hide");
	})

	// alert ("season.js: point 2");

	// When an input value is changed, enable all inputs and buttons in its form, 
	// and disable all inputs and buttons in other forms in the document
	document.querySelectorAll("INPUT").forEach(function(this_input){
		this_input.addEventListener("input", function(){
			// var my_form;
			// alert("Input " + this_input.name + " just got poked ");
			this.onchange = function(){
				// alert("Input " + this_input.name + " just changed to " + this.value);
				my_form = this_input.form;
				// alert("my_form name = " + $(my_form).attr('name'));
				document.querySelectorAll("FORM").forEach(function(this_form){
					// alert("this_form = " + $(this_form).attr('name') + "  my_form = " + $(my_form).attr('name'));
					if (this_form == my_form) {
						modifyInputFieldsOfForm(this_form, "enable", "show");
					} else {
						modifyInputFieldsOfForm(this_form, "disable", "hide");
					}
				}) 
			}	
		})
	}) 

	// When a form is reset, enable all inputs on all forms on the page
	document.querySelectorAll("FORM").forEach(function(this_form){
		this_form.addEventListener("reset", function(){
			document.querySelectorAll("FORM").forEach(function(a_form){
				modifyInputFieldsOfForm(a_form, "enable", "hide");
			})
			// for (i = 0; i < this_form.children.length; i++) {
				// alert("I'm " + this_form.children[i].name + " and my form just got reset, and my value is " + this_form.children[i].value); 
			// }
		})
	})
}