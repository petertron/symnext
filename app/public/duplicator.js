'use strict';

class DuplicatorFrame extends HTMLElement
{
    constructor()
    {
        super();
    }

    connectedCallback()
    {
        setTimeout(() => {
            this.constructorFieldSelector = this.querySelector('fieldset.apply select')
            this.constructorButton = this.querySelector('fieldset.apply button.constructor');
            this.addEventListener('click', this.onClick);
            this.addEventListener('orderstart', this.onOrderStart);
        }, 0);
    }

    onOrderStart(event)
    {
        this.orderingItem = event.target;
        this.addEventListener('pointermove', this.onPointerMove, {capture: true});
        this.addEventListener('pointerleave', this.onPointerLeave, {capture: true});
        this.addEventListener('pointerup', this.onPointerLeave, {capture: true});
    }

    onPointerMove(event)
    {
        let y = event.pageY - window.scrollY;
        requestAnimationFrame(() => {
            let item_ord = this.querySelector('duplicator-field.ordering');
            //let item_ord = this.orderingItem;
            if (!item_ord) return;
            let rect = item_ord.getBoundingClientRect();
            let top = rect.top,// + window.scrollY,
                bottom = rect.bottom,// + window.scrollY,
                item_swap;
            // Remove text ranges
            window.getSelection().removeAllRanges();
            // Move item up
            if (y < top) {
                item_swap = item_ord.previousElementSibling;
                if (item_swap) {
                    item_ord.after(item_swap);
                }
            }
            // Move item down
            else if (y > bottom) {
                item_swap = item_ord.nextElementSibling;
                if (item_swap) {
                    item_ord.before(item_swap);
                }
            }
        });
    }

    onPointerLeave(event)
    {
        if (event.type == 'pointerleave' && event.eventPhase != Event.AT_TARGET) return;
        let item_ord = this.querySelector('duplicator-field.ordering');
        if (item_ord) {
            item_ord.classList.remove('ordering');
            this.removeEventListener('pointermove', this.onPointerMove, {capture: true});
            this.removeEventListener('pointerleave', this.onPointerLeave, {capture: true});
            this.removeEventListener('pointerup', this.onPointerLeave,  {capture: true});
        }
        this.querySelectorAll('duplicator-field').forEach((item, index) => {
            item.setIndex(index);
        });
        event.stopPropagation();
    }

    onClick(event)
    {
        if (event.target == this.constructorButton) {
            let field_type = this.constructorFieldSelector ?
                this.constructorFieldSelector.value : 'default-field';
            let template = document.getElementById(field_type);
            let new_item = template.content.cloneNode(true);
            let num_fields = this.querySelectorAll('duplicator-field').length;
            this.firstChild.appendChild(new_item);
            new_item.setIndex(num_fields);
        }
    }

    expandAll()
    {
        this.setFieldState(false);
    }

    collapseAll()
    {
        this.setFieldState(true);
    }

    setFieldState(collapsed)
    {
        this.querySelectorAll('duplicator-field').forEach((item) => {
            item.collapsed = collapsed;
        });
    }
}

class DuplicatorField extends HTMLElement
{
    constructor()
    {
        super();
    }

    get collapsed()
    {
        return this.classList.contains('collapsed');;
    }

    set collapsed(value)
    {
        if (typeof value === 'boolean') {
            if (value) {
                this.classList.add('collapsed');
            } else {
                this.classList.remove('collapsed');
            }
        }
    }

    reset()
    {}

    connectedCallback()
    {
        setTimeout(() => {
            let header = this.querySelector('header');
            header.addEventListener('pointerdown', (event) => {this.onHeaderPointerDown(event);});
            header.addEventListener('click', (event) => {this.onHeaderClick(event);});
            this.querySelector('input[type="text"]')
                .addEventListener('input', (event) => {
                    let text = event.target.value;
                    text = text ? text : header.dataset.defaultName;
                    this.querySelector('header h4 strong').textContent = text;
                    event.stopPopagation();
                });
            }, 0)
    }

    onHeaderPointerDown(event)
    {
        event.stopPropagation();
        let target = event.target;
        if (target.matches('a.destructor')) {
            return;
        }
        this.timeout = setTimeout(() => {this.onTimeout();}, 200);
    }

    onHeaderClick(event)
    {
        let target = event.target;
        if (target.matches('a.destructor')) {
            this.remove();
            return;
        }
        if (this.timeout) {
            this.classList.toggle('collapsed');
            clearTimeout(this.timeout);
            this.timeout = null;
        }
    }

    onTimeout()
    {
        this.classList.add('ordering');
        this.timeout = null;
        this.dispatchEvent(new CustomEvent('orderstart', {bubbles: true}));
    }

    setIndex(index)
    {
        const inputs = this.querySelectorAll('*[name]');
        const regexp = /\[\-?[0-9]+\]/;
        inputs.forEach((input) => {
            let name = input.name;
            // Set index
            if (regexp.test(name)) {
                input.setAttribute('name', name.replace(regexp, `[${index}]`));
            }
        });
    }
}

class SelectorList extends HTMLUListElement
{
    constructor()
    {
        super();
    }

    connectedCallback()
    {
        const boundary_selector = this.getAttribute('boundary');
        const target_selector = this.getAttribute('target');
        let boundary_element = null;
        if (boundary_selector) {
            boundary_element = this.closest(boundary_selector);
        }
        if (!boundary_element) boundary_element = document;
        this.targetElement = boundary_element.querySelector(target_selector);
        this.addEventListener('click', this.onClick);
    }

    onClick(event)
    {
        if (event.target.matches('li')) {
        //alert(this.targetElement);
            if (event.target.dataset.output === undefined) {
                this.targetElement.value = event.target.textContent;
            } else {
                this.targetElement.value = event.target.dataset.output;
            }
        }
    }
}

class ExpandCollapse extends HTMLParagraphElement
{
    constructor()
    {
        super();
    }

    connectedCallback()
    {
        this.addEventListener('click', this.onClick);
    }

    onClick(event)
    {
        let element_for = document.getElementById('fields-duplicator');
        let target = event.target;
        if (target instanceof HTMLAnchorElement) {
            if (target.classList.contains('expand')) {
                element_for.expandAll();
            } else if (target.classList.contains('collapse')) {
                element_for.collapseAll();
            }
        }
    }
}

customElements.define('duplicator-frame', DuplicatorFrame);
customElements.define('duplicator-field', DuplicatorField);
customElements.define('expand-collapse', ExpandCollapse, {extends: 'p'});
customElements.define('selector-list', SelectorList, {extends: 'ul'});


