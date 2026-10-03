/*window.addEventListener("click", function (event) {
    let menu = getElement('navbarMenu');

    /!*if (!menu.contains(event.target) && !menu.classList.contains('hidden')) {
        menu.classList.add('hidden');
    }*!/
})*/

/**
 * Toggles the visibility of an element.
 * @param {HTMLElement} target - The element to toggle.
 */
function toggleVisibility(target)
{
    if (!target) {
        return;
    }
    target.classList.toggle('hidden');
}

/**
 * Handles the logic for a "collapse" toggle.
 * @param {HTMLElement} element - The element that triggers the collapse.
 */
export function toggleCollapse(element)
{
    const target = document.getElementById(element.dataset.target);
    const elementOntoggle = document.getElementById(element.dataset.ontoggle);

    toggleVisibility(target);
    if (elementOntoggle) {
        toggleVisibility(elementOntoggle);
    }
}

/**
 * Handles the logic for a "block" toggle.
 * @param {HTMLElement} element - The element that triggers the block switch.
 */
export function toggleBlock(element)
{
    const target = document.getElementById(element.dataset.target);
    const elementOntoggle = document.getElementById(element.dataset.ontoggle);

    toggleVisibility(target);
    if (elementOntoggle) {
        toggleVisibility(elementOntoggle);
    }
}

/**
 * Handles tab switching.
 * @param {HTMLElement} element - The element that triggers the tab switch.
 */
const toggleTab = element => {
    const target = document.getElementById(element.dataset.target);
    const tabList = element.closest('.tablist');
    const tabsContainer = document.getElementById(tabList.dataset.target);

    if (!target || !tabsContainer) {
        return;
    }

    // Hide all tab contents
    tabsContainer.querySelectorAll('.tabcontent').forEach(tabContent => {
        tabContent.style.display = 'none';
    });

    // Remove the 'tab-active' class from all tabs
    tabList.querySelectorAll('.tab').forEach(tab => {
        tab.classList.remove('tab-active');
    });

    // Show the active tab and mark it as active
    target.style.display = 'block';
    element.classList.add('tab-active');
};

/**
 * Event handler for the different toggle types.
 * Maps each toggle type to its respective handler function.
 */
const eventHandler = element => {
    const toggleType = element.dataset.toggle;
    const toggleActions = {
        'collapse': toggleCollapse,
        'tab': toggleTab,
        'block': toggleBlock
    };

    const action = toggleActions[toggleType];
    if (action) {
        action(element);
    } else {
        toggleVisibility(document.getElementById(element.dataset.target));
    }
};

document.addEventListener('click', event => {
    const element = event.target.closest('[data-toggle]');
    if (!element) {
        return;
    }

    const toggleType = element.dataset.toggle;
    if (toggleType === 'modal') {
        return;
    }

    eventHandler(element);
}, false);
