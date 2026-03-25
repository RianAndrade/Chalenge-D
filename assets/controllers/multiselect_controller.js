import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['select', 'search', 'dropdown', 'tags'];

    connect() {
        this.originalSelect = this.selectTarget;
        this.originalSelect.style.display = 'none';

        this.buildUI();
        this.syncFromSelect();

        this.handleDocumentClick = this.handleDocumentClick.bind(this);
        document.addEventListener('click', this.handleDocumentClick);
    }

    disconnect() {
        document.removeEventListener('click', this.handleDocumentClick);
    }

    buildUI() {
        const wrapper = document.createElement('div');
        wrapper.classList.add('multiselect-wrapper');

        // Tags container + search input
        const inputArea = document.createElement('div');
        inputArea.classList.add('multiselect-input');
        inputArea.addEventListener('click', () => {
            this.searchInput.focus();
            this.openDropdown();
        });

        this.tagsContainer = document.createElement('div');
        this.tagsContainer.classList.add('multiselect-tags');

        this.searchInput = document.createElement('input');
        this.searchInput.type = 'text';
        this.searchInput.classList.add('multiselect-search');
        this.searchInput.placeholder = 'Buscar...';
        this.searchInput.addEventListener('input', () => this.filterOptions());
        this.searchInput.addEventListener('focus', () => this.openDropdown());
        this.searchInput.addEventListener('keydown', (e) => this.handleKeydown(e));

        inputArea.appendChild(this.tagsContainer);
        inputArea.appendChild(this.searchInput);

        // Dropdown
        this.dropdownEl = document.createElement('div');
        this.dropdownEl.classList.add('multiselect-dropdown');
        this.dropdownEl.style.display = 'none';

        wrapper.appendChild(inputArea);
        wrapper.appendChild(this.dropdownEl);

        this.originalSelect.parentNode.insertBefore(wrapper, this.originalSelect.nextSibling);

        this.buildOptions();
    }

    buildOptions() {
        this.dropdownEl.innerHTML = '';
        const options = Array.from(this.originalSelect.options);

        options.forEach((option) => {
            const item = document.createElement('div');
            item.classList.add('multiselect-option');
            item.dataset.value = option.value;
            item.textContent = option.textContent;

            if (option.selected) {
                item.classList.add('selected');
            }

            item.addEventListener('click', (e) => {
                e.stopPropagation();
                this.toggleOption(option.value);
            });

            this.dropdownEl.appendChild(item);
        });
    }

    syncFromSelect() {
        this.tagsContainer.innerHTML = '';
        const selected = Array.from(this.originalSelect.selectedOptions);

        selected.forEach((option) => {
            this.addTag(option.value, option.textContent);
        });

        this.updatePlaceholder();
        this.updateDropdownState();
    }

    addTag(value, label) {
        const tag = document.createElement('span');
        tag.classList.add('multiselect-tag');
        tag.dataset.value = value;

        const text = document.createElement('span');
        text.textContent = label;

        const remove = document.createElement('button');
        remove.type = 'button';
        remove.classList.add('multiselect-tag-remove');
        remove.innerHTML = '&times;';
        remove.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggleOption(value);
        });

        tag.appendChild(text);
        tag.appendChild(remove);
        this.tagsContainer.appendChild(tag);
    }

    toggleOption(value) {
        const option = this.originalSelect.querySelector(`option[value="${value}"]`);
        if (!option) return;

        option.selected = !option.selected;

        // Dispatch change event for form compatibility
        this.originalSelect.dispatchEvent(new Event('change', { bubbles: true }));

        this.syncFromSelect();
        this.searchInput.value = '';
        this.filterOptions();
        this.searchInput.focus();
    }

    filterOptions() {
        const query = this.searchInput.value.toLowerCase().trim();
        const items = this.dropdownEl.querySelectorAll('.multiselect-option');
        let hasVisible = false;

        items.forEach((item) => {
            const text = item.textContent.toLowerCase();
            const match = !query || text.includes(query);
            item.style.display = match ? '' : 'none';
            if (match) hasVisible = true;
        });

        // Show/hide empty state
        let emptyMsg = this.dropdownEl.querySelector('.multiselect-empty');
        if (!hasVisible) {
            if (!emptyMsg) {
                emptyMsg = document.createElement('div');
                emptyMsg.classList.add('multiselect-empty');
                emptyMsg.textContent = 'Nenhum resultado encontrado';
                this.dropdownEl.appendChild(emptyMsg);
            }
            emptyMsg.style.display = '';
        } else if (emptyMsg) {
            emptyMsg.style.display = 'none';
        }
    }

    updateDropdownState() {
        const items = this.dropdownEl.querySelectorAll('.multiselect-option');
        items.forEach((item) => {
            const option = this.originalSelect.querySelector(`option[value="${item.dataset.value}"]`);
            if (option && option.selected) {
                item.classList.add('selected');
            } else {
                item.classList.remove('selected');
            }
        });
    }

    updatePlaceholder() {
        const hasSelected = this.originalSelect.selectedOptions.length > 0;
        this.searchInput.placeholder = hasSelected ? '' : 'Buscar veterinário...';
    }

    openDropdown() {
        this.dropdownEl.style.display = '';
        this.filterOptions();
    }

    closeDropdown() {
        this.dropdownEl.style.display = 'none';
        this.searchInput.value = '';
    }

    handleDocumentClick(event) {
        const wrapper = this.originalSelect.parentNode.querySelector('.multiselect-wrapper');
        if (wrapper && !wrapper.contains(event.target)) {
            this.closeDropdown();
        }
    }

    handleKeydown(event) {
        if (event.key === 'Backspace' && !this.searchInput.value) {
            const tags = this.tagsContainer.querySelectorAll('.multiselect-tag');
            if (tags.length > 0) {
                const lastTag = tags[tags.length - 1];
                this.toggleOption(lastTag.dataset.value);
            }
        }
        if (event.key === 'Escape') {
            this.closeDropdown();
        }
    }
}
