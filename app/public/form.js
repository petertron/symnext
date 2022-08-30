(function () {
    /*document
        .querySelector('button[name="action[submit]"]')
        .addEventListener('click', eventFormSubmit);*/

    function eventFormSubmit(event) {
        document.querySelectorAll('form input').forEach(function (input) {
            let div = input.closest('div');
            let span = div.querySelector('span');
            if (input.dataset.required == 'yes' && input.value == '') {
                div.classList.add('invalid');
                let tpl = document.querySelector('template[data-error="required"]');
                let msg = tpl.content.firstChild.cloneNode().textContent;
                span.textContent = msg;
                span.style.display = 'inline';
            } else {
                div.classList.remove('invalid');
                span.textContent = null;
                span.style.display = 'none';
            }
        });

        fetch(event.target.action, {
            method: 'POST',
            body: new FormData(event.target)
        })
        .then(function (response) {
            if (response.ok) {
                return response.json();
            }
            return Promise.reject(response);
        })
        .then(function (data) {
            console.log(data);
        })
        .catch(function (error) {
            console.warn(error);
        });
    }
})();
