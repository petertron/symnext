(function () {
    //var shift_key_down = false;
    //var ctrl_key_down = false;
    const doc_body = document.body;
    const doc_data = doc_body.dataset;
    let table = document.querySelector('table.selectable');
    if (!table) return;

    document.forms[0].reset();
    document.body.addEventListener('keydown', eventKeyMove);
    document.body.addEventListener('keyup', eventKeyMove);
    table.addEventListener('mousedown', eventTableClickCapture, true);
    table.addEventListener('x', eventTableClickBubble);

    function eventKeyMove(event)
    {
        doc_data.shiftKeyDown = event.shiftKey ? '1' : '0';
        doc_data.ctrlKeyDown = (event.ctrlKey || event.metaKey) ? '1' : '0';
    }

    function eventTableClickCapture(event)
    {
        let sel = window.getSelection();
        sel.removeAllRanges();
        let table = event.currentTarget;
        var rows = table.querySelectorAll('tbody tr');
        rows.forEach(function (row) {
            row.addEventListener('mousedown', eventTrClick, {once: true});
        });
        event.preventDefault();
    }

	function eventTableClickBubble(event)
	{
	    let table = event.currentTarget;
	    let focus_row = event.target;
	    if (focus_row == table) {
			alert("!");
			return;
		}
		let rows_selected = table.querySelectorAll('tbody tr.selected');
		let anchor_row = table.querySelector('tbody tr.anchor');
		let shift_key_down = (doc_data.shiftKeyDown == '1');

		let ctrl_key_down = (doc_data.ctrlKeyDown == '1');
		if (!shift_key_down) {
			if (!ctrl_key_down && rows_selected.length > 0) {
				rows_selected.forEach(function (row) {
					if (row !== focus_row) {
						updateRow(row, 'clear');
					}
				});
			}
			updateRow(focus_row, 'toggle');
            if (anchor_row) {
                anchor_row.classList.remove('anchor');
            }
            focus_row.classList.add('anchor');
		} else if (!ctrl_key_down) {
			let row = anchor_row;
			switch (anchor_row.compareDocumentPosition(focus_row)) {
				case 2:
				do {
					updateRow(row, 'set');
					row = row.previousElementSibling;
				} while (row !== focus_row);
				break;
				case 4:
				do {
					updateRow(row, 'set');
					row = row.nextElementSibling;
				} while (row !== focus_row);
				break;
			}
			updateRow(focus_row, 'set');
		}
	}

    function eventTrClick(event)
    {
		event.stopPropagation();
        let tr = event.currentTarget;
        tr.dispatchEvent(new CustomEvent('x', {bubbles: true}));
    }

    function updateRow(tr, action)
    {
        let checkbox = tr.querySelector('input[type=checkbox]');
        if (!checkbox) return;

        switch (action) {
            case 'set':
                checkbox.checked = true;
                break;
            case 'clear':
                checkbox.checked = false;
                break;
            case 'toggle':
                checkbox.checked = !checkbox.checked;
                break;
            default:
        }
        if (checkbox.checked) {
            tr.classList.add('selected');
        } else {
            tr.classList.remove('selected');
        }
    }

})();
